<?php
// cron/verificar_alertas.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$raizProjeto = dirname(__DIR__);

// Garanta que estes caminhos batem com a sua estrutura de pastas
$caminhoBanco = $raizProjeto . '/config/db.php'; 
$caminhoBrapi = $raizProjeto . '/src/Services/BrapiService.php';

if (!file_exists($caminhoBanco) || !file_exists($caminhoBrapi)) {
    die("❌ERRO: Verifique os caminhos do database.php ou BrapiService.php\n");
}

require_once $caminhoBanco;
require_once $caminhoBrapi;

echo "==================================================\n";
echo "[" . date('Y-m-d H:i:s') . "] Iniciando verificação de alertas...\n";
echo "==================================================\n";

try {
    // 1. Buscar apenas alertas pendentes no MySQL
    $stmt = $pdo->prepare("SELECT * FROM alertas WHERE status = 'PENDENTE'");
    $stmt->execute();
    $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($alertas)) {
        echo "Nenhum alerta pendente para verificar.\n";
        exit;
    }

    $brapiService = new BrapiService();
    $alertasDisparados = 0;

    foreach ($alertas as $alerta) {
        $id = $alerta['id'];
        
        // Mapeamento correto com as colunas reais da sua tabela
        $ticker    = strtoupper(trim($alerta['codigo_ativo']));
        $precoAlvo = (float)$alerta['preco_alvo'];
        $tipo      = strtoupper($alerta['tipo_alerta']); // 'COMPRA' ou 'VENDA'

        if (empty($ticker)) {
            echo "⚠️ [ID: $id] Alerta sem código de ativo preenchido. Ignorando...\n";
            continue;
        }

        echo "Verificando [ID: $id] - $ticker | Alvo: R$ " . number_format($precoAlvo, 2, ',', '.') . " ($tipo)... ";

        // Consulta cotação na Brapi
        $dadosBrapi = $brapiService->buscarCotacao($ticker);

        if (!isset($dadosBrapi['results'][0]['regularMarketPrice'])) {
            echo "ERRO: Cotação não encontrada na Brapi.\n";
            continue;
        }

        $precoAtual = (float)$dadosBrapi['results'][0]['regularMarketPrice'];
        echo "Preço Atual: R$ " . number_format($precoAtual, 2, ',', '.') . " -> ";

        $disparar = false;

        // Lógica de disparo:
        // VENDA: avisa ao subir/atingir o preço limite
        // COMPRA: avisa ao cair/atingir o preço limite
        if ($tipo === 'VENDA' && $precoAtual >= $precoAlvo) {
            $disparar = true;
        } elseif ($tipo === 'COMPRA' && $precoAtual <= $precoAlvo) {
            $disparar = true;
        }

        if ($disparar) {
            // Atualiza o status para DISPARADO e grava a data/hora do disparo
            $updateStmt = $pdo->prepare("
                UPDATE alertas 
                SET status = 'DISPARADO', 
                    data_disparo = NOW() 
                WHERE id = :id
            ");
            $updateStmt->execute([':id' => $id]);

            echo "🔥 ALERTA DISPARADO!\n";
            $alertasDisparados++;
        } else {
            echo "Meta não atingida.\n";
        }
    }

    echo "==================================================\n";
    echo "Verificação concluída. Total disparados: $alertasDisparados\n";

} catch (Exception $e) {
    echo "ERRO FATAL NO CRON: " . $e->getMessage() . "\n";
}