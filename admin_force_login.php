<?php
session_start();
require_once __DIR__ . '/db.php';

// Принудительный вход для администратора
$username = 'admin';

$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {
    // Устанавливаем сессию принудительно
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = 'admin';  // Принудительно ставим роль admin
    
    echo "✅ Вы успешно вошли как администратор!<br>";
    echo "Логин: " . $user['username'] . "<br>";
    echo "Роль: admin<br>";
    echo "<br><a href='index.php'>Перейти в админ-панель →</a>";
    echo "<br><a href='admin_reviews.php'>Модерация рецензий →</a>";
    echo "<br><a href='admin_promocodes.php'>Управление промокодами →</a>";
    exit;
} else {
    echo "❌ Пользователь admin не найден в базе данных!<br>";
    
    // Создаём админа
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, 'dummy', 'admin')");
    $stmt->execute(['admin', 'admin@comicuniverse.com']);
    
    // Получаем созданного пользователя
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $newUser = $stmt->fetch();
    
    $_SESSION['user_id'] = $newUser['id'];
    $_SESSION['username'] = $newUser['username'];
    $_SESSION['role'] = 'admin';
    
    echo "✅ Администратор создан и авторизован!<br>";
    echo "<br><a href='index.php'>Перейти в админ-панель →</a>";
}
?>