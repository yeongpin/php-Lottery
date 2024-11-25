<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 處理新增充值選項
if (isset($_POST['add_option'])) {
    $tokens = $_POST['tokens'];
    $price = $_POST['price'];
    $bonus_tokens = $_POST['bonus_tokens'] ?? 0;
    $active = 1;
    
    $stmt = $conn->prepare("INSERT INTO recharge_options (tokens, price, bonus_tokens, active) VALUES (?, ?, ?, ?)");
    $stmt->execute([$tokens, $price, $bonus_tokens, $active]);
    
    header("Location: recharge_options.php");
    exit();
}

// 處理更新充值選項
if (isset($_POST['update_option'])) {
    $id = $_POST['id'];
    $tokens = $_POST['tokens'];
    $price = $_POST['price'];
    $bonus_tokens = $_POST['bonus_tokens'] ?? 0;
    $active = isset($_POST['active']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE recharge_options SET tokens = ?, price = ?, bonus_tokens = ?, active = ? WHERE id = ?");
    $stmt->execute([$tokens, $price, $bonus_tokens, $active, $id]);
    
    header("Location: recharge_options.php");
    exit();
}

// 處理刪除充值選項
if (isset($_POST['delete_option'])) {
    $id = $_POST['id'];
    
    $stmt = $conn->prepare("DELETE FROM recharge_options WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: recharge_options.php");
    exit();
}

// 獲取所有充值選項
$options = $conn->query("SELECT * FROM recharge_options ORDER BY tokens ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>充值選項管理 - 管理後台</title>
    <link rel="stylesheet" href="../styles/admin.css">
    <link rel="stylesheet" href="./styles/global.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/nav.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h2>充值選項管理</h2>
                <button onclick="showAddOptionModal()" class="add-btn">
                    <i class="fas fa-plus"></i> 新增選項
                </button>
            </div>

            <!-- 充值選項列表 -->
            <div class="options-grid">
                <?php foreach ($options as $option): ?>
                    <div class="option-card">
                        <div class="option-header">
                            <h3><?php echo $option['tokens']; ?> 代幣</h3>
                            <?php if ($option['bonus_tokens'] > 0): ?>
                                <span class="bonus-tag">+<?php echo $option['bonus_tokens']; ?> 贈送</span>
                            <?php endif; ?>
                        </div>
                        <div class="option-body">
                            <div class="option-info">
                                <h4>NT$ <?php echo $option['price']; ?></h4>
                                <p class="option-status <?php echo $option['active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $option['active'] ? '販售中' : '已停用'; ?>
                                </p>
                            </div>
                            <div class="option-actions">
                                <button onclick="editOption(<?php echo $option['id']; ?>)" class="edit-btn">編輯</button>
                                <form method="post" style="display: inline;" onsubmit="return confirm('確定要刪除此充值選項嗎？');">
                                    <input type="hidden" name="delete_option" value="1">
                                    <input type="hidden" name="id" value="<?php echo $option['id']; ?>">
                                    <button type="submit" class="delete-btn" style="background-color: #e74c3c;">刪除</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- 新增充值選項的 Modal -->
    <div id="addOptionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>新增充值選項</h3>
                <span class="close" onclick="closeModal('addOptionModal')">&times;</span>
            </div>
            <form method="post" class="option-form">
                <div class="form-group">
                    <label>代幣數量</label>
                    <input type="number" name="tokens" required>
                </div>
                <div class="form-group">
                    <label>價格 (TWD)</label>
                    <input type="number" name="price" required>
                </div>
                <div class="form-group">
                    <label>贈送代幣</label>
                    <input type="number" name="bonus_tokens" value="0">
                </div>
                <button type="submit" name="add_option">新增選項</button>
            </form>
        </div>
    </div>

    <!-- 編輯充值選項的 Modal -->
    <div id="editOptionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>編輯充值選項</h3>
                <span class="close" onclick="closeModal('editOptionModal')">&times;</span>
            </div>
            <form method="post" class="option-form">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>代幣數量</label>
                    <input type="number" name="tokens" id="edit_tokens" required>
                </div>
                <div class="form-group">
                    <label>價格 (TWD)</label>
                    <input type="number" name="price" id="edit_price" required>
                </div>
                <div class="form-group">
                    <label>贈送代幣</label>
                    <input type="number" name="bonus_tokens" id="edit_bonus_tokens" value="0">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="active" id="edit_active">
                        啟用選項
                    </label>
                </div>
                <button type="submit" name="update_option">更新選項</button>
            </form>
        </div>
    </div>

    <script>
    function showAddOptionModal() {
        document.getElementById('addOptionModal').style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function editOption(optionId) {
        // 從服務器獲取選項數據
        fetch(`get_option.php?id=${optionId}`)
            .then(response => response.json())
            .then(option => {
                // 填充編輯表單
                document.getElementById('edit_id').value = option.id;
                document.getElementById('edit_tokens').value = option.tokens;
                document.getElementById('edit_price').value = option.price;
                document.getElementById('edit_bonus_tokens').value = option.bonus_tokens;
                document.getElementById('edit_active').checked = option.active == 1;

                // 顯示編輯彈窗
                document.getElementById('editOptionModal').style.display = 'flex';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('獲取充值選項數據失敗');
            });
    }

    // 添加點擊外部關閉彈窗的功能
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>


</body>
</html> 