/*<?php
/*session_start();
/*require_once '../config/database.php';
/*require_once '../vendor/autoload.php';  // 需要安裝 PayPal SDK
/*
/*use PayPalCheckoutSdk\Core\PayPalHttpClient;
/*use PayPalCheckoutSdk\Core\SandboxEnvironment;
/*use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
/*
/*if (!isset($_SESSION['user_id'])) {
/*    header('Content-Type: application/json');
/*    echo json_encode(['error' => '請先登入']);
/*    exit;
/*}
/*
/*$data = json_decode(file_get_contents('php://input'), true);
/*$optionId = $data['optionId'] ?? null;
/*$amount = $data['amount'] ?? null;
/*
/*if (!$optionId || !$amount) {
/*    echo json_encode(['error' => '無效的請求']);
/*    exit;
/*}
/*
/* PayPal 配置
/*$clientId = "YOUR_PAYPAL_CLIENT_ID";
/*$clientSecret = "YOUR_PAYPAL_CLIENT_SECRET";
/*$environment = new SandboxEnvironment($clientId, $clientSecret);
/*$client = new PayPalHttpClient($environment);
/*
/*try {
/*    $request = new OrdersCreateRequest();
/*    $request->prefer('return=representation');
/*    
/*    $request->body = [
/*        "intent" => "CAPTURE",
/*        "purchase_units" => [[
/*            "amount" => [
/*                "currency_code" => "TWD",
/*                "value" => $amount
/*            ]
/*        ]],
/*        "application_context" => [
/*            "cancel_url" => "http://yourdomain.com/cancel_payment.php",
/*            "return_url" => "http://yourdomain.com/complete_payment.php?option_id=" . $optionId,
/*            "brand_name" => "您的網站名稱",
/*            "locale" => "zh-TW",
/*            "landing_page" => "LOGIN",
/*            "user_action" => "PAY_NOW",
/*            "shipping_preference" => "NO_SHIPPING"
/*        ]
/*    ];
/*
/*    $response = $client->execute($request);
/*    
/*    // 保存訂單信息到數據庫
/*    $db = new Database();
/*    $conn = $db->getConnection();
/*    
/*    $stmt = $conn->prepare("
/*        INSERT INTO recharge_orders (user_id, option_id, paypal_order_id, amount, status) 
/*        VALUES (?, ?, ?, ?, 'pending')
/*    ");
/*    $stmt->execute([
/*        $_SESSION['user_id'],
/*        $optionId,
/*        $response->result->id,
/*        $amount
/*    ]);
/*
/*    // 返回 PayPal 支付頁面 URL
/*    foreach ($response->result->links as $link) {
/*        if ($link->rel === "approve") {
/*            echo json_encode(['approvalUrl' => $link->href]);
/*            exit;
/*        }
/*    }
/*    
/*} catch (Exception $e) {
/*    echo json_encode(['error' => $e->getMessage()]);
/*} 