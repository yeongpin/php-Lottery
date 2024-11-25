<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => '未授權']);
    exit;
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => '缺少選項ID']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare("SELECT * FROM recharge_options WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $option = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($option) {
        header('Content-Type: application/json');
        echo json_encode($option);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => '充值選項不存在']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
} 