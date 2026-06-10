<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Бан/разбан пользователя
if (isset($_GET['ban']) && isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
    $action = $_GET['ban'];
    
    if ($action === 'yes') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'banned' WHERE id = ? AND role != 'admin'");
        $stmt->execute([$userId]);
        $_SESSION['message'] = 'Пользователь забанен';
    } elseif ($action === 'no') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['message'] = 'Пользователь разбанен';
    }
    header('Location: admin_users.php');
    exit;
}

// Удаление пользователя
if (isset($_GET['delete']) && isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
    // Нельзя удалить админа
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$userId]);
    $_SESSION['message'] = 'Пользователь удалён';
    header('Location: admin_users.php');
    exit;
}

// Изменение роли
if (isset($_GET['role']) && isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
    $newRole = $_GET['role'];
    if ($newRole === 'admin' || $newRole === 'user') {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND id != ?");
        $stmt->execute([$newRole, $userId, $_SESSION['user_id']]);
        $_SESSION['message'] = 'Роль пользователя изменена';
    }
    header('Location: admin_users.php');
    exit;
}

// Поиск пользователей
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT * FROM users WHERE 1=1";
if ($search) {
    $sql .= " AND (username LIKE :search OR email LIKE :search)";
}
$sql .= " ORDER BY id DESC";

if ($search) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
$users = $stmt->fetchAll();

$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Пользователи | Админ-панель</title>
    <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
    <style>
        .admin-container { max-width: 1300px; margin: 0 auto; padding: 20px; }
        .users-table { width: 100%; background: #1a1f2e; border-radius: 12px; overflow-x: auto; }
        .users-table th, .users-table td { padding: 12px; text-align: left; border-bottom: 1px solid #2a2f3e; color: #e0e0e0; }
        .users-table th { background: #2a2f3e; color: white; }
        .user-banned { opacity: 0.6; background: rgba(220,53,69,0.1); }
        .status-banned { color: #dc3545; font-weight: bold; }
        .status-active { color: #28a745; }
        .role-admin { color: #ffc107; }
        .btn-ban { background: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; margin-right: 5px; }
        .btn-unban { background: #28a745; color: white; border: none; padding: 5px 12px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; margin-right: 5px; }
        .btn-delete { background: #6c757d; color: white; border: none; padding: 5px 12px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; }
        .btn-make-admin { background: #ffc107; color: #333; border: none; padding: 5px 12px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; margin-right: 5px; }
        .btn-make-user { background: #17a2b8; color: white; border: none; padding: 5px 12px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; margin-right: 5px; }
        .search-form { margin-bottom: 20px; display: flex; gap: 10px; }
        .search-input { padding: 10px; background: #2a2f3e; border: 1px solid #3a3f4e; color: white; border-radius: 6px; flex: 1; }
        .search-btn { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        h1 { color: white; margin-bottom: 20px; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; color: #a0a0b0; }
    </style>
</head>
<body>
<div class="admin-container">
    <h1>👥 Управление пользователями</h1>
    <a href="index.php" style="color: #667eea;">← На главную</a>
    
    <?php if ($message): ?>
        <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <div class="stats">
        <span>📊 Всего пользователей: <?php echo count($users); ?></span>
        <span>👑 Администраторов: <?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?></span>
        <span>🚫 Забаненных: <?php echo count(array_filter($users, fn($u) => ($u['status'] ?? 'active') === 'banned')); ?></span>
    </div>
    
    <form method="get" class="search-form">
        <input type="text" name="search" class="search-input" placeholder="Поиск по имени или email..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="search-btn">🔍 Найти</button>
        <?php if ($search): ?>
            <a href="admin_users.php" class="search-btn" style="background:#6c757d; text-decoration:none;">Сбросить</a>
        <?php endif; ?>
    </form>
    
    <div class="users-table">
        <table style="width:100%;">
            <thead>
                <tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th>Статус</th><th>Дата регистрации</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php $isBanned = ($user['status'] ?? 'active') === 'banned'; ?>
                    <tr class="<?php echo $isBanned ? 'user-banned' : ''; ?>">
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($user['username']); ?>
                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                <span style="color:#ffc107;"> (Вы)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="<?php echo $user['role'] == 'admin' ? 'role-admin' : ''; ?>">
                            <?php echo $user['role'] == 'admin' ? '👑 Администратор' : '👤 Пользователь'; ?>
                        </td>
                        <td>
                            <?php if ($isBanned): ?>
                                <span class="status-banned">🚫 Забанен</span>
                            <?php else: ?>
                                <span class="status-active">✅ Активен</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id'] && $user['role'] !== 'admin'): ?>
                                <?php if ($isBanned): ?>
                                    <a href="?ban=no&user_id=<?php echo $user['id']; ?>" class="btn-unban" onclick="return confirm('Разбанить пользователя?')">🔓 Разбанить</a>
                                <?php else: ?>
                                    <a href="?ban=yes&user_id=<?php echo $user['id']; ?>" class="btn-ban" onclick="return confirm('Забанить пользователя?')">🔒 Забанить</a>
                                <?php endif; ?>
                                <a href="?role=admin&user_id=<?php echo $user['id']; ?>" class="btn-make-admin" onclick="return confirm('Сделать администратором?')">👑 Сделать админом</a>
                                <a href="?delete=1&user_id=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Удалить пользователя? Это действие необратимо!')">🗑️ Удалить</a>
                            <?php elseif ($user['id'] != $_SESSION['user_id'] && $user['role'] === 'admin'): ?>
                                <a href="?role=user&user_id=<?php echo $user['id']; ?>" class="btn-make-user" onclick="return confirm('Снять права администратора?')">⬇️ Снять админку</a>
                            <?php else: ?>
                                <span style="color:#666;">(Нельзя изменить себя)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>