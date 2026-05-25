<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';

try {
    // Получение ID комикса из параметра GET
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Parameter "id" is required'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // SQL запрос с JOIN для получения одного комикса с категорией
    $sql = "SELECT c.id, c.title, c.price, c.image, cat.name AS category 
            FROM comics c 
            JOIN categories cat ON cat.id = c.category_id 
            WHERE c.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $comic = $stmt->fetch();

    if (!$comic) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Comic not found'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Возврат найденного комикса в JSON формате
    echo json_encode([
        'status' => 'success',
        'data' => $comic
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
