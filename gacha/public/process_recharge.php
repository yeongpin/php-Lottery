<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => '請先登入']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$optionId = $data['optionId'] ?? null;

if (!$optionId) {
    echo json_encode(['error' => '無效的充值選項']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();
    
    // 獲取充值選項詳情
    $stmt = $conn->prepare("SELECT * FROM recharge_options WHERE id = ? AND active = 1");
    $stmt->execute([$optionId]);
    $option = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$option) {
        throw new Exception('無效的充值選項');
    }
    
    // 計算總代幣數（包含贈送）
    $totalTokens = $option['tokens'] + $option['bonus_tokens'];
    
    // 更新用戶代幣
    $stmt = $conn->prepare("UPDATE users SET tokens = tokens + ? WHERE id = ?");
    $stmt->execute([$totalTokens, $_SESSION['user_id']]);
    
    // 記錄充值歷史
    $stmt = $conn->prepare("
        INSERT INTO recharge_history (user_id, option_id, amount, tokens, bonus_tokens) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $option['id'],
        $option['price'],
        $option['tokens'],
        $option['bonus_tokens']
    ]);
    
    // 獲取用戶最新代幣數
    $stmt = $conn->prepare("SELECT tokens FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $newTokens = $stmt->fetchColumn();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "充值成功！獲得 {$option['tokens']} 代幣" . 
                    ($option['bonus_tokens'] > 0 ? " + {$option['bonus_tokens']} 贈送代幣" : ""),
        'newTokens' => $newTokens
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
} 