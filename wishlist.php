<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Обработка добавления/удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)$_POST['product_id'];
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $countStmt->execute([$userId]);
        $wishlistCount = $countStmt->fetchColumn();
        
        if ($wishlistCount < 200) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, comic_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $productId]);
            $_SESSION['message'] = 'Товар добавлен в избранное!';
        } else {
            $_SESSION['error'] = 'Вишлист заполнен (максимум 200 позиций)';
        }
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND comic_id = ?");
        $stmt->execute([$userId, $productId]);
        $_SESSION['message'] = 'Товар удалён из избранного';
    }
    
    header('Location: wishlist.php');
    exit;
}

// Обработка добавления в корзину через GET (с возможностью указать количество)
if (isset($_GET['action']) && $_GET['action'] === 'add_to_cart' && isset($_GET['product_id'])) {
    $productId = (int)$_GET['product_id'];
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Проверка лимита 500 товаров
    $currentTotal = array_sum($_SESSION['cart']);
    if ($currentTotal + $quantity <= 500) {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
        $_SESSION['message'] = 'Товар добавлен в корзину!';
    } else {
        $_SESSION['error'] = 'Нельзя добавить больше 500 товаров в один заказ!';
    }
    
    header('Location: cart.php');
    exit;
}

// Получаем вишлист с полной информацией о комиксах
$stmt = $pdo->prepare("
    SELECT c.*, w.created_at, cat.name AS category 
    FROM wishlist w 
    JOIN comics c ON c.id = w.comic_id 
    LEFT JOIN categories cat ON cat.id = c.category_id
    WHERE w.user_id = ? 
    ORDER BY w.created_at DESC
");
$stmt->execute([$userId]);
$wishlist = $stmt->fetchAll();
$wishlistCount = count($wishlist);

$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мой вишлист | Comic Universe</title>
    <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
    <style>
        .wishlist-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .wishlist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .wishlist-title {
            font-size: 28px;
            color: #fff;
        }
        .wishlist-count {
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        .wishlist-card {
            background: #1a1f2e;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .wishlist-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .wishlist-card__image {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        .wishlist-card__body {
            padding: 16px;
        }
        .wishlist-card__category {
            font-size: 0.75rem;
            color: #a0a0b0;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .wishlist-card__title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .wishlist-card__author {
            font-size: 0.85rem;
            color: #a0a0b0;
            margin-bottom: 12px;
        }
        .wishlist-card__price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .quantity-control label {
            color: #a0a0b0;
            font-size: 0.85rem;
        }
        .quantity-input {
            width: 60px;
            padding: 6px;
            border-radius: 4px;
            border: 1px solid #3a3f4e;
            background: #2a2f3e;
            color: white;
            text-align: center;
        }
        .wishlist-card__actions {
            display: flex;
            gap: 10px;
        }
        .btn-buy {
            flex: 1;
            background: #28a745;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        .btn-buy:hover {
            background: #218838;
        }
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .btn-remove:hover {
            background: #c82333;
        }
        .empty-wishlist {
            text-align: center;
            padding: 60px 20px;
            background: #1a1f2e;
            border-radius: 12px;
        }
        .empty-wishlist p {
            color: #a0a0b0;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        .btn-catalog {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-catalog:hover {
            background: #5a67d8;
        }
        .badge-preorder {
            background: #ff9800;
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            display: inline-block;
            margin-bottom: 8px;
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

    <div class="wishlist-container">
        <div class="wishlist-header">
            <h1 class="wishlist-title">❤️ Мой вишлист</h1>
            <span class="wishlist-count"><?php echo $wishlistCount; ?> / 200 товаров</span>
        </div>

        <?php if ($message): ?>
            <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($wishlistCount === 0): ?>
            <div class="empty-wishlist">
                <p>😕 Ваш вишлист пуст</p>
                <p>Добавляйте понравившиеся комиксы в избранное, и они появятся здесь</p>
                <a href="index.php" class="btn-catalog">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist as $item): ?>
                    <div class="wishlist-card">
                        <img class="wishlist-card__image" 
                             src="images/<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                             onerror="this.src='https://placehold.co/400x500/1a1f2e/667eea?text='+encodeURIComponent('<?php echo addslashes($item['title']); ?>')">
                        <div class="wishlist-card__body">
                            <div class="wishlist-card__category">
                                <?php echo htmlspecialchars($item['category'] ?? 'Без категории'); ?>
                            </div>
                            
                            <?php if (isset($item['preorder']) && $item['preorder'] == 1): ?>
                                <div class="badge-preorder">⚡ ПРЕДЗАКАЗ</div>
                            <?php endif; ?>
                            
                            <h3 class="wishlist-card__title">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </h3>
                            
                            <div class="wishlist-card__author">
                                Автор: <?php echo htmlspecialchars($item['author'] ?? 'Не указан'); ?>
                            </div>
                            
                            <div class="wishlist-card__price">
                                <?php echo number_format($item['price'], 0, '.', ' '); ?> ₸
                            </div>
                            
                            <!-- Выбор количества -->
                            <div class="quantity-control">
                                <label>Количество:</label>
                                <input type="number" id="qty_<?php echo $item['id']; ?>" class="quantity-input" value="1" min="1" max="99">
                            </div>
                            
                            <div class="wishlist-card__actions">
                                <button onclick="addToCart(<?php echo $item['id']; ?>)" class="btn-buy">
                                    🛒 Купить
                                </button>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="btn-remove" title="Удалить из вишлиста">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="page-footer" style="margin-top: 40px;">
        Comic Universe © 2026 — Магазин комиксов.
    </footer>
</div>

<script>
function addToCart(productId) {
    var quantityInput = document.getElementById('qty_' + productId);
    var quantity = quantityInput ? quantityInput.value : 1;
    window.location.href = 'wishlist.php?action=add_to_cart&product_id=' + productId + '&quantity=' + quantity;
}
</script>
</body>
</html>