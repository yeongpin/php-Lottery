<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 處理新增任務
if (isset($_POST['add_task'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $reward_tokens = $_POST['reward_tokens'];
    $type = $_POST['type'];
    $url = $_POST['url'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO tasks (name, description, reward_tokens, type, url) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $reward_tokens, $type, $url]);
}

// 獲取所有任務
$tasks = $conn->query("SELECT * FROM tasks ORDER BY type, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>任務管理 - 管理後台</title>
    <link rel="stylesheet" href="../styles/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/nav.php'; ?>
        
        <main class="admin-content">
            <h2>任務管理</h2>

            <!-- 新增任務表單 -->
            <div class="add-task-form">
                <h3>新增任務</h3>
                <form method="post">
                    <div class="form-group">
                        <label>任務名稱</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>任務描述</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>獎勵代幣數</label>
                        <input type="number" name="reward_tokens" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>任務類型</label>
                        <select name="type" required>
                            <option value="daily">每日任務</option>
                            <option value="monthly">每月任務</option>
                            <option value="one_time">限定一次任務</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>任務連結（選填）</label>
                        <input type="url" name="url">
                    </div>
                    <button type="submit" name="add_task">新增任務</button>
                </form>
            </div>

            <!-- 任務列表 -->
            <div class="tasks-list">
                <h3>現有任務</h3>
                <div class="tasks-grid">
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-item">
                            <h4><?php echo htmlspecialchars($task['name']); ?></h4>
                            <p><?php echo htmlspecialchars($task['description']); ?></p>
                            <div class="task-info">
                                <span class="task-type"><?php echo $task['type']; ?></span>
                                <span class="task-reward"><?php echo $task['reward_tokens']; ?> 代幣</span>
                            </div>
                            <?php if ($task['url']): ?>
                                <a href="<?php echo htmlspecialchars($task['url']); ?>" target="_blank" class="task-url">任務連結</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 