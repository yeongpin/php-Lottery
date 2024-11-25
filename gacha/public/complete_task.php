<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => '請先登入']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$taskId = $data['taskId'] ?? null;
$userId = $_SESSION['user_id'];

if (!$taskId) {
    echo json_encode(['error' => '無效的任務']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();
    
    // 檢查任務是否存在且可完成
    $stmt = $conn->prepare("
        SELECT * FROM tasks 
        WHERE id = ? AND active = 1
    ");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        throw new Exception('任務不存在或已停用');
    }
    
    // 檢查是否已經完成
    $stmt = $conn->prepare("
        SELECT * FROM user_task_completions 
        WHERE task_id = ? AND user_id = ?
        AND (
            CASE 
                WHEN ? = 'daily' THEN 
                    DATE(completed_at) = CURRENT_DATE 
                    AND TIME(completed_at) >= (
                        SELECT reset_time 
                        FROM tasks 
                        WHERE id = ?
                    )
                WHEN ? = 'monthly' THEN DATE_FORMAT(completed_at, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')
                ELSE 1=1
            END
        )
    ");
    $stmt->execute([$taskId, $userId, $task['type'], $taskId, $task['type']]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception('任務已完成');
    }
    
    // 記錄完成狀態
    $stmt = $conn->prepare("
        INSERT INTO user_task_completions (user_id, task_id) 
        VALUES (?, ?)
    ");
    $stmt->execute([$userId, $taskId]);
    
    // 發放獎勵
    $stmt = $conn->prepare("
        UPDATE users 
        SET tokens = tokens + ? 
        WHERE id = ?
    ");
    $stmt->execute([$task['reward_tokens'], $userId]);
    
    // 獲取更新後的代幣數量
    $stmt = $conn->prepare("SELECT tokens FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $newTokens = $stmt->fetchColumn();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'reward_tokens' => $task['reward_tokens'],
        'newTokens' => $newTokens
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
?> 