<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

$comicId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($comicId == 0) {
    header('Location: index.php');
    exit;
}

// Получаем информацию о комиксе
$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category, g.name as genre, p.name as publisher
    FROM comics c
    LEFT JOIN categories cat ON cat.id = c.category_id
    LEFT JOIN genres g ON g.id = c.genre_id
    LEFT JOIN publishers p ON p.id = c.publisher_id
    WHERE c.id = ?
");
$stmt->execute([$comicId]);
$comic = $stmt->fetch();

if (!$comic) {
    header('Location: index.php');
    exit;
}

// Получаем одобренные рецензии
$stmt = $pdo->prepare("
    SELECT r.*, u.username
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.comic_id = ? AND r.status = 'approved'
    ORDER BY r.approved_at DESC
");
$stmt->execute([$comicId]);
$reviews = $stmt->fetchAll();

// Проверяем, может ли пользователь оставить рецензию (купил комикс)
$canReview = false;
$userReview = null;
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    // Проверяем, покупал ли пользователь этот комикс
    $stmt = $pdo->prepare("SELECT 1 FROM user_collection WHERE user_id = ? AND comic_id = ? AND status IN ('bought', 'reading', 'read')");
    $stmt->execute([$userId, $comicId]);
    $canReview = $stmt->fetch() ? true : false;
    
    // Проверяем, есть ли уже рецензия от пользователя
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND comic_id = ?");
    $stmt->execute([$userId, $comicId]);
    $userReview = $stmt->fetch();
}

