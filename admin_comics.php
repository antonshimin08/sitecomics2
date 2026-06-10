<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Удаление комикса
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $comicId = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM comics WHERE id = ?");
    $stmt->execute([$comicId]);
    $_SESSION['message'] = 'Комикс удалён!';
    header('Location: admin_comics.php');
    exit;
}

// Добавление комикса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comic'])) {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $price = (int)$_POST['price'];
    $categoryId = (int)$_POST['category_id'];
    $genreId = (int)$_POST['genre_id'];
    $publisherId = (int)$_POST['publisher_id'];
    $stock = (int)$_POST['stock_quantity'];
    $image = trim($_POST['image']);
    $description = trim($_POST['description']);
    $preorder = isset($_POST['preorder']) ? 1 : 0;
    
    $stmt = $pdo->prepare("INSERT INTO comics (title, author, price, category_id, genre_id, publisher_id, stock_quantity, image, description, preorder) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $author, $price, $categoryId, $genreId, $publisherId, $stock, $image, $description, $preorder]);
    $_SESSION['message'] = 'Комикс добавлен!';
    header('Location: admin_comics.php');
    exit;
}

// Редактирование комикса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comic'])) {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $price = (int)$_POST['price'];
    $categoryId = (int)$_POST['category_id'];
    $genreId = (int)$_POST['genre_id'];
    $publisherId = (int)$_POST['publisher_id'];
    $stock = (int)$_POST['stock_quantity'];
    $image = trim($_POST['image']);
    $description = trim($_POST['description']);
    $preorder = isset($_POST['preorder']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE comics SET title=?, author=?, price=?, category_id=?, genre_id=?, publisher_id=?, stock_quantity=?, image=?, description=?, preorder=? WHERE id=?");
    $stmt->execute([$title, $author, $price, $categoryId, $genreId, $publisherId, $stock, $image, $description, $preorder, $id]);
    $_SESSION['message'] = 'Комикс обновлён!';
    header('Location: admin_comics.php');
    exit;
}

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$genres = $pdo->query("SELECT id, name FROM genres ORDER BY name")->fetchAll();
$publishers = $pdo->query("SELECT id, name FROM publishers ORDER BY name")->fetchAll();
$comics = $pdo->query("SELECT c.*, cat.name as category, g.name as genre, p.name as publisher FROM comics c LEFT JOIN categories cat ON cat.id=c.category_id LEFT JOIN genres g ON g.id=c.genre_id LEFT JOIN publishers p ON p.id=c.publisher_id ORDER BY c.id DESC")->fetchAll();

