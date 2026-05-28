<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

$cart = &$_SESSION['cart'];
if (!is_array($cart)) {
    $cart = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($action === 'add' && $productId > 0) {
        $cart[$productId] = ($cart[$productId] ?? 0) + 1;
    }

    if ($action === 'remove' && $productId > 0) {
        unset($cart[$productId]);
    }

    if ($action === 'clear') {
        $cart = [];
    }

    header('Location: cart.php');
    exit;
}

$productIds = array_keys($cart);
$items = [];
$totalPrice = 0;
$totalAmount = array_sum($cart);

if (count($productIds) > 0) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $sql = "SELECT c.id, c.title, c.price, c.image, cat.name AS category FROM comics c JOIN categories cat ON cat.id = c.category_id WHERE c.id IN ($placeholders) ORDER BY c.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($productIds);
    $items = $stmt->fetchAll();

    foreach ($items as &$item) {
        $item['quantity'] = $cart[$item['id']] ?? 0;
        $item['subtotal'] = $item['quantity'] * $item['price'];
        $totalPrice += $item['subtotal'];
    }
    unset($item);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Корзина — Comic Universe</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="page">
    <header class="site-header">
        <div class="site-brand">
            <div class="site-brand__logo">CU</div>
            <div>
                <h1 class="site-brand__title">Comic Universe</h1>
                <p class="site-brand__hint">Твоя корзина комиксов и мерча.</p>
            </div>
        </div>
        <nav class="site-nav">
            <a class="site-nav__link" href="index.php">Каталог</a>
            <a class="site-nav__link" href="about.php">О магазине</a>
            <a class="site-nav__button" href="cart.php">Корзина (<?= $totalAmount ?>)</a>
            <?php if (isLoggedIn()): ?>
                <span class="site-nav__link">👤 <?= htmlspecialchars(getCurrentUsername(), ENT_QUOTES) ?></span>
                <a class="site-nav__button" href="logout.php" style="background: #dc3545;">Выход</a>
            <?php else: ?>
                <a class="site-nav__button" href="login.php">Вход</a>
                <a class="site-nav__button" href="register.php" style="background: #28a745;">Регистрация</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="page-card">
        <h2>Корзина</h2>
        <?php if (empty($items)): ?>
            <p>Ваша корзина пока пуста. Добавьте товары из каталога и возвращайтесь сюда.</p>
            <p><a class="btn btn-primary" href="index.php">Перейти в каталог</a></p>
        <?php else: ?>
            <div class="cart-summary">
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
                        <tr class="cart-line">
                            <td>
                                <div style="display:flex; gap:14px; align-items:center;">
                                    <img class="cart-item__image" src="<?= htmlspecialchars($item['image'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>">
                                    <div>
                                        <p class="cart-item__title"><?= htmlspecialchars($item['title'], ENT_QUOTES) ?></p>
                                        <p class="cart-item__category"><?= htmlspecialchars($item['category'], ENT_QUOTES) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($item['category'], ENT_QUOTES) ?></td>
                            <td><?= number_format($item['price'], 0, '.', ' ') ?> ₸</td>
                            <td><?= $item['quantity'] ?></td>
                            <td><?= number_format($item['subtotal'], 0, '.', ' ') ?> ₸</td>
                            <td>
                                <form method="post" action="cart.php">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-secondary">Удалить</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="cart-actions">
                    <span class="btn btn-secondary">Товаров: <?= $totalAmount ?></span>
                    <span class="btn btn-primary">Сумма: <?= number_format($totalPrice, 0, '.', ' ') ?> ₸</span>
                </div>
                <div class="cart-actions">
                    <form method="post" action="cart.php">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-secondary">Очистить корзину</button>
                    </form>
                    <a class="btn btn-primary" href="index.php">Продолжить покупки</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="page-footer">
        Comic Universe © 2026 — Безопасная работа с фиктивной базой данных и корзиной.
    </footer>
</div>
</body>
</html>
