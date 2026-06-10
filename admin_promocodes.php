<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Добавление промокода
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promo'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount = (int)$_POST['discount'];
    $days = (int)$_POST['days'];
    $limit = $_POST['limit'] ? (int)$_POST['limit'] : null;
    
    $stmt = $pdo->prepare("INSERT INTO promocodes (code, discount_percent, expires_at, used_limit) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), ?)");
    $stmt->execute([$code, $discount, $days, $limit]);
    $_SESSION['message'] = "Промокод {$code} добавлен!";
    header('Location: admin_promocodes.php');
    exit;
}

// Удаление промокода
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM promocodes WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    $_SESSION['message'] = 'Промокод удалён';
    header('Location: admin_promocodes.php');
    exit;
}

$promocodes = $pdo->query("SELECT * FROM promocodes ORDER BY expires_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Управление промокодами</title><link rel="stylesheet" href="main.css"></head>
<body>
<div style="max-width:1000px; margin:20px auto; padding:20px;">
    <h1 style="color:white;">🎫 Управление промокодами</h1>
    
    <div style="background:#1a1f2e; padding:20px; border-radius:12px; margin-bottom:30px;">
        <h3 style="color:white;">➕ Добавить промокод</h3>
        <form method="post">
            <input type="hidden" name="add_promo" value="1">
            <input type="text" name="code" placeholder="Код (например SUMMER30)" required style="padding:10px; margin:5px; width:200px;">
            <input type="number" name="discount" placeholder="Скидка %" required style="padding:10px; margin:5px; width:100px;">
            <input type="number" name="days" placeholder="Дней действия" value="30" style="padding:10px; margin:5px; width:100px;">
            <input type="number" name="limit" placeholder="Лимит использований (пусто = без лимита)" style="padding:10px; margin:5px; width:150px;">
            <button type="submit" style="background:#28a745; color:white; padding:10px 20px; border:none; border-radius:6px; cursor:pointer;">➕ Добавить</button>
        </form>
    </div>
    
    <table style="width:100%; background:#1a1f2e; border-radius:12px; overflow:hidden;">
        <thead><tr style="background:#2a2f3e; color:white;"><th style="padding:12px;">Код</th><th>Скидка</th><th>Действует до</th><th>Использовано</th><th>Действие</th></tr></thead>
        <tbody>
        <?php foreach($promocodes as $p): ?>
            <tr style="border-bottom:1px solid #2a2f3e;">
                <td style="padding:12px; color:#ffc107;"><?php echo $p['code']; ?></td>
                <td style="color:#28a745;"><?php echo $p['discount_percent']; ?>%</td>
                <td style="color:#a0a0b0;"><?php echo date('d.m.Y', strtotime($p['expires_at'])); ?></td>
                <td style="color:#a0a0b0;"><?php echo $p['used_count']; ?><?php echo $p['used_limit'] ? '/'.$p['used_limit'] : ''; ?></td>
                <td><a href="?delete=<?php echo $p['id']; ?>" style="color:#dc3545;" onclick="return confirm('Удалить?')">🗑️ Удалить</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p style="margin-top:20px;"><a href="index.php" style="color:#667eea;">← На главную</a></p>
</div>
</body>
</html>