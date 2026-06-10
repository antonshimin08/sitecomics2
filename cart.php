<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

// Инициализация корзины
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

$cart = &$_SESSION['cart'];

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if ($action === 'add' && $productId > 0) {
        // Добавление товара - увеличиваем количество если уже есть
        if (isset($cart[$productId])) {
            $cart[$productId]++;
        } else {
            $cart[$productId] = 1;
        }
        
        // Проверка лимита 500 товаров
        if (array_sum($cart) > 500) {
            $cart[$productId]--;
            if ($cart[$productId] <= 0) {
                unset($cart[$productId]);
            }
            $_SESSION['error'] = 'Максимум 500 товаров в одном заказе!';
        } else {
            $_SESSION['message'] = 'Товар добавлен в корзину!';
        }
        header('Location: cart.php');
        exit;
    }
    
    if ($action === 'update_quantity' && $productId > 0) {
        $newQty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        $currentTotal = array_sum($cart);
        $oldQty = isset($cart[$productId]) ? $cart[$productId] : 0;
        
        if ($newQty > 0 && $currentTotal - $oldQty + $newQty <= 500) {
            $cart[$productId] = $newQty;
            $_SESSION['message'] = 'Количество обновлено';
        } elseif ($newQty <= 0) {
            unset($cart[$productId]);
            $_SESSION['message'] = 'Товар удалён из корзины';
        } else {
            $_SESSION['error'] = 'Нельзя добавить больше 500 товаров!';
        }
        header('Location: cart.php');
        exit;
    }
    
    if ($action === 'remove' && $productId > 0) {
        unset($cart[$productId]);
        $_SESSION['message'] = 'Товар удалён из корзины';
        header('Location: cart.php');
        exit;
    }
    
    if ($action === 'clear') {
        $cart = array();
        $_SESSION['message'] = 'Корзина очищена';
        header('Location: cart.php');
        exit;
    }
    
    if ($action === 'apply_promo') {
        $code = isset($_POST['promo_code']) ? trim($_POST['promo_code']) : '';
        try {
            $stmt = $pdo->prepare("SELECT * FROM promocodes WHERE code = ? AND expires_at > NOW()");
            $stmt->execute(array($code));
            $promo = $stmt->fetch();
            if ($promo) {
                $_SESSION['promo_code'] = $code;
                $_SESSION['discount_percent'] = $promo['discount_percent'];
                $_SESSION['message'] = 'Промокод применён!';
            } else {
                $_SESSION['error'] = 'Неверный или просроченный промокод';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Ошибка применения промокода';
        }
        header('Location: cart.php');
        exit;
    }
}

$discountPercent = isset($_SESSION['discount_percent']) ? $_SESSION['discount_percent'] : 0;

$productIds = array_keys($cart);
$items = array();
$totalPrice = 0;
$totalAmount = array_sum($cart);

if (!empty($productIds) && $totalAmount <= 500) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $sql = "SELECT c.id, c.title, c.price, c.image, cat.name AS category 
            FROM comics c 
            LEFT JOIN categories cat ON cat.id = c.category_id 
            WHERE c.id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        $qty = isset($cart[$product['id']]) ? $cart[$product['id']] : 0;
        if ($qty > 0) {
            $item = $product;
            $item['quantity'] = $qty;
            $item['subtotal'] = $qty * $product['price'];
            $totalPrice += $item['subtotal'];
            $items[] = $item;
        }
    }
}

$finalPrice = $totalPrice * (100 - $discountPercent) / 100;

