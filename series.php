<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

$seriesId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($seriesId > 0) {
    // Страница конкретной серии
    $stmt = $pdo->prepare("SELECT * FROM series WHERE id = ?");
    $stmt->execute([$seriesId]);
    $series = $stmt->fetch();
    if (!$series) { 
        header('Location: series.php'); 
        exit; 
    }
    
    // Получаем выпуски серии с дополнительной информацией
    $issuesStmt = $pdo->prepare("
        SELECT c.*, cat.name as category 
        FROM comics c 
        LEFT JOIN categories cat ON cat.id = c.category_id 
        WHERE c.series_id = ? 
        ORDER BY c.issue_number ASC
    ");
    $issuesStmt->execute([$seriesId]);
    $issues = $issuesStmt->fetchAll();
    
    // Статистика серии
    $totalIssues = count($issues);
    $totalPrice = array_sum(array_column($issues, 'price'));
    $avgPrice = $totalIssues > 0 ? round($totalPrice / $totalIssues) : 0;
    $inStock = count(array_filter($issues, function($i) { return $i['stock_quantity'] > 0; }));
    
    // Проверяем подписку пользователя
    $isSubscribed = false;
    if (isLoggedIn()) {
        $subStmt = $pdo->prepare("SELECT 1 FROM series_subscriptions WHERE user_id = ? AND series_id = ?");
        $subStmt->execute([$_SESSION['user_id'], $seriesId]);
        $isSubscribed = $subStmt->fetchColumn() > 0;
    }
    
    // Обработка подписки
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe_action'])) {
        if (!isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
        $userId = $_SESSION['user_id'];
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
        header('Location: series.php?id=' . $seriesId);
        exit;
    }
    
    $message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
    unset($_SESSION['message']);
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title><?php echo htmlspecialchars($series['name']); ?> | Серия комиксов</title>
        <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
        <style>
            .series-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
            .back-link { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; }
            .series-header { background: linear-gradient(135deg, #1a1f2e 0%, #2a2f3e 100%); border-radius: 16px; padding: 30px; margin-bottom: 30px; }
            .series-title { color: white; font-size: 32px; margin-bottom: 10px; }
            .series-desc { color: #a0a0b0; font-size: 16px; margin-bottom: 20px; line-height: 1.5; }
            .series-stats { display: flex; gap: 30px; flex-wrap: wrap; margin: 20px 0; }
            .stat { background: #1a1f2e; padding: 15px 25px; border-radius: 12px; text-align: center; }
            .stat-value { font-size: 28px; font-weight: bold; color: #667eea; }
            .stat-label { color: #a0a0b0; font-size: 12px; margin-top: 5px; }
            .subscribe-btn { background: #28a745; color: white; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-size: 16px; margin-top: 15px; }
            .unsubscribe-btn { background: #dc3545; color: white; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-size: 16px; margin-top: 15px; }
            .issues-section { background: #1a1f2e; border-radius: 16px; padding: 30px; margin-top: 20px; }
            .issues-title { color: white; font-size: 24px; margin-bottom: 20px; }
            .issues-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
            .issue-card { background: #2a2f3e; border-radius: 12px; padding: 20px; transition: transform 0.2s; }
            .issue-card:hover { transform: translateY(-4px); }
            .issue-number { display: inline-block; background: #667eea; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-bottom: 10px; }
            .issue-title { color: white; font-size: 18px; margin-bottom: 8px; }
            .issue-meta { color: #a0a0b0; font-size: 13px; margin: 8px 0; }
            .issue-price { font-size: 20px; color: #667eea; font-weight: bold; margin: 10px 0; }
            .issue-stock { font-size: 12px; margin-top: 5px; }
            .in-stock { color: #28a745; }
            .out-of-stock { color: #dc3545; }
            .btn-sm { padding: 8px 16px; font-size: 14px; margin-top: 10px; display: inline-block; }
            .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
            .empty-state { text-align: center; padding: 40px; color: #a0a0b0; }
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
                    <span class="site-nav__link">👤 <?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                    <a class="site-nav__button" href="logout.php" style="background: #dc3545;">Выход</a>
                <?php else: ?>
                    <a class="site-nav__button" href="login.php">Вход</a>
                    <a class="site-nav__button" href="register.php" style="background: #28a745;">Регистрация</a>
                <?php endif; ?>
            </nav>
        </header>

        <div class="series-container">
            <a href="series.php" class="back-link">← Ко всем сериям</a>
            
            <?php if ($message): ?>
                <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="series-header">
                <h1 class="series-title">📚 <?php echo htmlspecialchars($series['name']); ?></h1>
                <p class="series-desc"><?php echo htmlspecialchars($series['description'] ?? 'Описание серии скоро появится...'); ?></p>
                
                <div class="series-stats">
                    <div class="stat"><div class="stat-value"><?php echo $totalIssues; ?></div><div class="stat-label">Выпусков</div></div>
                    <div class="stat"><div class="stat-value"><?php echo $inStock; ?></div><div class="stat-label">В наличии</div></div>
                    <div class="stat"><div class="stat-value"><?php echo number_format($avgPrice); ?> ₸</div><div class="stat-label">Средняя цена</div></div>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <form method="post">
                        <input type="hidden" name="subscribe_action" value="<?php echo $isSubscribed ? 'unsubscribe' : 'subscribe'; ?>">
                        <button type="submit" class="<?php echo $isSubscribed ? 'unsubscribe-btn' : 'subscribe-btn'; ?>">
                            <?php echo $isSubscribed ? '🔕 Отписаться от серии' : '🔔 Подписаться на серию'; ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="subscribe-btn" style="display: inline-block; text-decoration: none;">🔔 Войдите, чтобы подписаться</a>
                <?php endif; ?>
            </div>
            
            <div class="issues-section">
                <h2 class="issues-title">📖 Выпуски серии</h2>
                <?php if (empty($issues)): ?>
                    <div class="empty-state">Выпусков пока нет. Скоро появятся!</div>
                <?php else: ?>
                    <div class="issues-grid">
                        <?php foreach ($issues as $issue): ?>
                            <div class="issue-card">
                                <span class="issue-number">Выпуск #<?php echo $issue['issue_number']; ?></span>
                                <h3 class="issue-title"><?php echo htmlspecialchars($issue['title']); ?></h3>
                                <div class="issue-meta">📚 <?php echo htmlspecialchars($issue['category'] ?? 'Без категории'); ?></div>
                                <div class="issue-meta">👤 <?php echo htmlspecialchars($issue['author'] ?? 'Автор не указан'); ?></div>
                                <div class="issue-price"><?php echo number_format($issue['price']); ?> ₸</div>
                                <div class="issue-stock <?php echo $issue['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                    <?php if ($issue['stock_quantity'] > 0): ?>
                                        ✅ В наличии: <?php echo $issue['stock_quantity']; ?> шт.
                                    <?php else: ?>
                                        ❌ Нет в наличии
                                    <?php endif; ?>
                                </div>
                                <a href="comic.php?id=<?php echo $issue['id']; ?>" class="btn btn-primary btn-sm">Подробнее</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <footer class="page-footer">Comic Universe © 2026 — Магазин комиксов</footer>
    </div>
    </body>
    </html>
    <?php
} else {
    // Страница со списком всех серий
    $series = $pdo->query("
        SELECT s.*, COUNT(c.id) as issues_count, SUM(c.stock_quantity) as total_stock, AVG(c.price) as avg_price
        FROM series s 
        LEFT JOIN comics c ON c.series_id = s.id 
        GROUP BY s.id 
        ORDER BY s.name
    ")->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Серии комиксов | Comic Universe</title>
        <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
        <style>
            .series-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
            .page-title { color: white; font-size: 32px; margin-bottom: 10px; }
            .page-subtitle { color: #a0a0b0; margin-bottom: 30px; }
            .series-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
            .series-card { background: #1a1f2e; border-radius: 16px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
            .series-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
            .series-header { background: linear-gradient(135deg, #2a2f3e 0%, #1a1f2e 100%); padding: 20px; border-bottom: 1px solid #3a3f4e; }
            .series-name { color: white; font-size: 22px; margin-bottom: 8px; }
            .series-desc { color: #a0a0b0; font-size: 13px; line-height: 1.4; }
            .series-body { padding: 20px; }
            .series-stats { display: flex; justify-content: space-between; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
            .stat-item { text-align: center; flex: 1; }
            .stat-number { font-size: 20px; font-weight: bold; color: #667eea; }
            .stat-label { font-size: 11px; color: #a0a0b0; }
            .series-footer { padding: 15px 20px; border-top: 1px solid #2a2f3e; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
            .btn-details { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; transition: background 0.2s; }
            .btn-details:hover { background: #5a67d8; }
            .btn-subscribe { background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
            .empty-state { text-align: center; padding: 60px; background: #1a1f2e; border-radius: 16px; color: #a0a0b0; }
            .back-link { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; }
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
                    <span class="site-nav__link">👤 <?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                    <a class="site-nav__button" href="logout.php" style="background: #dc3545;">Выход</a>
                <?php else: ?>
                    <a class="site-nav__button" href="login.php">Вход</a>
                    <a class="site-nav__button" href="register.php" style="background: #28a745;">Регистрация</a>
                <?php endif; ?>
            </nav>
        </header>

        <div class="series-container">
            <a href="index.php" class="back-link">← На главную</a>
            <h1 class="page-title">📚 Серии комиксов</h1>
            <p class="page-subtitle">Коллекционные серии, полные саги и законченные истории</p>
            
            <?php if (empty($series)): ?>
                <div class="empty-state">
                    <p>😕 Серии пока не добавлены</p>
                    <p>Скоро здесь появятся новые серии комиксов!</p>
                </div>
            <?php else: ?>
                <div class="series-grid">
                    <?php foreach ($series as $serie): ?>
                        <?php
                        $subscribed = false;
                        if (isLoggedIn()) {
                            $subStmt = $pdo->prepare("SELECT 1 FROM series_subscriptions WHERE user_id = ? AND series_id = ?");
                            $subStmt->execute([$_SESSION['user_id'], $serie['id']]);
                            $subscribed = $subStmt->fetchColumn() > 0;
                        }
                        ?>
                        <div class="series-card">
                            <div class="series-header">
                                <h3 class="series-name">📖 <?php echo htmlspecialchars($serie['name']); ?></h3>
                                <p class="series-desc"><?php echo htmlspecialchars(substr($serie['description'] ?? 'Нет описания', 0, 100)) . (strlen($serie['description'] ?? '') > 100 ? '...' : ''); ?></p>
                            </div>
                            <div class="series-body">
                                <div class="series-stats">
                                    <div class="stat-item"><div class="stat-number"><?php echo $serie['issues_count'] ?? 0; ?></div><div class="stat-label">Выпусков</div></div>
                                    <div class="stat-item"><div class="stat-number"><?php echo $serie['total_stock'] ?? 0; ?></div><div class="stat-label">В наличии</div></div>
                                    <div class="stat-item"><div class="stat-number"><?php echo $serie['avg_price'] ? number_format($serie['avg_price']) . ' ₸' : '—'; ?></div><div class="stat-label">Средняя цена</div></div>
                                </div>
                            </div>
                            <div class="series-footer">
                                <a href="series.php?id=<?php echo $serie['id']; ?>" class="btn-details">📖 Подробнее о серии</a>
                                <?php if (isLoggedIn()): ?>
                                    <?php if ($subscribed): ?>
                                        <span style="color:#28a745; font-size:12px;">✅ Вы подписаны</span>
                                    <?php else: ?>
                                        <form method="post" action="subscribe.php" style="display: inline;">
                                            <input type="hidden" name="series_id" value="<?php echo $serie['id']; ?>">
                                            <input type="hidden" name="action" value="subscribe">
                                            <button type="submit" class="btn-subscribe">🔔 Подписаться</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php" class="btn-subscribe" style="background:#6c757d;">🔔 Войдите</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="page-footer">Comic Universe © 2026 — Магазин комиксов</footer>
    </div>
    </body>
    </html>
    <?php
}
?>