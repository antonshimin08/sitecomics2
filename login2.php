<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Вход</title><style>body{background:#0f111a;display:flex;justify-content:center;align-items:center;height:100vh;font-family:Arial}.box{background:#1a1f2e;padding:30px;border-radius:12px;width:300px}h2{color:white}input{width:100%;padding:10px;margin:10px 0;background:#2a2f3e;border:1px solid #3a3f4e;color:white;border-radius:6px}button{width:100%;padding:10px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer}.error{color:#ff6b6b}</style></head>
<body>
<div class="box">
    <h2>🔐 Вход</h2>
    <?php if($error):?><div class="error"><?php echo $error;?></div><?php endif;?>
    <form method="post">
        <input type="text" name="username" placeholder="Логин (admin)" required>
        <input type="password" name="password" placeholder="Пароль (admin123)" required>
        <button type="submit">Войти</button>
    </form>
</div>
</body>
</html>