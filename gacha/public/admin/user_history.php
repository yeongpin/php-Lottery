<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 獲取用戶信息
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: users.php");
    exit();
}

// 在查詢用戶統計之前，先獲取所有類別的顏色
$category_colors = [];
$stmt = $conn->prepare("SELECT name, color FROM categories");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $category_colors[$row['name']] = $row['color'];
}

// 獲取抽獎統計
$stmt = $conn->prepare("
    SELECT 
        category,
        COUNT(*) as count,
        (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM draw_history WHERE userId = ?)) as percentage
    FROM draw_history 
    WHERE userId = ?
    GROUP BY category
    ORDER BY count DESC
");
$stmt->execute([$_GET['id'], $_GET['id']]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 分頁設置
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 獲取總記錄數
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM draw_history WHERE userId = ?");
$stmt->execute([$_GET['id']]);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $per_page);

// 獲取抽獎歷史 - 修改這部分的查詢
$stmt = $conn->prepare("
    SELECT dh.*, c.defaultProbability 
    FROM draw_history dh
    LEFT JOIN categories c ON dh.category = c.name
    WHERE dh.userId = ? 
    ORDER BY dh.drawTime DESC 
    LIMIT " . $offset . ", " . $per_page
);
$stmt->execute([$_GET['id']]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用戶抽獎歷史 - <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="stylesheet" href="../styles/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/nav.php'; ?>
        
        <main class="admin-content">
            <div class="user-header">
                <h2>用戶：<?php echo htmlspecialchars($user['username']); ?></h2>
                <div class="user-info">
                    <p>註冊時間：<?php echo $user['createdAt']; ?></p>
                    <p>當前代幣：<?php echo $user['tokens']; ?></p>
                    <p>總抽獎次數：<?php echo $total_records; ?></p>
                </div>
            </div>

            <div class="stats-container">
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="stats-table">
                    <h3>抽獎統計</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>類別</th>
                                <th>次數</th>
                                <th>百分比</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats as $stat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stat['category']); ?></td>
                                <td><?php echo $stat['count']; ?></td>
                                <td><?php echo number_format($stat['percentage'], 2); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="history-container">
                <h3>抽獎歷史</h3>
                <table>
                    <thead>
                        <tr>
                            <th>時間</th>
                            <th>物品</th>
                            <th>類別</th>
                            <th>類別基礎概率</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $record): ?>
                        <tr class="<?php echo strtolower($record['category']); ?>">
                            <td><?php echo $record['drawTime']; ?></td>
                            <td><?php echo htmlspecialchars($record['itemName']); ?></td>
                            <td><?php echo htmlspecialchars($record['category']); ?></td>
                            <td><?php echo ($record['defaultProbability'] * 100) . '%'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- 分頁導航 -->
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?id=<?php echo $_GET['id']; ?>&page=<?php echo $i; ?>" 
                           class="<?php echo $page == $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // 繪製圓餅圖
        const ctx = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($stats, 'category')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($stats, 'count')); ?>,
                    backgroundColor: <?php echo json_encode(array_map(function($category) use ($category_colors) {
                        return $category_colors[$category['category']] ?? '#666666';
                    }, $stats)); ?>
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    title: {
                        display: true,
                        text: '抽獎類別分布'
                    }
                }
            }
        });
    </script>
</body>
</html> 