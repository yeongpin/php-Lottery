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
    $reset_time = $_POST['reset_time'] ?? '00:00:00';
    $active = 1;
    
    $stmt = $conn->prepare("INSERT INTO tasks (name, description, reward_tokens, type, url, reset_time, active) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $reward_tokens, $type, $url, $reset_time, $active]);
    
    header("Location: tasks_manage.php");
    exit();
}

// 處理更新任務
if (isset($_POST['update_task'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $reward_tokens = $_POST['reward_tokens'];
    $type = $_POST['type'];
    $url = $_POST['url'] ?? null;
    $reset_time = $_POST['reset_time'] ?? '00:00:00';
    $active = isset($_POST['active']) ? 1 : 0;
    
    try {
        $stmt = $conn->prepare("UPDATE tasks SET name = ?, description = ?, reward_tokens = ?, type = ?, url = ?, reset_time = ?, active = ? WHERE id = ?");
        $stmt->execute([$name, $description, $reward_tokens, $type, $url, $reset_time, $active, $id]);
        
        header("Location: tasks_manage.php");
        exit();
    } catch (Exception $e) {
        error_log('Error updating task: ' . $e->getMessage());
    }
}

// 處理刪除任務
if (isset($_POST['delete_task'])) {
    $id = $_POST['id'];
    
    try {
        $conn->beginTransaction();
        
        // 先刪除相關的任務完成記錄
        $stmt = $conn->prepare("DELETE FROM user_task_completions WHERE task_id = ?");
        $stmt->execute([$id]);
        
        // 然後刪除任務本身
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        
        $conn->commit();
        
        header("Location: tasks_manage.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        error_log('Error deleting task: ' . $e->getMessage());
    }
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
    <link rel="stylesheet" href="./styles/global.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/nav.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h2>任務管理</h2>
                <button onclick="showAddTaskModal()" class="add-btn">
                    <i class="fas fa-plus"></i> 新增任務
                </button>
            </div>

            <!-- 任務列表 -->
            <div class="tasks-grid">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card" data-id="<?php echo $task['id']; ?>">
                        <div class="task-header">
                            <h3><?php echo htmlspecialchars($task['name']); ?></h3>
                            <span class="task-type"><?php echo $task['type']; ?></span>
                        </div>
                        <div class="task-body">
                            <p class="task-description"><?php echo htmlspecialchars($task['description']); ?></p>
                            <div class="task-details">
                                <span class="task-reward">獎勵: <?php echo $task['reward_tokens']; ?> 代幣</span>
                                <span class="task-status <?php echo $task['active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $task['active'] ? '啟用中' : '已停用'; ?>
                                </span>
                            </div>
                            <?php if ($task['url']): ?>
                                <div class="task-url">
                                    <a href="<?php echo htmlspecialchars($task['url']); ?>" target="_blank">任務連結</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="task-actions">
                            <button onclick="editTask(<?php echo $task['id']; ?>)" class="edit-btn">編輯</button>
                            <form method="post" style="display: inline;" onsubmit="return confirm('確定要刪除此任務嗎？');">
                                <input type="hidden" name="delete_task" value="1">
                                <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                                <button type="submit" class="delete-btn">刪除</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- 新增任務的 Modal -->
    <div id="addTaskModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>新增任務</h3>
                <span class="close" onclick="closeModal('addTaskModal')">&times;</span>
            </div>
            <form method="post" class="task-form">
                <div class="form-group">
                    <label>任務名稱</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>任務描述</label>
                    <textarea name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label>獎勵代幣</label>
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
                <div class="form-group">
                    <label>重置時間 (UTC)</label>
                    <input type="time" name="reset_time" value="00:00:00" required>
                </div>
                <button type="submit" name="add_task">新增任務</button>
            </form>
        </div>
    </div>

    <!-- 編輯任務的 Modal -->
    <div id="editTaskModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>編輯任務</h3>
                <span class="close" onclick="closeModal('editTaskModal')">&times;</span>
            </div>
            <form method="post" class="task-form" id="editTaskForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>任務名稱</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>任務描述</label>
                    <textarea name="description" id="edit_description" required></textarea>
                </div>
                <div class="form-group">
                    <label>獎勵代幣</label>
                    <input type="number" name="reward_tokens" id="edit_reward_tokens" min="1" required>
                </div>
                <div class="form-group">
                    <label>任務類型</label>
                    <select name="type" id="edit_type" required>
                        <option value="daily">每日任務</option>
                        <option value="monthly">每月任務</option>
                        <option value="one_time">限定一次任務</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>任務連結（選填）</label>
                    <input type="url" name="url" id="edit_url">
                </div>
                <div class="form-group">
                    <label>重置時間 (UTC)</label>
                    <input type="time" name="reset_time" id="edit_reset_time" value="00:00:00" required>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="active" id="edit_active">
                        啟用任務
                    </label>
                </div>
                <button type="submit" name="update_task">更新任務</button>
            </form>
        </div>
    </div>



    <script>
    function showAddTaskModal() {
        document.getElementById('addTaskModal').style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function editTask(taskId) {
        // 從服務器獲取任務數據
        fetch(`get_task.php?id=${taskId}`)
            .then(response => response.json())
            .then(task => {
                // 填充編輯表單
                document.getElementById('edit_id').value = task.id;
                document.getElementById('edit_name').value = task.name;
                document.getElementById('edit_description').value = task.description;
                document.getElementById('edit_reward_tokens').value = task.reward_tokens;
                document.getElementById('edit_type').value = task.type;
                document.getElementById('edit_url').value = task.url || '';
                document.getElementById('edit_reset_time').value = task.reset_time || '00:00:00';
                document.getElementById('edit_active').checked = task.active == 1;

                // 顯示編輯彈窗
                document.getElementById('editTaskModal').style.display = 'flex';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('獲取任務數據失敗');
            });
    }

    function deleteTask(taskId) {
        if (confirm('確定要刪除此任務嗎？')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="delete_task" value="1">
                <input type="hidden" name="id" value="${taskId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html> 