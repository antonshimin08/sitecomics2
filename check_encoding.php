<?php
require_once __DIR__ . '/db.php';

header('Content-Type: text/html; charset=utf-8');

$test = $pdo->query("SELECT id, title, category_id FROM comics LIMIT 5")->fetchAll();

echo "<h2>Проверка данных из БД</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title (raw)</th><th>Title (hex)</th><th>Длина</th></tr>";

foreach ($test as $row) {
    $title = $row['title'];
    $hex = bin2hex($title);
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td><small>$hex</small></td>";
    echo "<td>" . strlen($title) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Информация о кодировке соединения:</h3>";
$charset = $pdo->query("SHOW VARIABLES LIKE 'character_set_connection'")->fetch();
$collation = $pdo->query("SHOW VARIABLES LIKE 'collation_connection'")->fetch();
echo "<pre>";
print_r($charset);
print_r($collation);
echo "</pre>";
?>