$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление комиксами | Админ-панель</title>
    <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
    <style>
        .admin-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .add-form { background: #1a1f2e; border-radius: 12px; padding: 20px; margin-bottom: 30px; }
        .add-form h3 { color: white; margin-bottom: 15px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .form-grid input, .form-grid select, .form-grid textarea { padding: 8px; background: #2a2f3e; border: 1px solid #3a3f4e; color: white; border-radius: 6px; }
        .form-grid textarea { grid-column: span 2; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; margin-top: 15px; }
        .comics-table { width: 100%; background: #1a1f2e; border-radius: 12px; overflow-x: auto; margin-top: 20px; }
        .comics-table th, .comics-table td { padding: 10px; text-align: left; border-bottom: 1px solid #2a2f3e; color: #e0e0e0; }
        .comics-table th { background: #2a2f3e; color: white; }
        .btn-edit { background: #ffc107; color: #333; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 5px; font-size: 12px; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        h1 { color: white; margin-bottom: 20px; }
        .edit-row { display: none; background: #2a2f3e; }
        .edit-row.show { display: table-row; }
        .edit-form { padding: 15px; }
        .edit-form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px; }
        .edit-form-grid input, .edit-form-grid select, .edit-form-grid textarea { padding: 8px; background: #1a1f2e; border: 1px solid #3a3f4e; color: white; border-radius: 6px; }
        .edit-form-grid textarea { grid-column: span 4; }
        .btn-save { background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; margin-right: 10px; }
        .btn-cancel { background: #6c757d; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; }
        .back-link { color: #667eea; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="admin-container">
    <h1>📚 Управление комиксами</h1>
    <a href="index.php" class="back-link">← На главную</a>
    
    <?php if ($message): ?>
        <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <!-- Форма добавления -->
    <div class="add-form">
        <h3>➕ Добавить новый комикс</h3>
        <form method="post">
            <input type="hidden" name="add_comic" value="1">
            <div class="form-grid">
                <input type="text" name="title" placeholder="Название" required>
                <input type="text" name="author" placeholder="Автор" required>
                <input type="number" name="price" placeholder="Цена (₸)" required>
                <select name="category_id" required>
                    <option value="">Категория</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="genre_id">
                    <option value="">Жанр</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="publisher_id">
                    <option value="">Издательство</option>
                    <?php foreach ($publishers as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="stock_quantity" placeholder="Количество" value="10">
                <input type="text" name="image" placeholder="Имя файла изображения (например spiderman.jpg)">
                <textarea name="description" placeholder="Описание" rows="2"></textarea>
                <label style="color:white;"><input type="checkbox" name="preorder" value="1"> ⚡ Предзаказ</label>
            </div>
            <button type="submit" class="btn-submit">➕ Добавить комикс</button>
        </form>
    </div>
    
    <!-- Список комиксов -->
    <div class="comics-table">
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Изображение</th>
                    <th>Название</th>
                    <th>Автор</th>
                    <th>Цена</th>
                    <th>Категория</th>
                    <th>В наличии</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comics as $comic): ?>
                    <tr id="row-<?php echo $comic['id']; ?>">
                        <td><?php echo $comic['id']; ?></td>
                        <td><img src="images/<?php echo htmlspecialchars($comic['image']); ?>" width="40" style="border-radius: 4px;" onerror="this.src='https://placehold.co/40x50/1a1f2e/667eea?text=No'"></td>
                        <td><?php echo htmlspecialchars($comic['title']); ?></td>
                        <td><?php echo htmlspecialchars($comic['author'] ?? '-'); ?></td>
                        <td><?php echo number_format($comic['price']); ?> ₸</td>
                        <td><?php echo htmlspecialchars($comic['category'] ?? '-'); ?></td>
                        <td><?php echo $comic['stock_quantity']; ?> шт.</td>
                        <td>
                            <button class="btn-edit" onclick="toggleEdit(<?php echo $comic['id']; ?>)">✏️ Редактировать</button>
                            <a href="?delete=1&id=<?php echo $comic['id']; ?>" class="btn-delete" onclick="return confirm('Удалить комикс?')">🗑️ Удалить</a>
                        </td>
                    </tr>
                    <tr id="edit-row-<?php echo $comic['id']; ?>" class="edit-row">
                        <td colspan="8">
                            <div class="edit-form">
                                <form method="post">
                                    <input type="hidden" name="edit_comic" value="1">
                                    <input type="hidden" name="id" value="<?php echo $comic['id']; ?>">
                                    <div class="edit-form-grid">
                                        <input type="text" name="title" value="<?php echo htmlspecialchars($comic['title']); ?>" placeholder="Название">
                                        <input type="text" name="author" value="<?php echo htmlspecialchars($comic['author'] ?? ''); ?>" placeholder="Автор">
                                        <input type="number" name="price" value="<?php echo $comic['price']; ?>" placeholder="Цена">
                                        <select name="category_id">
                                            <option value="">Категория</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo $comic['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="genre_id">
                                            <option value="">Жанр</option>
                                            <?php foreach ($genres as $g): ?>
                                                <option value="<?php echo $g['id']; ?>" <?php echo $comic['genre_id'] == $g['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($g['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="publisher_id">
                                            <option value="">Издательство</option>
                                            <?php foreach ($publishers as $p): ?>
                                                <option value="<?php echo $p['id']; ?>" <?php echo $comic['publisher_id'] == $p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" name="stock_quantity" value="<?php echo $comic['stock_quantity']; ?>" placeholder="Количество">
                                        <input type="text" name="image" value="<?php echo htmlspecialchars($comic['image']); ?>" placeholder="Имя файла">
                                        <textarea name="description" placeholder="Описание" rows="2"><?php echo htmlspecialchars($comic['description'] ?? ''); ?></textarea>
                                        <label style="color:white; display:flex; align-items:center; gap:10px;">
                                            <input type="checkbox" name="preorder" value="1" <?php echo $comic['preorder'] ? 'checked' : ''; ?>> ⚡ Предзаказ
                                        </label>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn-save">💾 Сохранить</button>
                                        <button type="button" class="btn-cancel" onclick="toggleEdit(<?php echo $comic['id']; ?>)">❌ Отмена</button>
                                    </div>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleEdit(comicId) {
    var editRow = document.getElementById('edit-row-' + comicId);
    if (editRow) {
        if (editRow.classList.contains('show')) {
            editRow.classList.remove('show');
        } else {
            editRow.classList.add('show');
        }
    }
}
</script>
</body>
</html>