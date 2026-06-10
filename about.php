<?php
session_start();
require_once __DIR__ . '/myauth.php';
$cartCount = array_sum($_SESSION['cart'] ?? []);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>О магазине — Comic Universe</title>
    <link rel="stylesheet" href="http://comic-universe.xo.je/main.css?v=<?= time() ?>">
</head>
<body>
<div class="page">
    <header class="site-header">
        <div class="site-brand">
            <div class="site-brand__logo">CU</div>
            <div>
                <h1 class="site-brand__title">Comic Universe</h1>
                <p class="site-brand__hint">Магазин комиксов, мерча и коллекционных изданий.</p>
            </div>
        </div>
        <nav class="site-nav">
            <a class="site-nav__link" href="index.php">Каталог</a>
            <a class="site-nav__link" href="about.php">О магазине</a>
            <a class="site-nav__button" href="cart.php">Корзина (<?= $cartCount ?>)</a>
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
        <h2>О магазине Comic Universe</h2>
        <p>Comic Universe — это витрина современного комикс-контента из мира Marvel, DC и других легендарных вселенных. Наш проект демонстрирует работу с базой данных через PDO, удобный каталог, фильтрацию, поиск и полноценную корзину покупок.</p>
        <p>Ты можешь фильтровать товары по категориям, искать интересующие позиции и добавлять их в корзину, чтобы посмотреть все выбранные комиксы на отдельной странице.</p>
        <p>Каждый товар хранится в базе данных SQLite, а весь вывод защищен подготовленными запросами PDO. Это отличный пример безопасной работы PHP с SQL.</p>
        <p>Переходи в каталог, выбирай комиксы и смотри, как удобно работает мультимедийный магазин.</p>
    </div>

    <footer class="page-footer">
        Comic Universe © 2026 — Демо-магазин комиксов с интеграцией БД и корзиной.
    </footer>
</div>
</body>
</html>
