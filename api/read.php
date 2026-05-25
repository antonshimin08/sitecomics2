<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';

try {
    // Получение параметра limit из GET запроса
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Защита от слишком больших значений
    if ($limit > 100) {
        $limit = 100;
    }
    if ($limit < 1) {
        $limit = 10;
    }

    // SQL запрос с JOIN для получения комиксов и категорий
    $sql = "SELECT c.id, c.title, c.price, c.image, cat.name AS category 
            FROM comics c 
            JOIN categories cat ON cat.id = c.category_id 
            ORDER BY c.id ASC 
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $comics = $stmt->fetchAll();

    // Возврат JSON с флагом JSON_UNESCAPED_UNICODE для корректного отображения русского текста
    echo json_encode([
        'status' => 'success',
        'count' => count($comics),
        'data' => $comics
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
