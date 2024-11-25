<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: categories.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 獲取類別信息
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$_GET['id']]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header("Location: categories.php");
    exit();
}

// 處理新增物品
if (isset($_POST['add_item'])) {
    $name = $_POST['name'];
    $probability = $_POST['probability'] ?: null;
    
    $stmt = $conn->prepare("INSERT INTO items (name, category, probability) VALUES (?, ?, ?)");
    $stmt->execute([$name, $category['name'], $probability]);
    header("Location: category_items.php?id=" . $_GET['id']);
    exit();
}

// 處理刪除物品
if (isset($_POST['delete_item'])) {
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ? AND category = ?");
    $stmt->execute([$_POST['item_id'], $category['name']]);
    header("Location: category_items.php?id=" . $_GET['id']);
    exit();
}

// 處理更新物品概率
if (isset($_POST['update_probability'])) {
    $stmt = $conn->prepare("UPDATE items SET probability = ? WHERE id = ?");
    $stmt->execute([$_POST['probability'], $_POST['item_id']]);
    header("Location: category_items.php?id=" . $_GET['id']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - 物品列表</title>
    <link rel="stylesheet" href="../styles/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/nav.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h2><?php echo htmlspecialchars($category['name']); ?> - 物品列表</h2>
                <button onclick="showAddForm()" class="add-btn">新增物品</button>
            </div>

            <div class="category-info">
                <p>默認概率：<?php echo $category['defaultProbability']; ?></p>
            </div>

            <!-- 新增物品表單 -->
            <div id="addItemForm" class="modal" style="display: none;">
                <div class="modal-content">
                    <h3>新增物品</h3>
                    <form method="post">
                        <div class="form-group">
                            <label>物品名稱</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>自定義概率（可選）</label>
                            <input type="number" name="probability" step="0.0001" min="0" max="1">
                            <small>留空則使用類別默認概率</small>
                        </div>
                        <button type="submit" name="add_item">新增</button>
                        <button type="button" onclick="hideAddForm()">取消</button>
                    </form>
                </div>
            </div>

            <!-- 物品列表 -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>名稱</th>
                        <th>概率</th>
                        <th>創建時間</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM items WHERE category = ? ORDER BY id DESC");
                    $stmt->execute([$category['name']]);
                    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="probability" 
                                    value="<?php echo $item['probability'] ?: $category['defaultProbability']; ?>" 
                                    step="0.0001" min="0" max="1">
                                <button type="submit" name="update_probability" class="small-btn">更新</button>
                            </form>
                        </td>
                        <td><?php echo $item['createdAt']; ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="delete_item" class="delete-btn"
                                    onclick="return confirm('確定要刪除這個物品嗎？')">刪除</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </main>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('addItemForm').style.display = 'block';
        }

        function hideAddForm() {
            document.getElementById('addItemForm').style.display = 'none';
        }
    </script>
</body>
</html> 