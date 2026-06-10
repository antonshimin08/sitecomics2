<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// ========== ОБРАБОТКА ФОРМ ==========

// Обновление статуса в коллекции
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $comicId = (int)$_POST['comic_id'];
    $newStatus = $_POST['status'];
    $validStatuses = ['bought', 'reading', 'read', 'want'];
    
    if (in_array($newStatus, $validStatuses)) {
        $stmt = $pdo->prepare("INSERT INTO user_collection (user_id, comic_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = ?");
        $stmt->execute([$userId, $comicId, $newStatus, $newStatus]);
        $_SESSION['message'] = 'Статус обновлён!';
        header('Location: profile.php');
        exit;
    }
}

// Добавление/обновление рецензии
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $comicId = (int)$_POST['comic_id'];
    $rating = (int)$_POST['rating'];
    $reviewText = trim($_POST['review_text']);
    
    if ($rating >= 1 && $rating <= 5 && !empty($reviewText)) {
        // Проверяем, покупал ли пользователь этот комикс
        $stmt = $pdo->prepare("SELECT 1 FROM user_collection WHERE user_id = ? AND comic_id = ? AND status IN ('bought', 'reading', 'read')");
        $stmt->execute([$userId, $comicId]);
        $canReview = $stmt->fetch();
        
        if ($canReview) {
            $stmt = $pdo->prepare("INSERT INTO reviews (user_id, comic_id, rating, review_text, status) VALUES (?, ?, ?, ?, 'pending') ON DUPLICATE KEY UPDATE rating = ?, review_text = ?, status = 'pending'");
            $stmt->execute([$userId, $comicId, $rating, $reviewText, $rating, $reviewText]);
            $_SESSION['message'] = 'Рецензия отправлена на модерацию!';
        } else {
            $_SESSION['error'] = 'Вы можете оставить рецензию только на купленные комиксы';
        }
        header('Location: profile.php?tab=reviews');
        exit;
    }
}

// Подписка/отписка от серии
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe_series'])) {
    $seriesId = (int)$_POST['series_id'];
    $action = $_POST['subscribe_action'];
    
    if ($action === 'subscribe') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO series_subscriptions (user_id, series_id) VALUES (?, ?)");
        $stmt->execute([$userId, $seriesId]);
        $_SESSION['message'] = 'Вы подписались на серию!';
    } elseif ($action === 'unsubscribe') {
        $stmt = $pdo->prepare("DELETE FROM series_subscriptions WHERE user_id = ? AND series_id = ?");
        $stmt->execute([$userId, $seriesId]);
        $_SESSION['message'] = 'Вы отписались от серии';
    }
    header('Location: profile.php?tab=subscriptions');
    exit;
}

// Получаем статистику
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$ordersCount = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
$stmt->execute([$userId]);
$wishlistCount = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_collection WHERE user_id = ?");
$stmt->execute([$userId]);
$collectionCount = $stmt->fetch()['count'];

// Получаем коллекцию по статусам
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM user_collection 
    WHERE user_id = ? 
    GROUP BY status
");
$stmt->execute([$userId]);
$statusCounts = $stmt->fetchAll();
$statusMap = ['bought' => 0, 'reading' => 0, 'read' => 0, 'want' => 0];
foreach ($statusCounts as $sc) {
    $statusMap[$sc['status']] = $sc['count'];
}

// Получаем все комиксы пользователя для коллекции
$stmt = $pdo->prepare("
    SELECT c.*, uc.status
    FROM user_collection uc
    JOIN comics c ON c.id = uc.comic_id
    WHERE uc.user_id = ?
    ORDER BY uc.id DESC
");
$stmt->execute([$userId]);
$userCollection = $stmt->fetchAll();

// Получаем рецензии пользователя
$stmt = $pdo->prepare("
    SELECT r.*, c.title, c.image 
    FROM reviews r
    JOIN comics c ON c.id = r.comic_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$userId]);
$userReviews = $stmt->fetchAll();