// Получаем сообщения
$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['message'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Корзина | Comic Universe</title>
    <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
    <style>
        .cart-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .cart-title { font-size: 28px; color: #fff; margin-bottom: 30px; }
        .cart-table { width: 100%; background: #1a1f2e; border-radius: 12px; overflow: hidden; border-collapse: collapse; }
        .cart-table th, .cart-table td { padding: 15px; text-align: left; border-bottom: 1px solid #2a2f3e; color: #e0e0e0; }
        .cart-table th { background: #2a2f3e; color: #fff; font-weight: 600; }
        .cart-item-image { width: 60px; height: 80px; object-fit: cover; border-radius: 6px; }
        .quantity-input { width: 60px; padding: 6px; background: #2a2f3e; border: 1px solid #3a3f4e; color: white; border-radius: 4px; text-align: center; }
        .btn-update { background: #667eea; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-left: 5px; }
        .btn-update:hover { background: #5a67d8; }
        .cart-summary { margin-top: 20px; padding: 20px; background: #1a1f2e; border-radius: 12px; text-align: right; }
        .cart-total { font-size: 24px; color: #667eea; font-weight: bold; }
        .cart-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 20px; flex-wrap: wrap; }
        .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .btn { padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-block; cursor: pointer; border: none; font-size: 14px; }
        .btn-primary { background: #28a745; color: white; }
        .btn-primary:hover { background: #218838; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .promo-form { display: flex; gap: 10px; margin-bottom: 20px; justify-content: flex-end; }
        .promo-input { padding: 10px; background: #2a2f3e; border: 1px solid #3a3f4e; color: white; border-radius: 6px; width: 200px; }
        .empty-cart { text-align: center; padding: 60px; background: #1a1f2e; border-radius: 12px; }
        .empty-cart p { color: #a0a0b0; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="page">
    <header class="site-header">
        <div class="site-brand">
            <div class="site-brand__logo">CU</div>
            <div>
                <h1 class="site-brand__title">Comic Universe</h1>
                <p class="site-brand__hint">Коллекция комиксов</p>
            </div>
        </div>
        <nav class="site-nav">
            <a class="site-nav__link" href="index.php">Каталог</a>
            <a class="site-nav__link" href="series.php">Серии</a>
            <a class="site-nav__link" href="wishlist.php">Вишлист</a>
            <a class="site-nav__link" href="profile.php">Профиль</a>
            <a class="site-nav__button" href="cart.php">Корзина (<?php echo $totalAmount; ?>)</a>
            <?php if (isLoggedIn()): ?>
                <span class="site-nav__link">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a class="site-nav__button" href="logout.php" style="background:#dc3545;">Выход</a>
            <?php else: ?>
                <a class="site-nav__button" href="login.php">Вход</a>
                <a class="site-nav__button" href="register.php" style="background:#28a745;">Регистрация</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="cart-container">
        <h1 class="cart-title">🛒 Корзина</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (empty($items)): ?>
            <div class="empty-cart">
                <p>🛒 Ваша корзина пуста</p>
                <a href="index.php" class="btn btn-primary">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Категория</th>
                        <th>Цена</th>
                        <th>Кол-во</th>
                        <th>Итого</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div style="display:flex; gap:15px; align-items:center;">
                                <img class="cart-item-image" src="images/<?php echo htmlspecialchars($item['image']); ?>" onerror="this.src='https://placehold.co/60x80/1a1f2e/667eea?text=No'">
                                <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                        <td><?php echo number_format($item['price']); ?> ₸</td>
                        <td>
                            <form method="post" style="display:flex; gap:5px; align-items:center;">
                                <input type="hidden" name="action" value="update_quantity">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99" class="quantity-input">
                                <button type="submit" class="btn-update">Обн.</button>
                            </form>
                        </td>
                        <td><?php echo number_format($item['subtotal']); ?> ₸</td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="padding:6px 12px;">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="promo-form">
                <form method="post" style="display:flex; gap:10px;">
                    <input type="hidden" name="action" value="apply_promo">
                    <input type="text" name="promo_code" placeholder="Введите промокод" class="promo-input">
                    <button type="submit" class="btn btn-secondary">Применить</button>
                </form>
            </div>
            
            <div class="cart-summary">
                <?php if ($discountPercent > 0): ?>
                    <p style="color:#28a745;">Скидка: <?php echo $discountPercent; ?>%</p>
                <?php endif; ?>
                <div class="cart-total">Итого: <?php echo number_format($finalPrice); ?> ₸</div>
                <?php if ($discountPercent > 0): ?>
                    <small style="color:#888;">(было <?php echo number_format($totalPrice); ?> ₸)</small>
                <?php endif; ?>
            </div>
            
            <div class="cart-actions">
                <form method="post">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-secondary">Очистить корзину</button>
                </form>
                <a href="checkout.php" class="btn btn-primary">Оформить заказ</a>
                <a href="index.php" class="btn btn-secondary">Продолжить покупки</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="page-footer">
        Comic Universe © 2026 — Магазин комиксов
    </footer>
</div>
</body>
</html>