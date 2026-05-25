<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';

try {
    // Проверка метода запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method Not Allowed. Use POST.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Чтение JSON из php://input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Валидация полей
    if (!$data || !isset($data['title']) || !isset($data['price']) || !isset($data['image']) || !isset($data['category_id'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields: title, price, image, category_id'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Валидация типов данных
    $title = trim((string)$data['title']);
    $price = (int)$data['price'];
    $image = trim((string)$data['image']);
    $categoryId = (int)$data['category_id'];

    if (empty($title) || $price <= 0 || empty($image) || $categoryId <= 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid field values: title and image must not be empty, price and category_id must be positive numbers'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Проверка существования категории
    $catCheckSql = "SELECT id FROM categories WHERE id = :id";
    $catCheckStmt = $pdo->prepare($catCheckSql);
    $catCheckStmt->bindValue(':id', $categoryId, PDO::PARAM_INT);
    $catCheckStmt->execute();

    if (!$catCheckStmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Category with ID ' . $categoryId . ' not found'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Вставка новой записи
    $sql = "INSERT INTO comics (title, price, image, category_id) VALUES (:title, :price, :image, :category_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':price', $price, PDO::PARAM_INT);
    $stmt->bindValue(':image', $image, PDO::PARAM_STR);
    $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    $stmt->execute();

    $newId = $pdo->lastInsertId();

    // Возврат созданной записи
    $getSql = "SELECT c.id, c.title, c.price, c.image, cat.name AS category 
               FROM comics c 
               JOIN categories cat ON cat.id = c.category_id 
               WHERE c.id = :id";
    $getStmt = $pdo->prepare($getSql);
    $getStmt->bindValue(':id', $newId, PDO::PARAM_INT);
    $getStmt->execute();
    $comic = $getStmt->fetch();

    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Comic created successfully',
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
