<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/myauth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Обновление статуса заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $validStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
    
    if (in_array($newStatus, $validStatuses)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        $_SESSION['message'] = "Статус заказа #{$orderId} обновлён!";
        header('Location: admin_orders.php');
        exit;
    }
}

// Фильтрация заказов
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$validFilters = ['pending', 'processing', 'shipped', 'completed', 'cancelled', 'all'];
if (!in_array($statusFilter, $validFilters)) {
    $statusFilter = 'all';
}

$sql = "
    SELECT o.*, u.username, u.email, COUNT(oi.id) as items_count 
    FROM orders o
    JOIN users u ON u.id = o.user_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
";
if ($statusFilter !== 'all') {
    $sql .= " WHERE o.status = :status";
}
$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";

if ($statusFilter !== 'all') {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['status' => $statusFilter]);
} else {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}
$orders = $stmt->fetchAll();

// Статистика
$stats = $pdo->query("
    SELECT status, COUNT(*) as count FROM orders GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы | Админ-панель</title>
    <link rel="stylesheet" href="main.css?v=<?php echo time(); ?>">
    <style>
        .admin-container { max-width: 1300px; margin: 0 auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: #1a1f2e; border-radius: 12px; padding: 20px; text-align: center; }
        .stat-number { font-size: 28px; font-weight: bold; }
        .stat-label { color: #a0a0b0; font-size: 12px; margin-top: 5px; }
        .status-pending .stat-number { color: #ffc107; }
        .status-processing .stat-number { color: #17a2b8; }
        .status-shipped .stat-number { color: #6f42c1; }
        .status-completed .stat-number { color: #28a745; }
        .status-cancelled .stat-number { color: #dc3545; }
        
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-btn { background: #2a2f3e; border: none; padding: 8px 20px; border-radius: 20px; color: #a0a0b0; cursor: pointer; text-decoration: none; display: inline-block; }
        .filter-btn.active { background: #667eea; color: white; }
        .filter-btn:hover { background: #3a3f4e; color: white; }
        
        .orders-table { width: 100%; background: #1a1f2e; border-radius: 12px; overflow-x: auto; }
        .orders-table th, .orders-table td { padding: 12px; text-align: left; border-bottom: 1px solid #2a2f3e; color: #e0e0e0; }
        .orders-table th { background: #2a2f3e; color: white; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .status-pending-badge { background: #ffc107; color: #333; }
        .status-processing-badge { background: #17a2b8; color: white; }
        .status-shipped-badge { background: #6f42c1; color: white; }
        .status-completed-badge { background: #28a745; color: white; }
        .status-cancelled-badge { background: #dc3545; color: white; }
        
        .status-select { padding: 5px 10px; background: #2a2f3e; border: 1px solid #3a3f4e; color: white; border-radius: 6px; cursor: pointer; }
        .btn-update { background: #667eea; color: white; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; }
        .order-details { display: none; background: #2a2f3e; padding: 15px; margin-top: 10px; border-radius: 8px; }
        .order-details.show { display: block; }
        .view-btn { background: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; margin-right: 5px; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        h1 { color: white; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="admin-container">
    <h1>📦 Управление заказами</h1>
    <a href="index.php" style="color: #667eea;">← На главную</a>
    
    <?php if ($message): ?>
        <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <!-- Статистика -->
    <div class="stats-grid">
        <div class="stat-card status-pending"><div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div><div class="stat-label">⏳ Ожидают</div></div>
        <div class="stat-card status-processing"><div class="stat-number"><?php echo $stats['processing'] ?? 0; ?></div><div class="stat-label">🔄 В обработке</div></div>
        <div class="stat-card status-shipped"><div class="stat-number"><?php echo $stats['shipped'] ?? 0; ?></div><div class="stat-label">🚚 Отправлены</div></div>
        <div class="stat-card status-completed"><div class="stat-number"><?php echo $stats['completed'] ?? 0; ?></div><div class="stat-label">✅ Завершены</div></div>
        <div class="stat-card status-cancelled"><div class="stat-number"><?php echo $stats['cancelled'] ?? 0; ?></div><div class="stat-label">❌ Отменены</div></div>
    </div>
    
    <!-- Фильтры -->
    <div class="filter-tabs">
        <a href="?status=all" class="filter-btn <?php echo $statusFilter == 'all' ? 'active' : ''; ?>">📊 Все</a>
        <a href="?status=pending" class="filter-btn <?php echo $statusFilter == 'pending' ? 'active' : ''; ?>">⏳ Ожидают</a>
        <a href="?status=processing" class="filter-btn <?php echo $statusFilter == 'processing' ? 'active' : ''; ?>">🔄 В обработке</a>
        <a href="?status=shipped" class="filter-btn <?php echo $statusFilter == 'shipped' ? 'active' : ''; ?>">🚚 Отправлены</a>
        <a href="?status=completed" class="filter-btn <?php echo $statusFilter == 'completed' ? 'active' : ''; ?>">✅ Завершены</a>
        <a href="?status=cancelled" class="filter-btn <?php echo $statusFilter == 'cancelled' ? 'active' : ''; ?>">❌ Отменены</a>
    </div>
    
    <div class="orders-table">
        <table style="width:100%;">
            <thead>
                <tr><th>ID</th><th>Пользователь</th><th>Сумма</th><th>Товаров</th><th>Статус</th><th>Дата</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:40px;">Нет заказов</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $statusClass = '';
                        switch($order['status']) {
                            case 'pending': $statusClass = 'status-pending-badge'; break;
                            case 'processing': $statusClass = 'status-processing-badge'; break;
                            case 'shipped': $statusClass = 'status-shipped-badge'; break;
                            case 'completed': $statusClass = 'status-completed-badge'; break;
                            case 'cancelled': $statusClass = 'status-cancelled-badge'; break;
                        }
                        ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?><br><small><?php echo htmlspecialchars($order['email']); ?></small></td>
                            <td style="color:#667eea;"><?php echo number_format($order['total_amount']); ?> ₸</td>
                            <td><?php echo $order['items_count']; ?> шт.</td>
                            <td>
                                <form method="post" style="display:flex; gap:5px; align-items:center;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <select name="status" class="status-select">
                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>⏳ Ожидает</option>
                                        <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>🔄 В обработке</option>
                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>🚚 Отправлен</option>
                                        <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>✅ Завершён</option>
                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>❌ Отменён</option>
                                    </select>
                                    <button type="submit" class="btn-update">Обн.</button>
                                </form>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                            <td>
                                <button class="view-btn" onclick="toggleDetails(<?php echo $order['id']; ?>)">📋 Детали</button>
                            </td>
                        </tr>
                        <tr id="details-<?php echo $order['id']; ?>" style="display:none;">
                            <td colspan="7" style="background:#2a2f3e;">
                                <div style="padding:15px;">
                                    <strong>Детали заказа #<?php echo $order['id']; ?></strong><br>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT oi.*, c.title FROM order_items oi JOIN comics c ON c.id = oi.comic_id WHERE oi.order_id = ?");
                                    $stmt->execute([$order['id']]);
                                    $items = $stmt->fetchAll();
                                    foreach ($items as $item): ?>
                                        <div style="padding:5px 0;">• <?php echo htmlspecialchars($item['title']); ?> - <?php echo $item['quantity']; ?> шт. x <?php echo number_format($item['price']); ?> ₸ = <?php echo number_format($item['quantity'] * $item['price']); ?> ₸</div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function toggleDetails(orderId) {
    var row = document.getElementById('details-' + orderId);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}
</script>
</body>
</html>