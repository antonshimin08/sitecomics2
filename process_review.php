<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

// Только для администратора
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    die('Доступ запрещён');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reviewId = (int)$_POST['review_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        // Одобряем рецензию
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved', approved_at = NOW() WHERE id = ?");
        $stmt->execute([$reviewId]);
        
        // Получаем comic_id для обновления рейтинга
        $stmt = $pdo->prepare("SELECT comic_id, rating FROM reviews WHERE id = ?");
        $stmt->execute([$reviewId]);
        $review = $stmt->fetch();
        
        // Пересчитываем средний рейтинг комикса
        $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE comic_id = ? AND status = 'approved'");
        $stmt->execute([$review['comic_id']]);
        $avgRating = $stmt->fetch()['avg_rating'];
        
        $stmt = $pdo->prepare("UPDATE comics SET rating = ? WHERE id = ?");
        $stmt->execute([round($avgRating, 1), $review['comic_id']]);
        
        $_SESSION['message'] = 'Рецензия одобрена, рейтинг обновлён!';
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$reviewId]);
        $_SESSION['message'] = 'Рецензия отклонена';
    }
    
    header('Location: admin_reviews.php');
    exit;
}

// Получаем все рецензии на модерации
$stmt = $pdo->prepare("
    SELECT r.*, c.title, c.image, u.username 
    FROM reviews r
    JOIN comics c ON c.id = r.comic_id
    JOIN users u ON u.id = r.user_id
    WHERE r.status = 'pending'
    ORDER BY r.created_at DESC
");
$stmt->execute();
$pendingReviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Модерация рецензий | Comic Universe</title>
    <link rel="stylesheet" href="main.css">
    <style>
        .admin-container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .review-card { background: #1a1f2e; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .review-header { display: flex; gap: 15px; margin-bottom: 15px; align-items: center; flex-wrap: wrap; }
        .review-rating { color: #ffc107; font-size: 20px; }
        .review-text { color: #e0e0e0; line-height: 1.5; margin: 10px 0; }
        .btn-approve { background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; margin-right: 10px; }
        .btn-reject { background: #dc3545; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; }
        .btn-approve:hover { background: #218838; }
        .btn-reject:hover { background: #c82333; }
    </style>
</head>
<body>
<div class="admin-container">
    <h1 style="color: white;">📝 Модерация рецензий</h1>
    <?php if (empty($pendingReviews)): ?>
        <p style="color: #a0a0b0;">Нет рецензий на модерации</p>
    <?php else: ?>
        <?php foreach ($pendingReviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <img src="images/<?php echo htmlspecialchars($review['image']); ?>" width="50" style="border-radius: 6px;" onerror="this.src='https://placehold.co/50x70/1a1f2e/667eea?text=No'">
                    <div>
                        <strong style="color: white;"><?php echo htmlspecialchars($review['title']); ?></strong><br>
                        <small style="color: #a0a0b0;">Автор: <?php echo htmlspecialchars($review['username']); ?></small>
                    </div>
                    <div class="review-rating"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></div>
                </div>
                <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                <div style="margin-top: 15px;">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn-approve">✅ Одобрить</button>
                    </form>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn-reject">❌ Отклонить</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <p style="margin-top: 20px;"><a href="index.php" style="color: #667eea;">← На главную</a></p>
</div>
</body>
</html>