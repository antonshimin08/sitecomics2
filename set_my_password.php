<?php
require_once __DIR__ . '/db.php';
$myPassword = 'admin123'; 
$hash = password_hash($myPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
$stmt->execute([$hash]);
echo "✅ Пароль для администратора обновлён!<br>";
echo "Логин: <strong>admin</strong><br>";
echo "Новый пароль: <strong>" . htmlspecialchars($myPassword) . "</strong><br>";
echo "<br><a href='login.php'>Перейти к входу →</a>";
?>