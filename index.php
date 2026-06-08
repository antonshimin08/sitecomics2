<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

$cartCount = array_sum($_SESSION['cart'] ?? []);

$search = trim((string)($_GET['search'] ?? ''));
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 8;
$offset = ($page - 1) * $limit;

$whereClauses = ['1=1'];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(c.title LIKE :search OR cat.name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($categoryId > 0) {
    $whereClauses[] = 'c.category_id = :category_id';
    $params[':category_id'] = $categoryId;
}

$whereSql = implode(' AND ', $whereClauses);

$countSql = "SELECT COUNT(*) FROM comics c JOIN categories cat ON cat.id = c.category_id WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $limit));

$sql = "SELECT c.id, c.title, c.price, c.image, cat.name AS category FROM comics c JOIN categories cat ON cat.id = c.category_id WHERE $whereSql ORDER BY c.id ASC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$comics = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Comic Universe | Магазин комиксов</title>
    <link rel="stylesheet" href="styles.css">
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

    <section class="hero">
        <h2 class="hero__title">Добро пожаловать в Comic Universe</h2>
        <p class="hero__subtitle">Выбирай комиксы, фильтруй по категориям и добавляй товары в корзину.</p>
    </section>

    <div class="layout">
        <aside class="filters">
            <h2>Фильтры</h2>
            <form method="get" action="index.php">
                <label>Поиск по названию
                    <input type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>" placeholder="Введите название">
                </label>
                <label>Категория
                    <select name="category">
                        <option value="0">Все категории</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $categoryId === (int)$category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name'], ENT_QUOTES) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Применить</button>
            </form>
        </aside>

        <section>
            <div class="product-grid">
                <?php if (count($comics) === 0): ?>
                    <div class="page-card">
                        <p>По вашему запросу ничего не найдено. Попробуйте другой поисковый запрос или категорию.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($comics as $comic): ?>
                <article class="card">
                    <img class="card__image" loading="lazy" src="<?= htmlspecialchars($comic['image'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($comic['title'], ENT_QUOTES) ?>" onerror="this.src='https://via.placeholder.com/400x500/0b1220/ffffff?text=No+Image'">
                    <div class="card__body">
                        <div class="card__meta">
                            <span><?= htmlspecialchars($comic['category'], ENT_QUOTES) ?></span>
                            <span><?= number_format($comic['price'], 0, '.', ' ') ?> ₸</span>
                        </div>
                        <h3 class="card__title"><?= htmlspecialchars($comic['title'], ENT_QUOTES) ?></h3>
                        <form method="post" action="cart.php">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $comic['id'] ?>">
                            <button type="submit" class="btn btn-primary">Добавить в корзину</button>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="page-card">
                <div class="cart-actions">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php $query = array_filter([
                            'search' => $search,
                            'category' => $categoryId ?: null,
                            'page' => $i,
                        ], fn($value) => $value !== null && $value !== ''); ?>
                        <a class="btn btn-secondary <?= $i === $page ? 'active' : '' ?>" href="?<?= htmlspecialchars(http_build_query($query), ENT_QUOTES) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <footer class="page-footer">
        Comic Universe © 2026 — Магазин комиксов с БД, фильтрами и корзиной.
    </footer>
</div>
</body>
</html>
