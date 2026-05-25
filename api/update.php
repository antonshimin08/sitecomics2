<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';

try {
    // Проверка метода запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method Not Allowed. Use PUT.'
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

    // Валидация и подготовка обновляемых полей
    $updateFields = [];
    $params = [':id' => $id];

    if (isset($data['title'])) {
        $title = trim((string)$data['title']);
        if (empty($title)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Field "title" cannot be empty'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $updateFields[] = 'title = :title';
        $params[':title'] = $title;
    }

    if (isset($data['price'])) {
        $price = (int)$data['price'];
        if ($price <= 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Field "price" must be a positive number'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $updateFields[] = 'price = :price';
        $params[':price'] = $price;
    }

    if (isset($data['image'])) {
        $image = trim((string)$data['image']);
        if (empty($image)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Field "image" cannot be empty'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $updateFields[] = 'image = :image';
        $params[':image'] = $image;
    }

    if (isset($data['category_id'])) {
        $categoryId = (int)$data['category_id'];
        
        // Проверка существования категории
        $catCheckSql = "SELECT id FROM categories WHERE id = :cat_id";
        $catCheckStmt = $pdo->prepare($catCheckSql);
        $catCheckStmt->bindValue(':cat_id', $categoryId, PDO::PARAM_INT);
        $catCheckStmt->execute();

        if (!$catCheckStmt->fetch()) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Category with ID ' . $categoryId . ' not found'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $updateFields[] = 'category_id = :category_id';
        $params[':category_id'] = $categoryId;
    }

    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'No fields to update'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Обновление записи
    $sql = "UPDATE comics SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();

    // Возврат обновленной записи
    $getSql = "SELECT c.id, c.title, c.price, c.image, cat.name AS category 
               FROM comics c 
               JOIN categories cat ON cat.id = c.category_id 
               WHERE c.id = :id";
    $getStmt = $pdo->prepare($getSql);
    $getStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $getStmt->execute();
    $comic = $getStmt->fetch();

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Comic updated successfully',
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
