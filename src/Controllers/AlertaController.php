<?php

    class AlertaController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // GET /api/alertas
    public function listar(): void {
        $stmt = $this->pdo->query("SELECT * FROM alertas ORDER BY criado_em DESC");
        $alertas = $stmt->fetchAll();
        echo json_encode(['sucesso' => true, 'dados' => $alertas]);
    }

    // POST /api/alertas
    public function criar(): void {
        // Tenta capturar o JSON enviado pelo React Native
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // Aceita tanto as chaves do Frontend (ticker/tipo) quanto do Banco (codigo_ativo/tipo_alerta)
        $rawCodigo = $body['codigo_ativo'] ?? $body['ticker'] ?? null;
        $rawPreco  = $body['preco_alvo']   ?? $body['preco']  ?? null;
        $rawTipo   = $body['tipo_alerta']  ?? $body['tipo']   ?? 'COMPRA'; // Padrão 'COMPRA'

        // Valida se o código do ativo e o preço foram fornecidos
        if (empty($rawCodigo) || empty($rawPreco) || !is_numeric($rawPreco)) {
            http_response_code(400);
            echo json_encode([
                'sucesso' => false, 
                'mensagem' => 'Preencha o código do ativo e um preço alvo válido.'
            ]);
            return;
        }

        $codigoAtivo = strtoupper(trim($rawCodigo));
        $precoAlvo   = (float)$rawPreco;
        $tipoAlerta  = strtoupper(trim($rawTipo));

        // Garante que o tipo seja apenas COMPRA ou VENDA
        if (!in_array($tipoAlerta, ['COMPRA', 'VENDA'])) {
            $tipoAlerta = 'COMPRA';
        }

        $sql = "INSERT INTO alertas (codigo_ativo, preco_alvo, tipo_alerta, status) 
                VALUES (:codigo_ativo, :preco_alvo, :tipo_alerta, 'PENDENTE')";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $sucesso = $stmt->execute([
                ':codigo_ativo' => $codigoAtivo,
                ':preco_alvo'   => $precoAlvo,
                ':tipo_alerta'  => $tipoAlerta
            ]);

            if ($sucesso) {
                http_response_code(201);
                echo json_encode([
                    'sucesso' => true, 
                    'mensagem' => 'Alerta cadastrado com sucesso!', 
                    'id' => $this->pdo->lastInsertId()
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar o alerta.']);
            }
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
        }
    }

    // DELETE /api/alertas/{id}
   public function deletar(int $id): void {
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'mensagem' => 'ID não informado.']);
            return;
        }
        try {
            $stmt = $this->pdo->prepare("DELETE FROM alertas WHERE id = :id");
            $sucesso = $stmt->execute([':id' => $id]);

            if ($sucesso) {
                http_response_code(200);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Alerta removido com sucesso.']);
            } else {
                http_response_code(500);
                echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao deletar alerta.']);
            }
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
        }
    }
    
}