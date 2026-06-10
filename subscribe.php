<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seriesId = (int)$_POST['series_id'];
    $action = $_POST['action'];
    $userId = $_SESSION['user_id'];
    
    if ($action === 'subscribe') {
        $stmt = $pdo->prepare("INSERT INTO series_subscriptions (user_id, series_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $seriesId]);
        $_SESSION['message'] = 'Вы подписались на серию!';
    } elseif ($action === 'unsubscribe') {
        $stmt = $pdo->prepare("DELETE FROM series_subscriptions WHERE user_id = ? AND series_id = ?");
        $stmt->execute([$userId, $seriesId]);
        $_SESSION['message'] = 'Вы отписались от серии.';
    }
}
header('Location: series.php?id=' . $seriesId);
exit;
?>