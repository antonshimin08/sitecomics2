<?php
require_once __DIR__ . '/db.php';

// Устанавливаем точный хеш для пароля 'admin123'
$hash = '$2y$10$92IXUNpkj0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
$stmt->execute([$hash]);

echo "✅ Пароль администратора обновлён!<br>";
echo "Логин: <strong>admin</strong><br>";
echo "Пароль: <strong>admin123</strong><br>";
echo "<br>";

// Проверяем
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$user = $stmt->fetch();

if ($user && password_verify('admin123', $user['password_hash'])) {
    echo "<span style='color:green; font-weight:bold;'>✅ ПРОВЕРКА ПРОЙДЕНА! Пароль работает.</span><br>";
} else {
    echo "<span style='color:red; font-weight:bold;'>❌ Ошибка! Пароль не совпадает.</span><br>";
}

echo "<br><a href='login.php'>Перейти к входу →</a>";
?>