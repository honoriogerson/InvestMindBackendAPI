<?php
// Força a exibição de erros do PHP na resposta
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Configurações de CORS para aceitar conexões do app mobile
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Importação correta dos arquivos
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Services/BrapiService.php';
require_once __DIR__ . '/../src/Controllers/AlertaController.php';

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 3. Instanciação dos Controllers com os nomes em português
$alertaController = new AlertaController($pdo);
$brapiService     = new BrapiService();

// --- ROTAS ---

// GET /api/cotacao?ticker=PETR4
if ($method === 'GET' && $path === '/api/cotacao') {
    $ticker = $_GET['ticker'] ?? 'PETR4';
    $dados  = $brapiService->buscarCotacao($ticker);
    echo json_encode($dados);
    exit;
}

// GET /api/alertas
if ($method === 'GET' && $path === '/api/alertas') {
    $alertaController->listar();
    exit;
}

// POST /api/alertas
if ($method === 'POST' && $path === '/api/alertas') {
    $alertaController->criar();
    exit;
}

// DELETE /api/alertas OU /api/alertas/3
if ($method === 'DELETE' && strpos($path, '/api/alertas') === 0) {

    // 1. Tenta pegar o ID direto do caminho (/api/alertas/3)
    $partes = explode('/', trim($path, '/'));
    $id = end($partes);

    // 2. Se o último elemento não for numérico, tenta pegar do $_GET (?id=3)
    if (!is_numeric($id)) {
        $id = $_GET['id'] ?? null;
    }

    if ($id && is_numeric($id)) {
        $alertaController->deletar((int)$id);
    } else {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID do alerta não fornecido ou inválido.']);
    }
    exit;
}

// Rota não encontrada
http_response_code(404);
echo json_encode(['sucesso' => false, 'mensagem' => 'Rota não encontrada.']);