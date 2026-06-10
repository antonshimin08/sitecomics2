<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

// Только для администратора
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reviewId = (int)$_POST['review_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
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
        
        $_SESSION['message'] = '✅ Рецензия одобрена, рейтинг обновлён!';
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$reviewId]);
        $_SESSION['message'] = '❌ Рецензия отклонена';
    }
    
    header('Location: admin_reviews.php');
    exit;
}

// Фильтрация по статусу
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$validFilters = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($statusFilter, $validFilters)) {
    $statusFilter = 'pending';
}

// Построение запроса с фильтром
$sql = "
    SELECT r.*, c.title, c.image, u.username 
    FROM reviews r
    JOIN comics c ON c.id = r.comic_id
    JOIN users u ON u.id = r.user_id
";
if ($statusFilter !== 'all') {
    $sql .= " WHERE r.status = :status";
    $sql .= " ORDER BY r.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['status' => $statusFilter]);
} else {
    $sql .= " ORDER BY r.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
$reviews = $stmt->fetchAll();

// Статистика
$stmt = $pdo->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'");
$pendingCount = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'approved'");
$approvedCount = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM reviews WHERE status = 'rejected'");
$rejectedCount = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM reviews");
$totalCount = $stmt->fetch()['count'];

$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Модерация рецензий | Comic Universe</title>
    <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
    <style>
        .admin-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1 { color: white; margin-bottom: 20px; }
        
        /* Статистика */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #1a1f2e; border-radius: 12px; padding: 20px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: bold; }
        .stat-label { color: #a0a0b0; margin-top: 8px; }
        .stat-pending .stat-number { color: #ffc107; }
        .stat-approved .stat-number { color: #28a745; }
        .stat-rejected .stat-number { color: #dc3545; }
        .stat-total .stat-number { color: #667eea; }
        
        /* Фильтры */
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-btn { background: #2a2f3e; border: none; padding: 10px 20px; border-radius: 8px; color: #a0a0b0; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .filter-btn:hover { background: #3a3f4e; color: white; }
        .filter-btn.active { background: #667eea; color: white; }
        
        /* Рецензии */
        .review-card { background: #1a1f2e; border-radius: 12px; padding: 20px; margin-bottom: 20px; border-left: 4px solid; }
        .review-pending { border-left-color: #ffc107; }
        .review-approved { border-left-color: #28a745; }
        .review-rejected { border-left-color: #dc3545; }
        .review-header { display: flex; gap: 15px; margin-bottom: 15px; align-items: center; flex-wrap: wrap; }
        .review-image { width: 60px; height: 80px; object-fit: cover; border-radius: 8px; }
        .review-info { flex: 1; }
        .review-title { color: white; font-size: 18px; font-weight: bold; }
        .review-author { color: #a0a0b0; font-size: 13px; margin-top: 4px; }
        .review-rating { color: #ffc107; font-size: 18px; margin: 8px 0; }
        .review-text { color: #e0e0e0; line-height: 1.5; background: #2a2f3e; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .review-date { font-size: 12px; color: #666; margin-top: 10px; }
        .review-status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .status-pending { background: #ffc107; color: #333; }
        .status-approved { background: #28a745; color: white; }
        .status-rejected { background: #dc3545; color: white; }
        .review-actions { margin-top: 15px; display: flex; gap: 10px; }
        .btn-approve { background: #28a745; color: white; border: none; padding: 8px 24px; border-radius: 6px; cursor: pointer; }
        .btn-approve:hover { background: #218838; }
        .btn-reject { background: #dc3545; color: white; border: none; padding: 8px 24px; border-radius: 6px; cursor: pointer; }
        .btn-reject:hover { background: #c82333; }
        .btn-disabled { background: #6c757d; color: white; padding: 8px 24px; border-radius: 6px; cursor: not-allowed; border: none; }
        .empty-state { text-align: center; padding: 60px; background: #1a1f2e; border-radius: 12px; color: #a0a0b0; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
        hr { border-color: #2a2f3e; margin: 20px 0; }
    </style>
</head>
<body>
<div class="page">
    <header class="site-header">
        <div class="site-brand">
            <div class="site-brand__logo">CU</div>
            <div><h1 class="site-brand__title">Comic Universe</h1><p class="site-brand__hint">Панель администратора</p></div>
        </div>
        <nav class="site-nav">
            <a class="site-nav__link" href="index.php">Каталог</a>
            <a class="site-nav__link" href="admin_reviews.php">📝 Рецензии</a>
            <a class="site-nav__link" href="admin_promocodes.php">🎫 Промокоды</a>
            <a class="site-nav__button" href="cart.php">Корзина</a>
            <span class="site-nav__link">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a class="site-nav__button" href="logout.php" style="background: #dc3545;">Выход</a>
        </nav>
    </header>

    <div class="admin-container">
        <h1>📝 Модерация рецензий</h1>
        
        <?php if ($message): ?>
            <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card stat-pending">
                <div class="stat-number"><?php echo $pendingCount; ?></div>
                <div class="stat-label">⏳ На модерации</div>
            </div>
            <div class="stat-card stat-approved">
                <div class="stat-number"><?php echo $approvedCount; ?></div>
                <div class="stat-label">✅ Одобрено</div>
            </div>
            <div class="stat-card stat-rejected">
                <div class="stat-number"><?php echo $rejectedCount; ?></div>
                <div class="stat-label">❌ Отклонено</div>
            </div>
            <div class="stat-card stat-total">
                <div class="stat-number"><?php echo $totalCount; ?></div>
                <div class="stat-label">📊 Всего</div>
            </div>
        </div>
        
        <!-- Фильтры -->
        <div class="filter-tabs">
            <a href="?status=pending" class="filter-btn <?php echo $statusFilter == 'pending' ? 'active' : ''; ?>">⏳ На модерации (<?php echo $pendingCount; ?>)</a>
            <a href="?status=approved" class="filter-btn <?php echo $statusFilter == 'approved' ? 'active' : ''; ?>">✅ Одобренные (<?php echo $approvedCount; ?>)</a>
            <a href="?status=rejected" class="filter-btn <?php echo $statusFilter == 'rejected' ? 'active' : ''; ?>">❌ Отклонённые (<?php echo $rejectedCount; ?>)</a>
            <a href="?status=all" class="filter-btn <?php echo $statusFilter == 'all' ? 'active' : ''; ?>">📋 Все рецензии (<?php echo $totalCount; ?>)</a>
        </div>
        
        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <?php if ($statusFilter == 'pending'): ?>
                    <p>✅ Нет рецензий на модерации</p>
                <?php elseif ($statusFilter == 'approved'): ?>
                    <p>📝 Нет одобренных рецензий</p>
                <?php elseif ($statusFilter == 'rejected'): ?>
                    <p>📝 Нет отклонённых рецензий</p>
                <?php else: ?>
                    <p>📝 Пока нет ни одной рецензии</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card review-<?php echo $review['status']; ?>">
                    <div class="review-header">
                        <img class="review-image" src="images/<?php echo htmlspecialchars($review['image']); ?>" onerror="this.src='https://placehold.co/60x80/1a1f2e/667eea?text=No'">
                        <div class="review-info">
                            <div class="review-title"><?php echo htmlspecialchars($review['title']); ?></div>
                            <div class="review-author">👤 Автор: <?php echo htmlspecialchars($review['username']); ?></div>
                            <div class="review-rating"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></div>
                        </div>
                        <div class="review-status-badge status-<?php echo $review['status']; ?>">
                            <?php if ($review['status'] == 'pending'): ?>
                                ⏳ На модерации
                            <?php elseif ($review['status'] == 'approved'): ?>
                                ✅ Одобрена
                            <?php else: ?>
                                ❌ Отклонена
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="review-text">
                        <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                    </div>
                    
                    <div class="review-date">
                        📅 Дата написания: <?php echo date('d.m.Y H:i', strtotime($review['created_at'])); ?>
                        <?php if ($review['approved_at']): ?>
                            <br>✅ Одобрена: <?php echo date('d.m.Y H:i', strtotime($review['approved_at'])); ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($review['status'] == 'pending'): ?>
                        <div class="review-actions">
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
                    <?php else: ?>
                        <div class="review-actions">
                            <button class="btn-disabled" disabled>
                                <?php echo $review['status'] == 'approved' ? '✅ Уже одобрена' : '❌ Уже отклонена'; ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <hr>
        <p style="color: #a0a0b0; text-align: center;">
            <a href="index.php" style="color: #667eea;">← На главную</a>
        </p>
    </div>
    
    <footer class="page-footer">
        Comic Universe © 2026 — Панель администратора
    </footer>
</div>
</body>
</html>