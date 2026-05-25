<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';

try {
    // Проверка метода запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method Not Allowed. Use DELETE.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Чтение JSON из php://input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Валидация полей
    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Field "id" is required'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id = (int)$data['id'];

    // Проверка существования записи
    $checkSql = "SELECT id FROM comics WHERE id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Comic with ID ' . $id . ' not found'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Удаление записи
    $deleteSql = "DELETE FROM comics WHERE id = :id";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $deleteStmt->execute();

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Comic with ID ' . $id . ' deleted successfully'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