// Получаем подписки пользователя
$stmt = $pdo->prepare("
    SELECT s.*
    FROM series_subscriptions ss
    JOIN series s ON s.id = ss.series_id
    WHERE ss.user_id = ?
    ORDER BY ss.id DESC
");
$stmt->execute([$userId]);
$userSubscriptions = $stmt->fetchAll();

// Получаем все серии для подписки
$allSeries = $pdo->query("SELECT id, name, description FROM series ORDER BY name")->fetchAll();
if (!$allSeries) {
    $allSeries = array();
}

// Получаем последние заказы
$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) as items_count 
    FROM orders o 
    LEFT JOIN order_items oi ON oi.order_id = o.id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'collection';
$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['message'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мой профиль | Comic Universe</title>
    <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
    <style>
        .profile-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .profile-header { background: linear-gradient(135deg, #1a1f2e 0%, #2a2f3e 100%); border-radius: 16px; padding: 30px; margin-bottom: 30px; display: flex; align-items: center; gap: 30px; flex-wrap: wrap; }
        .profile-avatar { width: 100px; height: 100px; background: #667eea; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px; color: white; }
        .profile-info h1 { color: white; margin-bottom: 10px; }
        .profile-info p { color: #a0a0b0; margin: 5px 0; }
        .profile-stats { display: flex; gap: 20px; margin-top: 15px; flex-wrap: wrap; }
        .stat-card { background: #1a1f2e; border-radius: 12px; padding: 15px 25px; text-align: center; min-width: 100px; }
        .stat-number { font-size: 28px; font-weight: bold; color: #667eea; }
        .stat-label { color: #a0a0b0; font-size: 12px; margin-top: 5px; }
        .profile-tabs { display: flex; gap: 5px; margin-bottom: 30px; border-bottom: 1px solid #2a2f3e; flex-wrap: wrap; }
        .tab-btn { background: none; border: none; padding: 12px 20px; font-size: 14px; cursor: pointer; color: #a0a0b0; transition: all 0.2s; border-radius: 8px 8px 0 0; }
        .tab-btn.active { color: #667eea; border-bottom: 2px solid #667eea; background: rgba(102,126,234,0.1); }
        .tab-btn:hover { color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .collection-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .collection-card { background: #1a1f2e; border-radius: 12px; overflow: hidden; transition: transform 0.2s; }
        .collection-card:hover { transform: translateY(-4px); }
        .collection-image { width: 100%; height: 200px; object-fit: cover; }
        .collection-body { padding: 15px; }
        .collection-title { color: white; font-size: 1rem; margin-bottom: 5px; }
        .collection-meta { display: flex; justify-content: space-between; color: #a0a0b0; font-size: 12px; margin-bottom: 10px; }
        .status-select { width: 100%; padding: 8px; background: #2a2f3e; border: 1px solid #3a3f4e; color: white; border-radius: 6px; margin-top: 10px; cursor: pointer; }
        .review-card { background: #1a1f2e; border-radius: 12px; padding: 20px; margin-bottom: 15px; }
        .review-header { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; align-items: center; }
        .review-comic-image { width: 50px; height: 70px; object-fit: cover; border-radius: 6px; }
        .review-comic-title { color: white; font-size: 1.1rem; }
        .review-rating { color: #ffc107; font-size: 18px; }
        .review-text { color: #e0e0e0; line-height: 1.5; margin-top: 10px; }
        .review-status { font-size: 12px; padding: 4px 10px; border-radius: 20px; display: inline-block; }
        .status-pending { background: #ffc107; color: #333; }
        .status-approved { background: #28a745; color: white; }
        .status-rejected { background: #dc3545; color: white; }
        .review-form { background: #1a1f2e; border-radius: 12px; padding: 20px; margin-top: 20px; }
        .review-form select, .review-form textarea { width: 100%; padding: 10px; background: #2a2f3e; border: 1px solid #3a3f4e; color: white; border-radius: 6px; margin-bottom: 15px; }
        .review-form textarea { min-height: 100px; resize: vertical; }
        .star-rating { display: flex; gap: 5px; margin-bottom: 15px; }
        .star { font-size: 24px; cursor: pointer; color: #555; transition: color 0.2s; }
        .star.selected { color: #ffc107; }
        .series-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .series-card { background: #1a1f2e; border-radius: 12px; padding: 20px; }
        .series-name { color: white; font-size: 1.2rem; margin-bottom: 10px; }
        .series-desc { color: #a0a0b0; font-size: 13px; margin-bottom: 15px; }
        .subscribe-btn { background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; width: 100%; }
        .unsubscribe-btn { background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; width: 100%; }
        .empty-state { text-align: center; padding: 60px; background: #1a1f2e; border-radius: 12px; color: #a0a0b0; }
        .btn-primary { background: #667eea; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; border: none; cursor: pointer; }
        .btn-primary:hover { background: #5a67d8; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-danger { background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
        .status-filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .status-filter-btn { background: #2a2f3e; border: none; padding: 8px 16px; border-radius: 20px; color: #a0a0b0; cursor: pointer; }
        .status-filter-btn.active { background: #667eea; color: white; }
        .orders-list { display: flex; flex-direction: column; gap: 15px; }
    </style>
</head>
<body>
<div class="page">
    <header class="site-header">
        <div class="site-brand">
            <div class="site-brand__logo">CU</div>
            <div><h1 class="site-brand__title">Comic Universe</h1><p class="site-brand__hint">Коллекция комиксов и коллекционных изданий.</p></div>
        </div>
        <nav class="site-nav">
            <a class="site-nav__link" href="index.php">Каталог</a>
            <a class="site-nav__link" href="series.php">Серии</a>
            <a class="site-nav__link" href="wishlist.php">Вишлист</a>
            <a class="site-nav__link" href="profile.php">Мой профиль</a>
            <a class="site-nav__button" href="cart.php">Корзина (<?php echo array_sum($_SESSION['cart'] ?? []); ?>)</a>
            <?php if (isLoggedIn()): ?>
                <span class="site-nav__link">👤 <?php echo htmlspecialchars($username); ?></span>
                <a class="site-nav__button" href="logout.php" style="background: #dc3545;">Выход</a>
            <?php else: ?>
                <a class="site-nav__button" href="login.php">Вход</a>
                <a class="site-nav__button" href="register.php" style="background: #28a745;">Регистрация</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="profile-container">
        <?php if ($message): ?><div class="alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="profile-header">
            <div class="profile-avatar">👤</div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($username); ?></h1>
                <p>📧 <?php echo htmlspecialchars($username); ?>@comicuniverse.com</p>
                <div class="profile-stats">
                    <div class="stat-card"><div class="stat-number"><?php echo $ordersCount; ?></div><div class="stat-label">Заказов</div></div>
                    <div class="stat-card"><div class="stat-number"><?php echo $wishlistCount; ?></div><div class="stat-label">В вишлисте</div></div>
                    <div class="stat-card"><div class="stat-number"><?php echo $collectionCount; ?></div><div class="stat-label">В коллекции</div></div>
                </div>
            </div>
        </div>

        <div class="profile-tabs">
            <button class="tab-btn <?php echo $activeTab == 'collection' ? 'active' : ''; ?>" onclick="showTab('collection')">📚 Коллекция</button>
            <button class="tab-btn <?php echo $activeTab == 'reviews' ? 'active' : ''; ?>" onclick="showTab('reviews')">✍️ Рецензии</button>
            <button class="tab-btn <?php echo $activeTab == 'subscriptions' ? 'active' : ''; ?>" onclick="showTab('subscriptions')">🔔 Подписки</button>
            <button class="tab-btn <?php echo $activeTab == 'orders' ? 'active' : ''; ?>" onclick="showTab('orders')">📦 Заказы</button>
        </div>

        <!-- КОЛЛЕКЦИЯ -->
        <div id="tab-collection" class="tab-content <?php echo $activeTab == 'collection' ? 'active' : ''; ?>">
            <div class="status-filters">
                <button class="status-filter-btn active" data-status="all" onclick="filterCollection('all', this)">Все</button>
                <button class="status-filter-btn" data-status="bought" onclick="filterCollection('bought', this)">✅ Куплено (<?php echo $statusMap['bought']; ?>)</button>
                <button class="status-filter-btn" data-status="reading" onclick="filterCollection('reading', this)">📖 Читаю (<?php echo $statusMap['reading']; ?>)</button>
                <button class="status-filter-btn" data-status="read" onclick="filterCollection('read', this)">✔️ Прочитано (<?php echo $statusMap['read']; ?>)</button>
                <button class="status-filter-btn" data-status="want" onclick="filterCollection('want', this)">⭐ Хочу (<?php echo $statusMap['want']; ?>)</button>
            </div>
            
            <?php if (empty($userCollection)): ?>
                <div class="empty-state"><p>📚 Ваша коллекция пуста</p><a href="index.php" class="btn-primary">Перейти в каталог</a></div>
            <?php else: ?>
                <div class="collection-grid" id="collectionGrid">
                    <?php foreach ($userCollection as $item): ?>
                        <div class="collection-card" data-status="<?php echo $item['status']; ?>">
                            <img class="collection-image" src="images/<?php echo htmlspecialchars($item['image']); ?>" onerror="this.src='https://placehold.co/400x500/1a1f2e/667eea?text=No'">
                            <div class="collection-body">
                                <div class="collection-meta">
                                    <span><?php echo htmlspecialchars($item['category'] ?? 'Без категории'); ?></span>
                                    <span><?php echo number_format($item['price']); ?> ₸</span>
                                </div>
                                <h4 class="collection-title"><?php echo htmlspecialchars($item['title']); ?></h4>
                                <form method="post">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="comic_id" value="<?php echo $item['id']; ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="bought" <?php echo $item['status'] == 'bought' ? 'selected' : ''; ?>>✅ Куплено</option>
                                        <option value="reading" <?php echo $item['status'] == 'reading' ? 'selected' : ''; ?>>📖 Читаю</option>
                                        <option value="read" <?php echo $item['status'] == 'read' ? 'selected' : ''; ?>>✔️ Прочитано</option>
                                        <option value="want" <?php echo $item['status'] == 'want' ? 'selected' : ''; ?>>⭐ Хочу купить</option>
                                    </select>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- РЕЦЕНЗИИ -->
        <div id="tab-reviews" class="tab-content <?php echo $activeTab == 'reviews' ? 'active' : ''; ?>">
            <h3 style="color: white; margin-bottom: 20px;">✍️ Мои рецензии</h3>
            
            <?php if (!empty($userReviews)): ?>
                <?php foreach ($userReviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <img class="review-comic-image" src="images/<?php echo htmlspecialchars($review['image']); ?>" onerror="this.src='https://placehold.co/50x70/1a1f2e/667eea?text=No'">
                            <div>
                                <div class="review-comic-title"><?php echo htmlspecialchars($review['title']); ?></div>
                                <div class="review-rating"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></div>
                            </div>
                            <span class="review-status status-<?php echo $review['status']; ?>">
                                <?php echo $review['status'] == 'pending' ? '⏳ На модерации' : ($review['status'] == 'approved' ? '✅ Одобрено' : '❌ Отклонено'); ?>
                            </span>
                        </div>
                        <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                        <div style="font-size: 12px; color: #666; margin-top: 10px;">📅 <?php echo date('d.m.Y', strtotime($review['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="review-form">
                <h4 style="color: white; margin-bottom: 15px;">✏️ Написать рецензию</h4>
                <form method="post">
                    <input type="hidden" name="submit_review" value="1">
                    <select name="comic_id" required>
                        <option value="">Выберите комикс из коллекции</option>
                        <?php 
                        $stmt = $pdo->prepare("SELECT c.id, c.title FROM user_collection uc JOIN comics c ON c.id = uc.comic_id WHERE uc.user_id = ? AND uc.status IN ('bought', 'reading', 'read')");
                        $stmt->execute([$userId]);
                        $reviewableComics = $stmt->fetchAll();
                        foreach ($reviewableComics as $comic): ?>
                            <option value="<?php echo $comic['id']; ?>"><?php echo htmlspecialchars($comic['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="star-rating" id="starRating">
                        <span class="star" data-value="1">☆</span>
                        <span class="star" data-value="2">☆</span>
                        <span class="star" data-value="3">☆</span>
                        <span class="star" data-value="4">☆</span>
                        <span class="star" data-value="5">☆</span>
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" value="5">
                    <textarea name="review_text" placeholder="Поделитесь впечатлениями о комиксе..." required></textarea>
                    <button type="submit" class="btn-primary">📤 Отправить на модерацию</button>
                </form>
            </div>
        </div>

        <!-- ПОДПИСКИ -->
        <div id="tab-subscriptions" class="tab-content <?php echo $activeTab == 'subscriptions' ? 'active' : ''; ?>">
            <h3 style="color: white; margin-bottom: 20px;">🔔 Мои подписки на серии</h3>
            
            <?php if (empty($userSubscriptions)): ?>
                <div class="empty-state"><p>🔔 Вы пока не подписаны ни на одну серию</p></div>
            <?php else: ?>
                <div class="series-grid" style="margin-bottom: 40px;">
                    <?php foreach ($userSubscriptions as $sub): ?>
                        <div class="series-card">
                            <div class="series-name">📚 <?php echo htmlspecialchars($sub['name']); ?></div>
                            <div class="series-desc"><?php echo htmlspecialchars($sub['description'] ?? 'Новинки этой серии будут приходить вам на почту'); ?></div>
                            <form method="post">
                                <input type="hidden" name="subscribe_series" value="1">
                                <input type="hidden" name="series_id" value="<?php echo $sub['id']; ?>">
                                <input type="hidden" name="subscribe_action" value="unsubscribe">
                                <button type="submit" class="unsubscribe-btn">🔕 Отписаться</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <h3 style="color: white; margin-bottom: 20px;">📚 Доступные серии для подписки</h3>
            <?php if (empty($allSeries)): ?>
                <div class="empty-state"><p>📚 Серии пока не добавлены</p></div>
            <?php else: ?>
                <div class="series-grid">
                    <?php 
                    $subscribedIds = array_column($userSubscriptions, 'id');
                    foreach ($allSeries as $series): 
                        $isSubscribed = in_array($series['id'], $subscribedIds);
                    ?>
                        <div class="series-card">
                            <div class="series-name">📚 <?php echo htmlspecialchars($series['name']); ?></div>
                            <div class="series-desc"><?php echo htmlspecialchars($series['description'] ?? 'Подпишитесь и получайте уведомления о новых выпусках'); ?></div>
                            <form method="post">
                                <input type="hidden" name="subscribe_series" value="1">
                                <input type="hidden" name="series_id" value="<?php echo $series['id']; ?>">
                                <input type="hidden" name="subscribe_action" value="<?php echo $isSubscribed ? 'unsubscribe' : 'subscribe'; ?>">
                                <button type="submit" class="<?php echo $isSubscribed ? 'unsubscribe-btn' : 'subscribe-btn'; ?>">
                                    <?php echo $isSubscribed ? '🔕 Отписаться' : '🔔 Подписаться'; ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ЗАКАЗЫ -->
        <div id="tab-orders" class="tab-content <?php echo $activeTab == 'orders' ? 'active' : ''; ?>">
            <?php if (empty($recentOrders)): ?>
                <div class="empty-state"><p>📦 У вас пока нет заказов</p><a href="index.php" class="btn-primary">Начать покупки</a></div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($recentOrders as $order): ?>
                        <div class="collection-card" style="justify-content: space-between; display: flex; align-items: center;">
                            <div>
                                <h4 style="color: white;">Заказ #<?php echo $order['id']; ?></h4>
                                <p style="color: #a0a0b0;">📅 <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                                <p style="color: #a0a0b0;">📦 Товаров: <?php echo $order['items_count']; ?></p>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-size: 20px; color: #667eea;"><?php echo number_format($order['total_amount']); ?> ₸</span><br>
                                <span class="status-badge status-bought" style="background: #28a745; color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px;"><?php echo $order['status']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="page-footer">Comic Universe © 2026 — Магазин комиксов</footer>
</div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.classList.add('active');
    window.history.pushState({}, '', '?tab=' + tabName);
}

function filterCollection(status, btn) {
    document.querySelectorAll('.status-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const cards = document.querySelectorAll('.collection-card');
    cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Звёздный рейтинг
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const value = parseInt(this.dataset.value);
            document.getElementById('ratingValue').value = value;
            stars.forEach((s, i) => {
                if (i < value) {
                    s.textContent = '★';
                    s.classList.add('selected');
                } else {
                    s.textContent = '☆';
                    s.classList.remove('selected');
                }
            });
        });
    });
    // Устанавливаем 5 звёзд по умолчанию
    stars.forEach((s, i) => {
        if (i < 5) {
            s.textContent = '★';
            s.classList.add('selected');
        }
    });
});
</script>
</body>
</html>