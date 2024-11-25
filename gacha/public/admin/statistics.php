<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 獲取時間範圍
$period = isset($_GET['period']) ? $_GET['period'] : 'today';
$custom_start = isset($_GET['start']) ? $_GET['start'] : '';
$custom_end = isset($_GET['end']) ? $_GET['end'] : '';

// 根據時間範圍設置查詢條件
switch($period) {
    case 'today':
        $start_date = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 23:59:59');
        break;
    case 'week':
        $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $end_date = date('Y-m-d 23:59:59');
        break;
    case 'month':
        $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
        $end_date = date('Y-m-d 23:59:59');
        break;
    case 'custom':
        $start_date = $custom_start . ' 00:00:00';
        $end_date = $custom_end . ' 23:59:59';
        break;
    default:
        $start_date = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 23:59:59');
}

// 在查詢類別統計之前，先獲取所有類別的顏色
$category_colors = [];
$stmt = $conn->prepare("SELECT name, color FROM categories");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $category_colors[$row['name']] = $row['color'];
}

// 獲取統計數據
$stats = [
    // 總抽獎次數
    'total_draws' => $conn->query("
        SELECT COUNT(*) FROM draw_history 
        WHERE drawTime BETWEEN '$start_date' AND '$end_date'
    ")->fetchColumn(),
    
    // 活躍用戶數
    'active_users' => $conn->query("
        SELECT COUNT(DISTINCT userId) FROM draw_history 
        WHERE drawTime BETWEEN '$start_date' AND '$end_date'
    ")->fetchColumn(),
    
    // 消耗代幣數
    'tokens_spent' => $conn->query("
        SELECT COUNT(*) FROM draw_history 
        WHERE drawTime BETWEEN '$start_date' AND '$end_date'
    ")->fetchColumn(),
    
    // 各類別抽中統計
    'category_stats' => $conn->query("
        SELECT category, COUNT(*) as count,
        (COUNT(*) * 100.0 / (
            SELECT COUNT(*) FROM draw_history 
            WHERE drawTime BETWEEN '$start_date' AND '$end_date'
        )) as percentage
        FROM draw_history 
        WHERE drawTime BETWEEN '$start_date' AND '$end_date'
        GROUP BY category
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC),
    
    // 每日抽獎趨勢
    'daily_trend' => $conn->query("
        SELECT DATE(drawTime) as date, COUNT(*) as count
        FROM draw_history 
        WHERE drawTime BETWEEN '$start_date' AND '$end_date'
        GROUP BY DATE(drawTime)
        ORDER BY date
    ")->fetchAll(PDO::FETCH_ASSOC)
];
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>統計報表 - 管理後台</title>
    <link rel="stylesheet" href="../styles/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/nav.php'; ?>
        
        <main class="admin-content">
            <h2>統計報表</h2>

            <!-- 時間範圍選擇 -->
            <div class="filter-form">
                <form method="get">
                    <select name="period" onchange="this.form.submit()">
                        <option value="today" <?php echo $period == 'today' ? 'selected' : ''; ?>>今天</option>
                        <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>最近7天</option>
                        <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>最近30天</option>
                        <option value="custom" <?php echo $period == 'custom' ? 'selected' : ''; ?>>自定義</option>
                    </select>
                    <?php if($period == 'custom'): ?>
                        <input type="date" name="start" value="<?php echo $custom_start; ?>" required>
                        <input type="date" name="end" value="<?php echo $custom_end; ?>" required>
                    <?php endif; ?>
                </form>
            </div>

            <!-- 概覽數據 -->
            <div class="stats-overview">
                <div class="stat-card">
                    <h3>總抽獎次數</h3>
                    <p><?php echo $stats['total_draws']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>活躍用戶數</h3>
                    <p><?php echo $stats['active_users']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>消耗代幣數</h3>
                    <p><?php echo $stats['tokens_spent']; ?></p>
                </div>
            </div>

            <!-- 圖表區域 -->
            <div class="stats-container">
                <div class="chart-box">
                    <h3>類別分布</h3>
                    <div style="width: 300px; height: 300px; margin: 0 auto;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- 詳細數據表格 -->
            <div class="detailed-stats">
                <h3>類別詳細統計</h3>
                <table>
                    <thead>
                        <tr>
                            <th>類別</th>
                            <th>抽中次數</th>
                            <th>佔比</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['category_stats'] as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['category']); ?></td>
                            <td><?php echo $stat['count']; ?></td>
                            <td><?php echo number_format($stat['percentage'], 2); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // 繪製類別分布圓餅圖
        const ctx = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($stats['category_stats'], 'category')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($stats['category_stats'], 'count')); ?>,
                    backgroundColor: <?php echo json_encode(array_values($category_colors)); ?>
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: '抽獎類別分布',
                        font: {
                            size: 14
                        }
                    }
                }
            }
        });

        // 繪製每日趨勢折線圖
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($stats['daily_trend'], 'date')); ?>,
                datasets: [{
                    label: '抽獎次數',
                    data: <?php echo json_encode(array_column($stats['daily_trend'], 'count')); ?>,
                    borderColor: '#36A2EB',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html> 