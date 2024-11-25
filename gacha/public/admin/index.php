<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 獲取基本統計數據
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_draws' => $conn->query("SELECT COUNT(*) FROM draw_history")->fetchColumn(),
    'total_items' => $conn->query("SELECT COUNT(*) FROM items")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理後台 - 儀表板</title>
    <link rel="stylesheet" href="../styles/admin.css">
</head>
<body>
    <div class="admin-container">
    <?php include 'includes/nav.php'; ?>

        <main class="admin-content">
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>總用戶數</h3>
                    <p><?php echo $stats['total_users']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>總抽獎次數</h3>
                    <p><?php echo $stats['total_draws']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>總物品數</h3>
                    <p><?php echo $stats['total_items']; ?></p>
                </div>
            </div>

            <div class="recent-activities">
                <h2>最近活動</h2>
                <table>
                    <thead>
                        <tr>
                            <th>用戶</th>
                            <th>物品</th>
                            <th>類別</th>
                            <th>時間</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("
                            SELECT u.username, d.itemName, d.category, d.drawTime
                            FROM draw_history d
                            JOIN users u ON d.userId = u.id
                            ORDER BY d.drawTime DESC
                            LIMIT 10
                        ");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['itemName']); ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo $row['drawTime']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html> 