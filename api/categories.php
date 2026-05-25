<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';

try {
    // SQL запрос для получения всех категорий
    $sql = "SELECT id, name FROM categories ORDER BY name ASC";
    
    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll();

    // Возврат категорий в JSON формате
    echo json_encode([
        'status' => 'success',
        'count' => count($categories),
        'data' => $categories
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
