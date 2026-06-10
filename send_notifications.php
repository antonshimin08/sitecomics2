<?php
// Запускайте этот скрипт после добавления нового комикса в серию
// Или добавьте вызов функции при добавлении комикса

require_once __DIR__ . '/db.php';

function sendNewIssueNotifications($seriesId, $newComicTitle, $comicId) {
    global $pdo;
    
    // Получаем всех подписчиков серии
    $stmt = $pdo->prepare("
        SELECT ss.user_id, u.username, u.email 
        FROM series_subscriptions ss
        JOIN users u ON u.id = ss.user_id
        WHERE ss.series_id = ?
    ");
    $stmt->execute([$seriesId]);
    $subscribers = $stmt->fetchAll();
    
    // Получаем информацию о серии
    $stmt = $pdo->prepare("SELECT name FROM series WHERE id = ?");
    $stmt->execute([$seriesId]);
    $series = $stmt->fetch();
    
    $notifications = [];
    foreach ($subscribers as $sub) {
        // Добавляем уведомление в БД
        $stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, comic_id, type, is_read) VALUES (?, ?, 'new_issue', 0)");
        $stmt->execute([$sub['user_id'], $comicId]);
        
        // Отправляем email
        $to = $sub['email'];
        $subject = "Новый выпуск серии «{$series['name']}»!";
        $message = "Здравствуйте, {$sub['username']}!\n\n";
        $message .= "Вышёл новый комикс «{$newComicTitle}» в серии «{$series['name']}»!\n";
        $message .= "Спешите купить: https://comic-universe.xo.je/comic.php?id={$comicId}\n\n";
        $message .= "С уважением, Comic Universe";
        
        mail($to, $subject, $message, "From: noreply@comicuniverse.com");
        
        $notifications[] = $sub['username'];
    }
    
    return $notifications;
}

// Пример использования при добавлении комикса
if (isset($_GET['series_id']) && isset($_GET['comic_id']) && isset($_GET['title'])) {
    $seriesId = (int)$_GET['series_id'];
    $comicId = (int)$_GET['comic_id'];
    $title = $_GET['title'];
    
    $sent = sendNewIssueNotifications($seriesId, $title, $comicId);
    echo "Уведомления отправлены: " . implode(', ', $sent);
}
?>