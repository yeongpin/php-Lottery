<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 處理新增類別
if (isset($_POST['add_category'])) {
    $name = $_POST['name'];
    $probability = $_POST['probability'];
    $color = $_POST['color'] ?? '#666666';
    
    $stmt = $conn->prepare("INSERT INTO categories (name, defaultProbability, color) VALUES (?, ?, ?)");
    $stmt->execute([$name, $probability, $color]);
}

// 處理更新類別
if (isset($_POST['update_category'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $probability = $_POST['probability'];
    $color = $_POST['color'] ?? '#666666';
    
    $stmt = $conn->prepare("UPDATE categories SET name = ?, defaultProbability = ?, color = ? WHERE id = ?");
    $stmt->execute([$name, $probability, $color, $id]);
}

// 獲取所有類別
$categories = $conn->query("SELECT * FROM categories ORDER BY defaultProbability DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>類別管理 - 管理後台</title>
    <link rel="stylesheet" href="../styles/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/nav.php'; ?>
        
        <main class="admin-content">
            <h2>類別管理</h2>

            <!-- 新增類別表單 -->
            <div class="add-category-form">
                <h3>新增類別</h3>
                <form method="post" class="category-form">
                    <div class="form-group">
                        <label>類別名稱</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>基礎機率</label>
                        <input type="number" name="probability" step="0.01" min="0" max="1" required>
                    </div>
                    <div class="form-group">
                        <label>類別顏色</label>
                        <input type="color" name="color" required>
                    </div>
                    <button type="submit" name="add_category">新增類別</button>
                </form>
            </div>

            <!-- 類別列表 -->
            <div class="categories-list">
                <h3>現有類別</h3>
                <div class="category-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-item" style="border-left: 4px solid <?php echo $category['color']; ?>">
                            <form method="post" class="category-form">
                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                <div class="form-group">
                                    <label>類別名稱</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>基礎機率</label>
                                    <input type="number" name="probability" step="0.01" min="0" max="1" 
                                           value="<?php echo $category['defaultProbability']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>類別顏色</label>
                                    <input type="color" name="color" value="<?php echo $category['color']; ?>" required>
                                </div>
                                <button type="submit" name="update_category">更新</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <style>
    .category-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .category-item {
        background: white;
        border-radius: 10px;
        transition: transform 0.3s ease;
    }

    .category-item:hover {
        transform: translateY(-5px);
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    input[type="color"] {
        width: 100%;
        height: 40px;
        padding: 5px;
        border: 1px solid var(--border-color);
        border-radius: 5px;
    }
    </style>
</body>
</html> 