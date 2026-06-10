<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart = $_SESSION['cart'] ?? array();
    $totalAmount = array_sum($cart);
    
    if ($totalAmount == 0) {
        $_SESSION['error'] = 'Корзина пуста';
        header('Location: cart.php');
        exit;
    }
    
    if ($totalAmount > 500) {
        $_SESSION['error'] = 'Превышен лимит 500 товаров';
        header('Location: cart.php');
        exit;
    }
    
    $discountPercent = isset($_SESSION['discount_percent']) ? $_SESSION['discount_percent'] : 0;
    
    // Получаем товары для подсчёта суммы
    $productIds = array_keys($cart);
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $pdo->prepare("SELECT id, price, title FROM comics WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $products = $stmt->fetchAll();
        
        $subtotal = 0;
        $orderItems = array();
        foreach ($products as $p) {
            $qty = isset($cart[$p['id']]) ? $cart[$p['id']] : 0;
            $subtotal += $p['price'] * $qty;
            $orderItems[] = array(
                'title' => $p['title'],
                'price' => $p['price'],
                'quantity' => $qty,
                'subtotal' => $p['price'] * $qty
            );
        }
        $finalTotal = $subtotal * (100 - $discountPercent) / 100;
        
        // Создаём заказ в БД
        try {
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, discount_percent, status, created_at) VALUES (?, ?, ?, 'completed', NOW())");
            $stmt->execute(array($userId, $finalTotal, $discountPercent));
            $orderId = $pdo->lastInsertId();
            
            // Добавляем товары в заказ и в коллекцию
            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, comic_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmtCollection = $pdo->prepare("INSERT INTO user_collection (user_id, comic_id, status) VALUES (?, ?, 'bought') ON DUPLICATE KEY UPDATE status = 'bought'");
            
            foreach ($products as $p) {
                $qty = isset($cart[$p['id']]) ? $cart[$p['id']] : 0;
                if ($qty > 0) {
                    $stmtItem->execute(array($orderId, $p['id'], $qty, $p['price']));
                    $stmtCollection->execute(array($userId, $p['id']));
                }
            }
            
            // ========== ОТПРАВКА EMAIL ПОДТВЕРЖДЕНИЯ ==========
            $userEmail = $username . '@example.com';
            $subject = "✅ Подтверждение заказа #{$orderId} - Comic Universe";
            
            // Формируем список товаров
            $orderList = "";
            foreach ($orderItems as $item) {
                $orderList .= "   • {$item['title']} - {$item['quantity']} шт. x " . number_format($item['price'], 0, '.', ' ') . " ₸ = " . number_format($item['subtotal'], 0, '.', ' ') . " ₸\n";
            }
            
            // Формируем письмо
            $message = "Здравствуйте, {$username}!\n\n";
            $message .= "Благодарим вас за покупку в магазине Comic Universe!\n";
            $message .= "Ваш заказ #{$orderId} успешно оформлен и принят в обработку.\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "📦 ДЕТАЛИ ЗАКАЗА:\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= $orderList;
            $message .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "💰 СУММА ЗАКАЗА: " . number_format($finalTotal, 0, '.', ' ') . " ₸\n";
            if ($discountPercent > 0) {
                $message .= "🎫 Скидка: {$discountPercent}%\n";
                $message .= "   (Было: " . number_format($subtotal, 0, '.', ' ') . " ₸)\n";
            }
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "📅 Дата заказа: " . date('d.m.Y H:i') . "\n";
            $message .= "🆔 Номер заказа: #{$orderId}\n\n";
            $message .= "Доставка будет осуществлена в течение 3-5 рабочих дней.\n";
            $message .= "Отслеживать статус заказа вы можете в личном кабинете.\n\n";
            $message .= "С уважением,\n";
            $message .= "Команда Comic Universe\n";
            $message .= "────────────────────────────────────────\n";
            $message .= "Если у вас есть вопросы, ответьте на это письмо.\n";
            $message .= "https://comic-universe.xo.je\n";
            
            $headers = "From: noreply@comicuniverse.com\r\n";
            $headers .= "Reply-To: support@comicuniverse.com\r\n";
            $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            // Отправляем email
            $mailSent = mail($userEmail, $subject, $message, $headers);
            
            // Сохраняем информацию об отправке письма в лог (опционально)
            if ($mailSent) {
                error_log("Email подтверждения отправлен для заказа #{$orderId} на адрес {$userEmail}");
            } else {
                error_log("Ошибка отправки email для заказа #{$orderId}");
            }
            
            // Очищаем корзину и промокод
            $_SESSION['cart'] = array();
            unset($_SESSION['promo_code'], $_SESSION['discount_percent']);
            
            // Сохраняем данные для уведомления на странице
            $_SESSION['order_success'] = true;
            $_SESSION['order_id'] = $orderId;
            $_SESSION['order_total'] = $finalTotal;
            $_SESSION['order_items'] = $orderItems;
            $_SESSION['order_email_sent'] = $mailSent;
            
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Ошибка при оформлении заказа: ' . $e->getMessage();
            header('Location: cart.php');
            exit;
        }
    }
    
    header('Location: checkout.php?success=1');
    exit;
}

