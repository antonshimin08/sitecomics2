<?php
require_once __DIR__ . '/db.php';

echo "<h1>Восстановление данных</h1>";

// Очищаем
$pdo->exec("TRUNCATE TABLE comics");
$pdo->exec("TRUNCATE TABLE categories");

// Категории
$categories = [
    ['id' => 1, 'name' => 'Marvel'],
    ['id' => 2, 'name' => 'DC Comics'],
    ['id' => 3, 'name' => 'Image Comics'],
    ['id' => 4, 'name' => 'Dark Horse'],
    ['id' => 5, 'name' => 'Manga'],
    ['id' => 6, 'name' => 'Инди'],
    ['id' => 7, 'name' => 'Французские'],
    ['id' => 8, 'name' => 'Классика'],
];

$stmt = $pdo->prepare("INSERT INTO categories (id, name) VALUES (?, ?)");
foreach ($categories as $cat) {
    $stmt->execute([$cat['id'], $cat['name']]);
}
echo "<p>Категории: " . count($categories) . " вставлено</p>";

// Комиксы - замените на ваши реальные данные
$comics = [
    ['title' => 'Человек-паук: Паутина жизни', 'price' => 4500, 'category_id' => 1, 'image' => 'spider_man.jpg'],
    ['title' => 'Бэтмен: Тёмный рыцарь', 'price' => 5200, 'category_id' => 2, 'image' => 'batman.jpg'],
    ['title' => 'Мстители: Финал', 'price' => 4900, 'category_id' => 1, 'image' => 'avengers.jpg'],
    ['title' => 'Ходячие мертвецы', 'price' => 3800, 'category_id' => 3, 'image' => 'walking_dead.jpg'],
    ['title' => 'Трансформеры', 'price' => 3500, 'category_id' => 4, 'image' => 'transformers.jpg'],
    ['title' => 'Наруто', 'price' => 4200, 'category_id' => 5, 'image' => 'naruto.jpg'],
    ['title' => 'Сага', 'price' => 3900, 'category_id' => 6, 'image' => 'saga.jpg'],
    ['title' => 'Черный отряд', 'price' => 4100, 'category_id' => 7, 'image' => 'black_squad.jpg'],
    ['title' => 'Бессмертный', 'price' => 4700, 'category_id' => 6, 'image' => 'invincible.jpg'],
    ['title' => 'Хеллбой', 'price' => 4400, 'category_id' => 4, 'image' => 'hellboy.jpg'],
    ['title' => 'Лига справедливости', 'price' => 5300, 'category_id' => 2, 'image' => 'justice_league.jpg'],
    ['title' => 'Люди Икс', 'price' => 4800, 'category_id' => 1, 'image' => 'x_men.jpg'],
    ['title' => 'Ванпанчмен', 'price' => 3900, 'category_id' => 5, 'image' => 'one_punch.jpg'],
    ['title' => 'Скотт Пилигрим', 'price' => 3600, 'category_id' => 6, 'image' => 'scott_pilgrim.jpg'],
];

$stmt = $pdo->prepare("INSERT INTO comics (title, price, category_id, image) VALUES (?, ?, ?, ?)");
foreach ($comics as $comic) {
    $stmt->execute([$comic['title'], $comic['price'], $comic['category_id'], $comic['image']]);
}
echo "<p>Комиксы: " . count($comics) . " вставлено</p>";

echo "<h2>✅ Готово! <a href='index.php'>На главную</a></h2>";
?>