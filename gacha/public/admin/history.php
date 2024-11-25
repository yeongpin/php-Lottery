<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 分頁設置
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 篩選條件
$category = isset($_GET['category']) ? $_GET['category'] : '';
$username = isset($_GET['username']) ? $_GET['username'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// 構建查詢條件
$where = [];
$params = [];

if ($category) {
    $where[] = "d.category = ?";
    $params[] = $category;
}
if ($username) {
    $where[] = "u.username LIKE ?";
    $params[] = "%$username%";
}
if ($date_from) {
    $where[] = "d.drawTime >= ?";
    $params[] = $date_from . " 00:00:00";
}
if ($date_to) {
    $where[] = "d.drawTime <= ?";
    $params[] = $date_to . " 23:59:59";
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// 獲取總記錄數
$count_sql = "
    SELECT COUNT(*) as total 
    FROM draw_history d 
    JOIN users u ON d.userId = u.id 
    $where_clause
";
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $per_page);

// 獲取歷史記錄
$sql = "
    SELECT d.*, u.username 
    FROM draw_history d 
    JOIN users u ON d.userId = u.id 
    $where_clause 
    ORDER BY d.drawTime DESC 
    LIMIT $offset, $per_page
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>抽獎歷史 - 管理後台</title>
    <link rel="stylesheet" href="../styles/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/nav.php'; ?>
        
        <main class="admin-content">
            <h2>抽獎歷史</h2>

            <!-- 篩選表單 -->
            <form class="filter-form">
                <div class="form-group">
                    <label>類別</label>
                    <select name="category">
                        <option value="">全部</option>
                        <?php
                        $cats = $conn->query("SELECT DISTINCT name FROM categories");
                        while ($cat = $cats->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <option value="<?php echo $cat['name']; ?>" 
                            <?php echo $category == $cat['name'] ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>用戶名</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>">
                </div>
                <div class="form-group">
                    <label>開始日期</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="form-group">
                    <label>結束日期</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <button type="submit">篩選</button>
            </form>

            <!-- 歷史記錄表格 -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用戶名</th>
                        <th>物品名稱</th>
                        <th>類別</th>
                        <th>抽獎時間</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?php echo $record['id']; ?></td>
                        <td><?php echo htmlspecialchars($record['username']); ?></td>
                        <td><?php echo htmlspecialchars($record['itemName']); ?></td>
                        <td><?php echo htmlspecialchars($record['category']); ?></td>
                        <td><?php echo $record['drawTime']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- 分頁導航 -->
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&category=<?php echo urlencode($category); ?>&username=<?php echo urlencode($username); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                       class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </main>
    </div>
</body>
</html> 