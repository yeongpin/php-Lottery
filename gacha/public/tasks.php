<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

// 獲取任務列表
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? 'daily';
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['error' => '請先登入']);
        exit;
    }
    
    try {
        // 先檢查是否有任務存在
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE type = ? AND active = 1 AND deleted_at IS NULL");
        $checkStmt->execute([$type]);
        $taskCount = $checkStmt->fetchColumn();
        
        error_log("Task count for type $type: $taskCount");  // 調試信息
        
        // 獲取指定類型的任務和完成狀態
        $stmt = $conn->prepare("
            SELECT t.*, 
                   CASE 
                       WHEN t.type = 'daily' THEN DATE(utc.completed_at) = CURRENT_DATE
                       WHEN t.type = 'monthly' THEN DATE_FORMAT(utc.completed_at, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')
                       ELSE utc.completed_at IS NOT NULL
                   END as completed
            FROM tasks t
            LEFT JOIN user_task_completions utc ON t.id = utc.task_id 
                AND utc.user_id = ?
            WHERE t.type = ? 
            AND t.active = 1 
            AND t.deleted_at IS NULL
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$userId, $type]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 轉換任務類型名稱
        switch($type) {
            case '每日任務':
                $type = 'daily';
                break;
            case '每月任務':
                $type = 'monthly';
                break;
            case '限定任務':
                $type = 'one_time';
                break;
        }
        
        echo json_encode([
            'success' => true, 
            'tasks' => $tasks,
            'debug' => [
                'type' => $type,
                'userId' => $userId,
                'taskCount' => $taskCount
            ]
        ]);
    } catch (Exception $e) {
        error_log('Error in tasks.php: ' . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?> 