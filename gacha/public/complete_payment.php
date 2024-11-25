/*<?php
/*session_start();
/*require_once '../config/database.php';
/*require_once '../vendor/autoload.php';
/*
/*use PayPalCheckoutSdk\Core\PayPalHttpClient;
/*use PayPalCheckoutSdk\Core\SandboxEnvironment;
/*use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
/*
/*if (!isset($_SESSION['user_id']) || !isset($_GET['token'])) {
/*    header('Location: dashboard.php');
/*    exit;
/*}
/*
/*$clientId = "YOUR_PAYPAL_CLIENT_ID";
/*$clientSecret = "YOUR_PAYPAL_CLIENT_SECRET";
/*$environment = new SandboxEnvironment($clientId, $clientSecret);
/*$client = new PayPalHttpClient($environment);
/*
/*try {
/*    $db = new Database();
/*    $conn = $db->getConnection();
/*    
/*    // 獲取訂單信息
/*    $stmt = $conn->prepare("
/*        SELECT * FROM recharge_orders 
/*        WHERE paypal_order_id = ? AND status = 'pending'
/*    ");
/*    $stmt->execute([$_GET['token']]);
/*    $order = $stmt->fetch(PDO::FETCH_ASSOC);
/*    
/*    if (!$order) {
/*        throw new Exception('訂單不存在或已處理');
/*    }
/*    
/*    // 完成 PayPal 支付
/*    $request = new OrdersCaptureRequest($_GET['token']);
/*    $response = $client->execute($request);
/*    
/*    if ($response->result->status === "COMPLETED") {
/*        $conn->beginTransaction();
/*        
/*        // 更新訂單狀態
/*        $stmt = $conn->prepare("
/*            UPDATE recharge_orders 
/*            SET status = 'completed', completed_at = NOW() 
/*            WHERE id = ?
/*        ");
/*        $stmt->execute([$order['id']]);
/*        
/*        // 給用戶增加代幣
/*        $stmt = $conn->prepare("
/*            UPDATE users 
/*            SET tokens = tokens + (
/*                SELECT tokens + bonus_tokens 
/*                FROM recharge_options 
/*                WHERE id = ?
/*            ) 
/*            WHERE id = ?
/*        ");
/*        $stmt->execute([$order['option_id'], $_SESSION['user_id']]);
/*        
/*        $conn->commit();
/*        
/*        // 重定向到成功頁面
/*        header('Location: dashboard.php?recharge=success');
/*    } else {
/*        throw new Exception('支付未完成');
/*    }
/*    
/*} catch (Exception $e) {
/*    if (isset($conn)) {
/*        $conn->rollBack();
/*    }
/*    header('Location: dashboard.php?recharge=failed');
/*} 