// Проверяем success параметр для отображения уведомления
$showSuccess = isset($_GET['success']) && isset($_SESSION['order_success']) && $_SESSION['order_success'] === true;
if ($showSuccess) {
    $orderId = isset($_SESSION['order_id']) ? $_SESSION['order_id'] : 0;
    $orderTotal = isset($_SESSION['order_total']) ? $_SESSION['order_total'] : 0;
    $orderItems = isset($_SESSION['order_items']) ? $_SESSION['order_items'] : array();
    $emailSent = isset($_SESSION['order_email_sent']) ? $_SESSION['order_email_sent'] : false;
    // Очищаем сессионные данные после получения
    unset($_SESSION['order_success'], $_SESSION['order_id'], $_SESSION['order_total'], $_SESSION['order_items'], $_SESSION['order_email_sent']);
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
$totalAmount = array_sum($cart);
$discountPercent = isset($_SESSION['discount_percent']) ? $_SESSION['discount_percent'] : 0;

// Предварительный подсчёт итоговой суммы
$preTotal = 0;
$cartProducts = array();
if (!empty($cart)) {
    $productIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $pdo->prepare("SELECT id, price, title FROM comics WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $cartProducts = $stmt->fetchAll();
    foreach ($cartProducts as $p) {
        $qty = isset($cart[$p['id']]) ? $cart[$p['id']] : 0;
        $preTotal += $p['price'] * $qty;
    }
}
$finalPreTotal = $preTotal * (100 - $discountPercent) / 100;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Оформление заказа | Comic Universe</title>
    <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
    <style>
        .checkout-container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .checkout-title { font-size: 28px; color: #fff; margin-bottom: 30px; }
        .order-summary { background: #1a1f2e; border-radius: 12px; padding: 24px; margin-bottom: 30px; }
        .order-summary h3 { color: #fff; margin-bottom: 20px; font-size: 20px; }
        .order-items { margin-bottom: 20px; }
        .order-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #2a2f3e; color: #e0e0e0; }
        .order-total { display: flex; justify-content: space-between; padding-top: 15px; margin-top: 15px; font-size: 20px; font-weight: bold; color: #667eea; border-top: 2px solid #2a2f3e; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #e0e0e0; margin-bottom: 8px; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; background: #2a2f3e; border: 1px solid #3a3f4e; border-radius: 8px; color: white; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .form-group small { color: #888; font-size: 12px; margin-top: 5px; display: block; }
        .btn-confirm { width: 100%; background: #28a745; color: white; border: none; padding: 15px; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .btn-confirm:hover { background: #218838; }
        .empty-cart { text-align: center; padding: 60px; background: #1a1f2e; border-radius: 12px; }
        .empty-cart p { color: #a0a0b0; margin-bottom: 20px; }
        .btn-primary { background: #667eea; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background: #5a67d8; }
        
        /* Модальное окно уведомления */
        .notification-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 1000; animation: fadeIn 0.3s ease; }
        .notification-modal { background: linear-gradient(135deg, #1a1f2e 0%, #2a2f3e 100%); border-radius: 20px; padding: 40px; max-width: 550px; width: 90%; text-align: center; box-shadow: 0 25px 50px rgba(0,0,0,0.5); animation: slideIn 0.3s ease; }
        .notification-modal .success-icon { font-size: 80px; margin-bottom: 20px; }
        .notification-modal h2 { color: #28a745; margin-bottom: 15px; font-size: 28px; }
        .notification-modal p { color: #e0e0e0; margin-bottom: 10px; line-height: 1.5; }
        .notification-modal .order-details { background: #1a1f2e; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: left; border: 1px solid #2a2f3e; }
        .notification-modal .order-details p { margin: 8px 0; }
        .notification-modal hr { border-color: #2a2f3e; margin: 12px 0; }
        .email-status { background: rgba(40,167,69,0.2); padding: 10px; border-radius: 8px; margin-top: 15px; }
        .email-status.success { color: #28a745; }
        .email-status.error { color: #dc3545; background: rgba(220,53,69,0.2); }
        .btn-close { background: #667eea; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: background 0.2s; margin-top: 10px; }
        .btn-close:hover { background: #5a67d8; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
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
            <a class="site-nav__button" href="cart.php">Корзина (<?php echo array_sum($_SESSION['cart'] ?? array()); ?>)</a>
            <?php if (isLoggedIn()): ?>
                <span class="site-nav__link">👤 <?php echo htmlspecialchars($username); ?></span>
                <a class="site-nav__button" href="logout.php" style="background: #dc3545;">Выход</a>
            <?php else: ?>
                <a class="site-nav__button" href="login.php">Вход</a>
                <a class="site-nav__button" href="register.php" style="background: #28a745;">Регистрация</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="checkout-container">
        <?php if ($showSuccess): ?>
            <div class="notification-overlay" id="notificationOverlay">
                <div class="notification-modal">
                    <div class="success-icon">🎉✅</div>
                    <h2>Заказ успешно оформлен!</h2>
                    <p>Спасибо за покупку, <strong><?php echo htmlspecialchars($username); ?></strong>!</p>
                    <div class="order-details">
                        <p><strong>📦 Номер заказа:</strong> #<?php echo $orderId; ?></p>
                        <p><strong>💰 Сумма:</strong> <?php echo number_format($orderTotal, 0, '.', ' '); ?> ₸</p>
                        <p><strong>📅 Дата:</strong> <?php echo date('d.m.Y H:i'); ?></p>
                        <hr>
                        <p><strong>🛒 Состав заказа:</strong></p>
                        <?php if (!empty($orderItems)): ?>
                            <?php foreach ($orderItems as $item): ?>
                                <p style="font-size:13px; margin:5px 0;">• <?php echo htmlspecialchars($item['title']); ?> - <?php echo $item['quantity']; ?> шт. x <?php echo number_format($item['price']); ?> ₸ = <?php echo number_format($item['subtotal']); ?> ₸</p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($emailSent): ?>
                        <div class="email-status success">
                            📧 Письмо с подтверждением отправлено на почту <?php echo htmlspecialchars($username); ?>@example.com
                        </div>
                    <?php else: ?>
                        <div class="email-status error">
                            ⚠️ Не удалось отправить письмо. Проверьте настройки почты.
                        </div>
                    <?php endif; ?>
                    <p style="margin-top: 15px; font-size: 13px; color: #888;">
                        Детали заказа доступны в личном кабинете
                    </p>
                    <button class="btn-close" onclick="window.location.href='index.php'">🏠 Перейти в каталог</button>
                </div>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 6000);
            </script>
        <?php endif; ?>

        <?php if ($totalAmount == 0 && !$showSuccess): ?>
            <div class="empty-cart">
                <p>🛒 Ваша корзина пуста</p>
                <a href="index.php" class="btn-primary">Перейти в каталог</a>
            </div>
        <?php elseif (!$showSuccess && $totalAmount > 0): ?>
            <h1 class="checkout-title">📋 Оформление заказа</h1>
            
            <div class="order-summary">
                <h3>Ваш заказ</h3>
                <div class="order-items">
                    <?php foreach ($cartProducts as $product): ?>
                        <?php 
                        $qty = isset($cart[$product['id']]) ? $cart[$product['id']] : 0;
                        if ($qty > 0):
                        ?>
                        <div class="order-item">
                            <span><?php echo htmlspecialchars($product['title']); ?> × <?php echo $qty; ?></span>
                            <span><?php echo number_format($product['price'] * $qty, 0, '.', ' '); ?> ₸</span>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($discountPercent > 0): ?>
                    <div class="order-item" style="color:#28a745;">
                        <span>Скидка (<?php echo $discountPercent; ?>%)</span>
                        <span>-<?php echo number_format($preTotal - $finalPreTotal, 0, '.', ' '); ?> ₸</span>
                    </div>
                <?php endif; ?>
                <div class="order-total">
                    <span>Итого к оплате:</span>
                    <span><?php echo number_format($finalPreTotal, 0, '.', ' '); ?> ₸</span>
                </div>
            </div>
            
            <form method="post" id="checkoutForm">
                <div class="form-group">
                    <label>👤 Имя получателя</label>
                    <input type="text" value="<?php echo htmlspecialchars($username); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>📧 Email для подтверждения</label>
                    <input type="email" value="<?php echo htmlspecialchars($username); ?>@example.com" disabled>
                    <small>Письмо с деталями заказа будет отправлено на этот адрес</small>
                </div>
                
                <button type="submit" class="btn-confirm">✅ Подтвердить заказ</button>
            </form>
        <?php endif; ?>
    </div>

    <footer class="page-footer">
        Comic Universe © 2026 — Магазин комиксов с БД, фильтрами и корзиной.
    </footer>
</div>
</body>
</html>