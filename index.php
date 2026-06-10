<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

$cartCount = array_sum($_SESSION['cart'] ?? []);

$search = trim((string)($_GET['search'] ?? ''));
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$genreId = isset($_GET['genre']) ? (int)$_GET['genre'] : 0;
$publisherId = isset($_GET['publisher']) ? (int)$_GET['publisher'] : 0;
$priceMin = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
$priceMax = isset($_GET['price_max']) && (int)$_GET['price_max'] > 0 ? (int)$_GET['price_max'] : 100000;
$inStock = isset($_GET['in_stock']) ? 1 : 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 8;
$offset = ($page - 1) * $limit;

$whereSql = "";
$params = array();

if ($search !== '') {
    $whereSql .= " AND (c.title LIKE ? OR c.author LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryId > 0) {
    $whereSql .= " AND c.category_id = ?";
    $params[] = $categoryId;
}

if ($genreId > 0) {
    $whereSql .= " AND c.genre_id = ?";
    $params[] = $genreId;
}

if ($publisherId > 0) {
    $whereSql .= " AND c.publisher_id = ?";
    $params[] = $publisherId;
}

if ($priceMin > 0) {
    $whereSql .= " AND c.price >= ?";
    $params[] = $priceMin;
}

if ($priceMax < 100000 && $priceMax > 0) {
    $whereSql .= " AND c.price <= ?";
    $params[] = $priceMax;
}

if ($inStock) {
    $whereSql .= " AND c.stock_quantity > 0";
}

$countSql = "SELECT COUNT(*) FROM comics c WHERE 1=1 $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $limit));

$sql = "SELECT c.*, cat.name AS category 
        FROM comics c 
        LEFT JOIN categories cat ON cat.id = c.category_id 
        WHERE 1=1 $whereSql 
        ORDER BY c.id ASC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comics = $stmt->fetchAll();

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

$genres = array();
try {
    $genres = $pdo->query('SELECT id, name FROM genres ORDER BY name')->fetchAll();
} catch (Exception $e) {
    $genres = array();
}

$publishers = array();
try {
    $publishers = $pdo->query('SELECT id, name FROM publishers ORDER BY name')->fetchAll();
} catch (Exception $e) {
    $publishers = array();
}

