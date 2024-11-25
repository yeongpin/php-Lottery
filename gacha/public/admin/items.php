<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 處理新增物品
if (isset($_POST['add_item'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $probability = $_POST['probability'] ? $_POST['probability'] / 100 : null; // 如果沒填則為 null
    
    $stmt = $conn->prepare("INSERT INTO items (name, category, probability) VALUES (?, ?, ?)");
    $stmt->execute([$name, $category, $probability]);
    
    header("Location: items.php");
    exit();
}

// 處理更新物品
if (isset($_POST['update_item'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $probability = $_POST['probability'] ? $_POST['probability'] / 100 : null; // 如果沒填則為 null
    
    $stmt = $conn->prepare("UPDATE items SET name = ?, category = ?, probability = ? WHERE id = ?");
    $stmt->execute([$name, $category, $probability, $id]);
    
    header("Location: items.php");
    exit();
}

// 獲取所有類別及其預設機率
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// 獲取所有物品，包括類別的預設機率
$stmt = $conn->prepare("
    SELECT i.*, c.defaultProbability, c.color 
    FROM items i 
    LEFT JOIN categories c ON i.category = c.name 
    ORDER BY i.category, i.name
");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>物品管理 - 管理後台</title>
    <link rel="stylesheet" href="../styles/admin.css">
    <link rel="stylesheet" href="./styles/global.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/nav.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h2>物品管理</h2>
                <button onclick="showAddItemModal()" class="add-btn">
                    <i class="fas fa-plus"></i> 新增物品
                </button>
            </div>

            <!-- 物品列表 -->
            <div class="items-grid">
                <?php foreach ($items as $item): 
                    // 獲取類別顏色
                    $stmt = $conn->prepare("SELECT color FROM categories WHERE name = ?");
                    $stmt->execute([$item['category']]);
                    $categoryColor = $stmt->fetchColumn() ?? '#666666';
                ?>
                    <div class="item-card">
                        <div class="item-header" style="background: <?php echo $categoryColor; ?>">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <span class="item-category"><?php echo $item['category']; ?></span>
                        </div>
                        <div class="item-body">
                            <div class="item-info">
                                <div class="probability-setting">
                                    <label>物品機率:</label>
                                    <span><?php 
                                        if ($item['probability'] !== null) {
                                            echo ($item['probability'] * 100) . '%';
                                        } else {
                                            echo ($item['defaultProbability'] * 100) . '% (繼承類別)';
                                        }
                                    ?></span>
                                </div>
                            </div>
                            <div class="item-actions">
                                <button onclick="editItem(<?php echo $item['id']; ?>)" class="edit-btn">編輯</button>
                                <form method="post" style="display: inline;" onsubmit="return confirm('確定要刪除此物品嗎？');">
                                    <input type="hidden" name="delete_item" value="1">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="delete-btn">刪除</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- 新增物品的 Modal -->
    <div id="addItemModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>新增物品</h3>
                <span class="close" onclick="closeModal('addItemModal')">&times;</span>
            </div>
            <form method="post" class="item-form">
                <div class="form-group">
                    <label>物品名稱</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>物品類別</label>
                    <select name="category" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['name']; ?>">
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>物品機率 (%)</label>
                    <input type="number" name="probability" 
                           step="0.0001" min="0" max="100" value="0" required>
                </div>
                <button type="submit" name="add_item">新增物品</button>
            </form>
        </div>
    </div>

    <!-- 編輯物品的 Modal -->
    <div id="editItemModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>編輯物品</h3>
                <span class="close" onclick="closeModal('editItemModal')">&times;</span>
            </div>
            <form method="post" class="item-form">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>物品名稱</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>物品類別</label>
                    <select name="category" id="edit_category" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['name']; ?>">
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>物品機率 (%) (留空則使用類別預設機率)</label>
                    <input type="number" name="probability" id="edit_probability" 
                           step="0.0001" min="0" max="100" 
                           value="<?php echo $item['probability'] !== null ? ($item['probability'] * 100) : ''; ?>"
                           placeholder="使用類別預設機率">
                </div>
                <button type="submit" name="update_item">更新物品</button>
            </form>
        </div>
    </div>



    <script>
    function showAddItemModal() {
        document.getElementById('addItemModal').style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function editItem(itemId) {
        fetch(`get_item.php?id=${itemId}`)
            .then(response => response.json())
            .then(item => {
                document.getElementById('edit_id').value = item.id;
                document.getElementById('edit_name').value = item.name;
                document.getElementById('edit_category').value = item.category;
                document.getElementById('editItemModal').style.display = 'flex';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('獲取物品數據失敗');
            });
    }

    // 點擊外部關閉彈窗
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>

    <style>
    /* 物品卡片樣式 */
    .items-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
    }

    .item-card {
        background: #f8f9fa;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .item-header {
        color: white;
        padding: 15px;
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .item-header h3 {
        margin: 0;
        font-size: 1.2em;
        font-weight: 500;
    }

    .item-category {
        padding: 4px 8px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
        font-size: 0.9em;
    }

    .item-body {
        padding: 15px;
    }

    .item-info {
        margin-bottom: 15px;
    }

    .probability-setting {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.1em;
        color: var(--text-color);
    }

    .probability-setting label {
        font-weight: 500;
    }

    .probability-setting span {
        color: var(--primary-color);
        font-weight: 600;
    }

    .item-actions {
        display: flex;
        gap: 10px;
    }

    .item-actions button {
        flex: 1;
        padding: 8px 0;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
        color: white;
    }

    .edit-btn {
        background: var(--primary-color);
    }

    .delete-btn {
        background: #e74c3c !important;
    }

    /* 深色模式樣式 */
    .dark-mode .item-card {
        background: #2d2d2d;
        border-color: rgba(255, 255, 255, 0.1);
    }

    .dark-mode .probability-setting {
        color: white;
    }

    .dark-mode .probability-setting span {
        color: var(--accent-color);
    }

    /* 動畫效果 */
    .item-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .item-actions button:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }
    </style>
</body>
</html> 