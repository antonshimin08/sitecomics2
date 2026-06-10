<?php
session_start();
require_once __DIR__ . '/db.php';

echo "<h1>Сброс пароля администратора</h1>";

// Проверяем, есть ли пользователь admin
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    echo "✅ Пользователь admin найден!<br>";
    echo "ID: " . $admin['id'] . "<br>";
    echo "Текущий хеш пароля: " . $admin['password_hash'] . "<br><br>";
    
    // Устанавливаем новый пароль admin123
    $newPassword = 'admin123';
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $updateStmt->execute([$newHash]);
    
    echo "✅ Пароль обновлён!<br>";
    echo "Новый хеш: " . $newHash . "<br><br>";
    
    // Проверяем что пароль работает
    if (password_verify($newPassword, $newHash)) {
        echo "<span style='color:green; font-weight:bold;'>✅ ПРОВЕРКА ПРОЙДЕНА! Пароль '{$newPassword}' работает.</span><br>";
    } else {
        echo "<span style='color:red;'>❌ Ошибка проверки пароля!</span><br>";
    }
} else {
    echo "❌ Пользователь admin НЕ НАЙДЕН! Создаём...<br>";
    
    $newPassword = 'admin123';
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
    $insertStmt->execute(['admin', 'admin@comicuniverse.com', $newHash]);
    
    echo "✅ Администратор создан!<br>";
    echo "Логин: admin<br>";
    echo "Пароль: {$newPassword}<br>";
}

echo "<br><a href='login.php'>Перейти к входу →</a>";
?>