$preorderNotification = false;
$preorderSeries = array();
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    try {
        $preorderCheck = $pdo->prepare("SELECT DISTINCT s.id, s.name, c.id as comic_id, c.title FROM series_subscriptions ss JOIN series s ON s.id = ss.series_id JOIN comics c ON c.series_id = s.id WHERE ss.user_id = ? AND c.preorder = 1 LIMIT 3");
        $preorderCheck->execute(array($userId));
        $preorderSeries = $preorderCheck->fetchAll();
        $preorderNotification = count($preorderSeries) > 0;
    } catch (Exception $e) {
        $preorderNotification = false;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Comic Universe | Магазин комиксов</title>
    <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
    <style>
        .notification-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .badge-preorder { background: #ff9800; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; display: inline-block; margin-right: 5px; }
        .badge-outofstock { background: #dc3545; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; display: inline-block; }
        .badge-stock { background: #28a745; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; display: inline-block; }
        .badge-lowstock { background: #ffc107; color: #333; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; display: inline-block; }
        .filter-group { margin-bottom: 15px; }
        .filter-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .price-range { display: flex; gap: 10px; align-items: center; }
        .price-range input { width: 80px; padding: 5px; }
        .action-buttons { display: flex; gap: 8px; margin-top: 10px; align-items: center; }
        .btn-outline { background: transparent; border: 2px solid #667eea; color: #667eea; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
        .btn-outline:hover { background: #667eea; color: white; }
        .filter-form input, .filter-form select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-form button { margin-top: 10px; width: 100%; }
        .active-filter { background: #667eea; color: white; }
        .action-buttons {
            margin-top: auto;
        }
        .card__body {
            display: flex;
            flex-direction: column;
        }
        .stock-info {
            margin: 5px 0;
            font-size: 0.75rem;
        }
        .btn-preorder {
            background: #ff9800;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
            width: 100%;
        }
        .btn-preorder:hover {
            background: #e68900;
        }
        .btn-disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        /* Стили для админ-меню */
        .admin-dropdown {
            position: relative;
            display: inline-block;
        }
        .admin-btn {
            cursor: pointer;
        }
        .admin-dropdown-content {
            display: none;
            position: absolute;
            background: #1a1f2e;
            min-width: 220px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            border-radius: 8px;
            z-index: 1000;
            border: 1px solid #2a2f3e;
        }
        .admin-dropdown-content a {
            color: #e0e0e0;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            transition: all 0.2s;
        }
        .admin-dropdown-content a:hover {
            background: #667eea;
            color: white;
        }
        .admin-dropdown-content hr {
            margin: 5px 0;
            border-color: #2a2f3e;
        }
        .admin-dropdown:hover .admin-dropdown-content {
            display: block;
        }
    </style>
</head>
<body>
<div class="page">
    <header class="site-header">
        <div class="site-brand">
            <div class="site-brand__logo">CU</div>
            <div>
                <h1 class="site-brand__title">Comic Universe</h1>
                <p class="site-brand__hint">Коллекция комиксов и коллекционных изданий.</p>
            </div>
        </div>
        <nav class="site-nav">
            <a class="site-nav__link" href="index.php">Каталог</a>
            <a class="site-nav__link" href="series.php">Серии</a>
            <a class="site-nav__link" href="wishlist.php">Вишлист</a>
            <a class="site-nav__link" href="profile.php">Мой профиль</a>
            
            <?php if (isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="admin-dropdown">
                    <a class="site-nav__link admin-btn" href="javascript:void(0)">⚙️ Админ панель ▼</a>
                    <div class="admin-dropdown-content">
                        <a href="admin_reviews.php">📝 Модерация рецензий</a>
                        <a href="admin_promocodes.php">🎫 Управление промокодами</a>
                        <a href="admin_orders.php">📦 Заказы</a>
                        <a href="admin_comics.php">📚 Управление комиксами</a>
                        <hr>
                        <a href="admin_users.php">👥 Пользователи</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <a class="site-nav__button" href="cart.php">Корзина (<?php echo $cartCount; ?>)</a>
            <?php if (isLoggedIn()): ?>
                <span class="site-nav__link">👤 <?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                <a class="site-nav__button" href="logout.php" style="background: #dc3545;">Выход</a>
            <?php else: ?>
                <a class="site-nav__button" href="login.php">Вход</a>
                <a class="site-nav__button" href="register.php" style="background: #28a745;">Регистрация</a>
            <?php endif; ?>
        </nav>
    </header>

    <section class="hero">
        <h2 class="hero__title">Добро пожаловать в Comic Universe</h2>
        <p class="hero__subtitle">Выбирай комиксы, фильтруй по категориям и добавляй товары в корзину.</p>
    </section>

    <?php if ($preorderNotification && !empty($preorderSeries)): ?>
    <div class="notification-banner" id="preorderBanner">
        <div class="notification-banner__content">
            🎉 Новые предзаказы! Доступны: <?php echo implode(', ', array_column($preorderSeries, 'title')); ?>
        </div>
        <button onclick="document.getElementById('preorderBanner').style.display='none'" style="background:none;border:none;color:white;font-size:20px;cursor:pointer;">✕</button>
    </div>
    <?php endif; ?>

    <div class="layout">
        <aside class="filters">
            <h2>Фильтры</h2>
            <form method="get" action="index.php" class="filter-form">
                <div class="filter-group">
                    <label>Поиск по названию или автору</label>
                    <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Введите название или автора">
                </div>
                
                <div class="filter-group">
                    <label>Категория</label>
                    <select name="category">
                        <option value="0">Все категории</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php if($categoryId == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!empty($genres)): ?>
                <div class="filter-group">
                    <label>Жанр</label>
                    <select name="genre">
                        <option value="0">Все жанры</option>
                        <?php foreach ($genres as $g): ?>
                        <option value="<?php echo $g['id']; ?>" <?php if($genreId == $g['id']) echo 'selected'; ?>><?php echo htmlspecialchars($g['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if (!empty($publishers)): ?>
                <div class="filter-group">
                    <label>Издательство</label>
                    <select name="publisher">
                        <option value="0">Все издательства</option>
                        <?php foreach ($publishers as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php if($publisherId == $p['id']) echo 'selected'; ?>><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="filter-group">
                    <label>Цена (₸)</label>
                    <div class="price-range">
                        <input type="number" name="price_min" value="<?php echo $priceMin ?: ''; ?>" placeholder="от" min="0">
                        <span>-</span>
                        <input type="number" name="price_max" value="<?php echo ($priceMax < 100000 && $priceMax > 0) ? $priceMax : ''; ?>" placeholder="до" min="0">
                    </div>
                </div>

                <div class="filter-group">
                    <label>
                        <input type="checkbox" name="in_stock" value="1" <?php if($inStock) echo 'checked'; ?>>
                        Только в наличии
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; margin-bottom:10px;">Применить</button>
                <button type="button" onclick="window.location.href='index.php'" style="width:100%; background:#6c757d; color:white; border:none; padding:10px; border-radius:6px; cursor:pointer;">Сбросить</button>
            </form>
        </aside>

        <section style="width:100%;">
            <div class="product-grid">
                <?php if (count($comics) === 0): ?>
                    <div class="page-card" style="grid-column:1/-1; text-align:center; padding:40px;">
                        <p>😕 По вашему запросу ничего не найдено.</p>
                        <p>Попробуйте изменить параметры поиска или нажмите "Сбросить".</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($comics as $comic): ?>
                <article class="card">
                    <img class="card__image" 
                         src="images/<?php echo htmlspecialchars($comic['image']); ?>" 
                         alt="<?php echo htmlspecialchars($comic['title']); ?>" 
                         onerror="this.src='https://placehold.co/400x500/1a1f2e/667eea?text='+encodeURIComponent('<?php echo addslashes($comic['title']); ?>')">
                    <div class="card__body">
                        <div class="card__meta">
                            <span><?php echo htmlspecialchars($comic['category'] ?? 'Без категории'); ?></span>
                            <span><?php echo number_format($comic['price'], 0, '.', ' '); ?> ₸</span>
                        </div>
                        
                        <div style="margin: 5px 0;">
                            <?php if (isset($comic['preorder']) && $comic['preorder'] == 1): ?>
                                <span class="badge-preorder">⚡ ПРЕДЗАКАЗ</span>
                            <?php else: ?>
                                <?php 
                                $stock = $comic['stock_quantity'] ?? 0;
                                if ($stock <= 0): ?>
                                    <span class="badge-outofstock">❌ НЕТ В НАЛИЧИИ</span>
                                <?php elseif ($stock < 5): ?>
                                    <span class="badge-lowstock">⚠️ ОСТАЛОСЬ <?php echo $stock; ?> шт.</span>
                                <?php else: ?>
                                    <span class="badge-stock">✅ В НАЛИЧИИ <?php echo $stock; ?> шт.</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="card__title">
                            <a href="comic.php?id=<?php echo $comic['id']; ?>" style="color:inherit; text-decoration:none;">
                                <?php echo htmlspecialchars($comic['title']); ?>
                            </a>
                        </h3>
                        
                        <div class="card__author" style="font-size:0.85rem; color:#666;">
                            Автор: <?php echo htmlspecialchars($comic['author'] ?? 'Не указан'); ?>
                        </div>
                        
                        <div class="stock-info" style="font-size:0.75rem; color:#888;">
                            <?php if (isset($comic['preorder']) && $comic['preorder'] == 1): ?>
                                📅 Выход: скоро
                            <?php else: ?>
                                📦 В наличии: <?php echo $comic['stock_quantity'] ?? 0; ?> экз.
                            <?php endif; ?>
                        </div>
                        
                        <div class="action-buttons">
                            <?php 
                            $hasStock = (isset($comic['stock_quantity']) && $comic['stock_quantity'] > 0);
                            $isPreorder = (isset($comic['preorder']) && $comic['preorder'] == 1);
                            ?>
                            
                            <?php if ($isPreorder): ?>
                                <form method="post" action="cart.php" style="flex:1;">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $comic['id']; ?>">
                                    <button type="submit" class="btn-preorder" style="width:100%;">
                                        📖 ПРЕДЗАКАЗ
                                    </button>
                                </form>
                            <?php elseif ($hasStock): ?>
                                <form method="post" action="cart.php" style="flex:1;">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $comic['id']; ?>">
                                    <button type="submit" class="btn btn-primary" style="width:100%;">
                                        🛒 В КОРЗИНУ
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-primary btn-disabled" disabled style="width:100%;">
                                    ❌ НЕТ В НАЛИЧИИ
                                </button>
                            <?php endif; ?>
                            
                            <form method="post" action="wishlist.php" style="display:inline-block;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $comic['id']; ?>">
                                <button type="submit" class="btn-outline" style="padding:8px 12px;" title="Добавить в избранное">
                                    ♥️
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="page-card">
                <div class="cart-actions">
                    <?php for ($i = 1; $i <= $totalPages; $i++): 
                        $query = array();
                        if ($search) $query['search'] = $search;
                        if ($categoryId) $query['category'] = $categoryId;
                        if ($genreId) $query['genre'] = $genreId;
                        if ($publisherId) $query['publisher'] = $publisherId;
                        if ($priceMin) $query['price_min'] = $priceMin;
                        if ($priceMax > 0 && $priceMax < 100000) $query['price_max'] = $priceMax;
                        if ($inStock) $query['in_stock'] = $inStock;
                        $query['page'] = $i;
                    ?>
                        <a class="btn btn-secondary <?php if($i == $page) echo 'active'; ?>" 
                           href="?<?php echo htmlspecialchars(http_build_query($query)); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <footer class="page-footer">
        Comic Universe © 2026 — Магазин комиксов.
    </footer>
</div>

<script>
setTimeout(function() {
    var banner = document.getElementById('preorderBanner');
    if (banner) banner.style.display = 'none';
}, 5000);
</script>
</body>
</html>