// Обработка отправки рецензии
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && isLoggedIn()) {
    $rating = (int)$_POST['rating'];
    $reviewText = trim($_POST['review_text']);
    
    if ($rating >= 1 && $rating <= 5 && !empty($reviewText)) {
        if ($canReview && !$userReview) {
            $stmt = $pdo->prepare("INSERT INTO reviews (user_id, comic_id, rating, review_text, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $comicId, $rating, $reviewText]);
            $_SESSION['message'] = 'Рецензия отправлена на модерацию!';
            header('Location: comic.php?id=' . $comicId);
            exit;
        } else {
            $_SESSION['error'] = 'Вы уже оставляли рецензию на этот комикс';
        }
    } else {
        $_SESSION['error'] = 'Пожалуйста, поставьте оценку и напишите текст';
    }
    header('Location: comic.php?id=' . $comicId);
    exit;
}

// Проверяем, подписан ли пользователь на серию
$isSubscribed = false;
if (isLoggedIn() && $comic['series_id']) {
    $stmt = $pdo->prepare("SELECT 1 FROM series_subscriptions WHERE user_id = ? AND series_id = ?");
    $stmt->execute([$_SESSION['user_id'], $comic['series_id']]);
    $isSubscribed = $stmt->fetchColumn() > 0;
}

$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['message'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($comic['title']); ?> | Comic Universe</title>
    <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
    <style>
        .comic-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .comic-main { display: flex; gap: 40px; flex-wrap: wrap; background: #1a1f2e; border-radius: 16px; padding: 30px; margin-bottom: 30px; }
        .comic-image { width: 300px; border-radius: 12px; }
        .comic-info { flex: 1; }
        .comic-title { color: white; font-size: 28px; margin-bottom: 15px; }
        .comic-rating { color: #ffc107; font-size: 24px; margin-bottom: 15px; }
        .comic-price { font-size: 28px; color: #667eea; font-weight: bold; margin: 15px 0; }
        .comic-detail { color: #a0a0b0; margin: 8px 0; }
        .btn { padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; margin-right: 10px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #28a745; color: white; }
        .btn-primary:hover { background: #218838; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .subscribe-btn { background: #ff9800; color: white; }
        .subscribe-btn:hover { background: #e68900; }
        .reviews-section { background: #1a1f2e; border-radius: 16px; padding: 30px; }
        .review-card { background: #2a2f3e; border-radius: 12px; padding: 20px; margin-bottom: 15px; }
        .review-author { color: white; font-weight: bold; }
        .review-rating { color: #ffc107; margin: 5px 0; }
        .review-text { color: #e0e0e0; line-height: 1.5; margin-top: 10px; }
        .no-reviews { color: #a0a0b0; text-align: center; padding: 40px; }
        .review-form { background: #2a2f3e; border-radius: 12px; padding: 20px; margin-top: 20px; }
        .review-form h3 { color: white; margin-bottom: 15px; }
        .star-rating { display: flex; gap: 8px; margin-bottom: 15px; }
        .star { font-size: 28px; cursor: pointer; color: #555; transition: color 0.2s; }
        .star.selected { color: #ffc107; }
        .review-form textarea { width: 100%; padding: 12px; background: #1a1f2e; border: 1px solid #3a3f4e; color: white; border-radius: 8px; min-height: 100px; resize: vertical; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-danger { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .btn-home { background: #667eea; color: white; margin-bottom: 20px; display: inline-block; }
        .btn-home:hover { background: #5a67d8; }
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
    
    <div class="comic-container">
        <!-- Кнопка возврата на главную -->
        <a href="index.php" class="back-link btn-home btn">🏠 На главную</a>
        
        <?php if ($message): ?>
            <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="comic-main">
            <img class="comic-image" src="images/<?php echo htmlspecialchars($comic['image']); ?>" onerror="this.src='https://placehold.co/300x450/1a1f2e/667eea?text=No+Image'">
            <div class="comic-info">
                <h1 class="comic-title"><?php echo htmlspecialchars($comic['title']); ?></h1>
                <div class="comic-rating">
                    <?php 
                    $rating = round($comic['rating'] ?? 0, 1);
                    for ($i = 0; $i < floor($rating); $i++) echo '★';
                    for ($i = floor($rating); $i < 5; $i++) echo '☆';
                    echo " ($rating)";
                    ?>
                </div>
                <div class="comic-detail">📚 Категория: <?php echo htmlspecialchars($comic['category'] ?? 'Не указана'); ?></div>
                <div class="comic-detail">🎭 Жанр: <?php echo htmlspecialchars($comic['genre'] ?? 'Не указан'); ?></div>
                <div class="comic-detail">🏢 Издательство: <?php echo htmlspecialchars($comic['publisher'] ?? 'Не указано'); ?></div>
                <div class="comic-detail">👤 Автор: <?php echo htmlspecialchars($comic['author'] ?? 'Не указан'); ?></div>
                <div class="comic-detail">📅 Год: <?php echo $comic['year'] ?? '2024'; ?></div>
                <div class="comic-price"><?php echo number_format($comic['price']); ?> ₸</div>
                <div class="comic-detail">📖 <?php echo htmlspecialchars($comic['description'] ?? 'Описание отсутствует'); ?></div>
                
                <div style="margin-top: 20px;">
                    <form method="post" action="cart.php" style="display: inline;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $comic['id']; ?>">
                        <button type="submit" class="btn btn-primary">🛒 Добавить в корзину</button>
                    </form>
                    <form method="post" action="wishlist.php" style="display: inline;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $comic['id']; ?>">
                        <button type="submit" class="btn btn-secondary">❤️ В избранное</button>
                    </form>
                </div>
                
                <?php if ($comic['series_id']): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #2a2f3e;">
                    <form method="post" action="subscribe.php">
                        <input type="hidden" name="series_id" value="<?php echo $comic['series_id']; ?>">
                        <input type="hidden" name="action" value="<?php echo $isSubscribed ? 'unsubscribe' : 'subscribe'; ?>">
                        <button type="submit" class="btn subscribe-btn">
                            <?php echo $isSubscribed ? '🔕 Отписаться от серии' : '🔔 Подписаться на серию'; ?>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="reviews-section">
            <h2 style="color: white;">📝 Рецензии</h2>
            
            <?php if (empty($reviews)): ?>
                <div class="no-reviews">Пока нет рецензий. Будьте первым!</div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-author">👤 <?php echo htmlspecialchars($review['username']); ?></div>
                        <div class="review-rating"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></div>
                        <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                        <div style="font-size: 12px; color: #666; margin-top: 10px;">📅 <?php echo date('d.m.Y', strtotime($review['approved_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Кнопка "Написать рецензию" -->
            <?php if (isLoggedIn()): ?>
                <?php if ($canReview && !$userReview): ?>
                    <button class="btn btn-warning" id="showReviewFormBtn" style="margin-top: 20px;">✏️ Написать рецензию</button>
                    
                    <div id="reviewForm" class="review-form" style="display: none;">
                        <h3>✏️ Написать рецензию</h3>
                        <form method="post">
                            <input type="hidden" name="submit_review" value="1">
                            <div class="star-rating" id="starRating">
                                <span class="star" data-value="1">☆</span>
                                <span class="star" data-value="2">☆</span>
                                <span class="star" data-value="3">☆</span>
                                <span class="star" data-value="4">☆</span>
                                <span class="star" data-value="5">☆</span>
                            </div>
                            <input type="hidden" name="rating" id="ratingValue" value="5">
                            <textarea name="review_text" placeholder="Поделитесь впечатлениями о комиксе..." required></textarea>
                            <button type="submit" class="btn btn-primary">📤 Отправить на модерацию</button>
                            <button type="button" class="btn btn-secondary" id="hideReviewFormBtn">Отмена</button>
                        </form>
                    </div>
                <?php elseif ($userReview): ?>
                    <div class="alert alert-info" style="background: #cce5ff; color: #004085; padding: 12px; border-radius: 8px; margin-top: 20px;">
                        📝 Вы уже оставили рецензию на этот комикс. Статус: 
                        <?php echo $userReview['status'] == 'pending' ? '⏳ На модерации' : ($userReview['status'] == 'approved' ? '✅ Одобрена' : '❌ Отклонена'); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" style="background: #fff3cd; color: #856404; padding: 12px; border-radius: 8px; margin-top: 20px;">
                        📚 Вы можете оставить рецензию только после покупки комикса!
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="margin-top: 20px; text-align: center;">
                    <a href="login.php" class="btn btn-primary">🔐 Войдите, чтобы оставить рецензию</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer class="page-footer">
        Comic Universe © 2026 — Магазин комиксов
    </footer>
</div>

<script>
// Звёздный рейтинг
document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', function() {
        const value = parseInt(this.dataset.value);
        document.getElementById('ratingValue').value = value;
        document.querySelectorAll('.star').forEach((s, i) => {
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

// Показать/скрыть форму рецензии
const showBtn = document.getElementById('showReviewFormBtn');
const hideBtn = document.getElementById('hideReviewFormBtn');
const reviewForm = document.getElementById('reviewForm');

if (showBtn) {
    showBtn.addEventListener('click', function() {
        reviewForm.style.display = 'block';
        showBtn.style.display = 'none';
    });
}
if (hideBtn) {
    hideBtn.addEventListener('click', function() {
        reviewForm.style.display = 'none';
        if (showBtn) showBtn.style.display = 'inline-block';
    });
}
</script>
</body>
</html>