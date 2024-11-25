<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 處理用戶代幣修改
if (isset($_POST['update_tokens'])) {
    $userId = $_POST['user_id'];
    $tokens = $_POST['tokens'];
    $stmt = $conn->prepare("UPDATE users SET tokens = ? WHERE id = ?");
    $stmt->execute([$tokens, $userId]);
    header("Location: users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用戶管理 - 管理後台</title>
    <link rel="stylesheet" href="../styles/admin.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/nav.php'; ?>
        
        <main class="admin-content">
            <h2>用戶管理</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用戶名</th>
                        <th>代幣數量</th>
                        <th>註冊時間</th>
                        <th>最後登入</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("SELECT * FROM users ORDER BY id DESC");
                    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="number" name="tokens" value="<?php echo $user['tokens']; ?>" min="0">
                                <button type="submit" name="update_tokens" class="small-btn">更新</button>
                            </form>
                        </td>
                        <td><?php echo $user['createdAt']; ?></td>
                        <td><?php echo $user['updatedAt']; ?></td>
                        <td>
                            <a href="user_history.php?id=<?php echo $user['id']; ?>" class="btn-link">查看歷史</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html> 