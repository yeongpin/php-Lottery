<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 獲取用戶信息
$stmt = $conn->prepare("SELECT username, tokens, lastClaim FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 在頁面頂部獲取類別顏色
$category_colors = [];
$stmt = $conn->prepare("SELECT name, color FROM categories");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $category_colors[$row['name']] = $row['color'];
}

// 處理每日領取
if (isset($_POST['claim_daily'])) {
    $lastClaim = new DateTime($user['lastClaim'] ?? '2000-01-01');
    $now = new DateTime();
    
    if ($lastClaim->format('Y-m-d') != $now->format('Y-m-d')) {
        $stmt = $conn->prepare("UPDATE users SET tokens = tokens + 5, lastClaim = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user['tokens'] += 5;
        header("Location: dashboard.php");
        exit();
    }
}

// 修改抽獎處理部分
if (isset($_POST['draw'])) {
    $draws = (int)$_POST['draw'];
    $cost = $draws;
    
    if ($user['tokens'] >= $cost) {
        // 執行抽獎邏輯並返回 JSON
        header('Content-Type: application/json');
        
        try {
            $conn->beginTransaction();
            
            // 扣除代幣
            $stmt = $conn->prepare("UPDATE users SET tokens = tokens - ? WHERE id = ?");
            $stmt->execute([$cost, $_SESSION['user_id']]);
            
            // 執行抽獎
            $results = [];
            for ($i = 0; $i < $draws; $i++) {
                $result = drawItem($conn);
                $results[] = $result;
                
                // 記錄抽獎歷史
                $stmt = $conn->prepare("
                    INSERT INTO draw_history (userId, itemName, category) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $result['name'],
                    $result['category']
                ]);
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'results' => $results, 'newTokens' => $user['tokens'] - $cost]);
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// 添加抽獎函數
function drawItem($conn) {
    $stmt = $conn->prepare("SELECT name, defaultProbability FROM categories ORDER BY defaultProbability DESC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $rand = mt_rand(1, 10000) / 10000;
    $cumulative = 0;
    $selected_category = '';
    
    foreach ($categories as $category) {
        $cumulative += $category['defaultProbability'];
        if ($rand <= $cumulative) {
            $selected_category = $category['name'];
            break;
        }
    }
    
    $stmt = $conn->prepare("
        SELECT name FROM items 
        WHERE category = ? 
        ORDER BY RAND() 
        LIMIT 1
    ");
    $stmt->execute([$selected_category]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'name' => $item['name'] ?? '未知物品',
        'category' => $selected_category
    ];
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="gacha">抽獎系統 - 儀表板</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1><span data-translate="welcome">歡迎</span>, <?php echo htmlspecialchars($user['username']); ?></h1>
            <div class="user-info">
                <div class="token-display">
                    <i class="token-icon">💰</i>
                    <span class="token-count"><?php echo $user['tokens']; ?></span>
                    <button onclick="showRechargeModal()" class="recharge-btn">+</button>
                </div>
                <button type="button" onclick="showTasks()" class="task-btn">
                    <i class="btn-icon">📋</i>
                    <span data-translate="tasks">任務</span>
                </button>
                <form method="post" class="claim-form">
                    <button type="submit" name="claim_daily" class="claim-btn" 
                        <?php 
                        $lastClaim = new DateTime($user['lastClaim'] ?? '2000-01-01');
                        $now = new DateTime();
                        echo ($lastClaim->format('Y-m-d') == $now->format('Y-m-d')) ? 'disabled' : '';
                        ?>>
                        <span data-translate="dailyReward">領取每日獎勵</span>
                    </button>
                </form>
                <a href="logout.php" class="logout-btn" data-translate="logout">登出</a>
            </div>
        </header>

        <div class="gacha-container">
            <h2 data-translate="gacha">抽獎系統</h2>
            
            <!-- Add prize pool button -->
            <button id="showPrizePoolBtn" class="btn btn-info" onclick="showPrizePool()" data-translate="prizePool">查看獎池詳情</button>
            
            <div class="gacha-machine-wrapper">
                <div class="gacha-icon">🎰</div>
            </div>
            
            <div class="gacha-buttons">
                <button type="button" onclick="performGacha(1)" class="pull-btn" <?php echo $user['tokens'] < 1 ? 'disabled' : ''; ?>>
                    <span data-translate="singleDraw">單抽</span>
                    <span class="cost" data-translate="consumeTokens" data-tokens="1">消耗1代幣</span>
                </button>
                <button type="button" onclick="performGacha(5)" class="pull-btn" <?php echo $user['tokens'] < 5 ? 'disabled' : ''; ?>>
                <span data-translate="fiveDraw">5連抽</span>
                <span class="cost" data-translate="consumeTokens" data-tokens="5">消耗5代幣</span>
                </button>
                <button type="button" onclick="performGacha(10)" class="pull-btn" <?php echo $user['tokens'] < 10 ? 'disabled' : ''; ?>>
                <span data-translate="tenDraw">10連抽</span>
                <span class="cost" data-translate="consumeTokens" data-tokens="10">消耗10代幣</span>
                </button>
            </div>

            <div class="action-buttons">
                <button type="button" onclick="showInventory()" class="action-btn">
                    <i class="btn-icon">🎁</i>
                    <span data-translate="inventory">物品欄</span>
                </button>
                <button type="button" onclick="showHistory()" class="action-btn">
                    <i class="btn-icon">📜</i>
                    <span data-translate="drawHistory">抽獎記錄</span>
                </button>
            </div>
        </div>

        <div id="inventoryPopup" class="gacha-popup" style="display: none;">
            <div class="popup-content">
                <h3 data-translate="inventory">我的物品欄</h3>
                <div class="inventory-grid">
                    <?php
                    $stmt = $conn->prepare("
                        SELECT itemName, category, COUNT(*) as count 
                        FROM draw_history 
                        WHERE userId = ? 
                        GROUP BY itemName, category 
                        ORDER BY category, itemName
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <div class="inventory-item" style="border: 2px solid <?php echo $category_colors[$item['category']]; ?>; background: linear-gradient(45deg, <?php echo $category_colors[$item['category']]; ?>1A, <?php echo $category_colors[$item['category']]; ?>33)">
                            <div class="item-icon">🎁</div>
                            <div class="item-details">
                                <span class="item-name"><?php echo htmlspecialchars($item['itemName']); ?></span>
                                <span class="item-category" style="background-color: <?php echo $category_colors[$item['category']]; ?>">
                                    <?php echo htmlspecialchars($item['category']); ?>
                                </span>
                                <span class="item-count">x<?php echo $item['count']; ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button onclick="closeModal('inventoryPopup')" class="close-btn" data-translate="close">關閉</button>
            </div>
        </div>

        <div id="historyPopup" class="gacha-popup" style="display: none;">
            <div class="popup-content">
                <h3 data-translate="drawHistory">抽獎記錄</h3>
                <div class="gacha-history-list">
                    <?php
                    $stmt = $conn->prepare("
                        SELECT itemName, category, drawTime 
                        FROM draw_history 
                        WHERE userId = ? 
                        ORDER BY drawTime DESC 
                        LIMIT 50
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <div class="history-item" style="border: 2px solid <?php echo $category_colors[$row['category']]; ?>; background: linear-gradient(45deg, <?php echo $category_colors[$row['category']]; ?>1A, <?php echo $category_colors[$row['category']]; ?>33)">
                            <div class="history-item-name">
                                <?php echo htmlspecialchars($row['itemName']); ?>
                            </div>
                            <div class="history-item-category" style="background-color: <?php echo $category_colors[$row['category']]; ?>">
                                <?php echo htmlspecialchars($row['category']); ?>
                            </div>
                            <div class="history-item-time">
                                <?php echo date('Y-m-d H:i:s', strtotime($row['drawTime'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button onclick="closeModal('historyPopup')" class="close-btn" data-translate="close">關閉</button>
            </div>
        </div>
    </div>

    <!-- 添加抽獎結果彈窗 -->
    <div id="gachaPopup" class="gacha-popup" style="display: none;">
        <div class="popup-content">
            <div class="gacha-animation">
                <div class="spinning-icon">🎲</div>
                <p data-translate="spinning">抽獎中...</p>
            </div>
            <div class="gacha-results" style="display: none;">
                <h2 data-translate="gachaResults">抽獎結果</h2>
                <div id="resultsContainer"></div>
                <button onclick="closeGachaPopup()" class="close-btn" data-translate="close">關閉</button>
            </div>
        </div>
    </div>

    <!-- 添加任務彈窗 -->
    <div id="tasksPopup" class="gacha-popup" style="display: none;">
        <div class="popup-content">
            <h3 data-translate="taskList">任務列表</h3>
            <div class="tasks-container">
                <div class="task-tabs">
                    <button class="task-tab active" data-type="daily" onclick="switchTaskTab('daily')" data-translate="dailyTasks">每日任務</button>
                    <button class="task-tab" data-type="monthly" onclick="switchTaskTab('monthly')" data-translate="monthlyTasks">每月任務</button>
                    <button class="task-tab" data-type="one_time" onclick="switchTaskTab('one_time')" data-translate="limitedTasks">限定任務</button>
                </div>
                <div class="task-list">
                    <!-- 任務列表會通過 AJAX 動態載入 -->
                </div>
            </div>
            <button onclick="closeModal('tasksPopup')" class="close-btn" data-translate="close">關閉</button>
        </div>
    </div>

    <!-- 修改充值彈窗部分 -->
    <div id="rechargeModal" class="gacha-popup" style="display: none;">
        <div class="popup-content">
            <h3 data-translate="recharge">代幣充值</h3>
            <div class="recharge-options">
                <?php
                // 獲取充值選
                $stmt = $conn->prepare("SELECT * FROM recharge_options WHERE active = 1 ORDER BY tokens ASC");
                $stmt->execute();
                $recharge_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($recharge_options as $option):
                ?>
                    <div class="recharge-item" onclick="selectRechargeAmount(<?php echo $option['id']; ?>)">
                        <div class="amount" data-translate="tokens"><?php echo $option['tokens']; ?> 代幣</div>
                        <div class="price">NT$ <?php echo $option['price']; ?></div>
                        <?php if ($option['bonus_tokens'] > 0): ?>
                            <div class="bonus" data-translate="bonus">+<?php echo $option['bonus_tokens']; ?> 贈送</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <button onclick="closeModal('rechargeModal')" class="close-btn" data-translate="close">關閉</button>
        </div>
    </div>

    <!-- 修改獎池模態框 -->
    <div class="prize-pool-modal" id="prizePoolModal" tabindex="-1">
        <div class="prize-pool-content">
            <div class="modal-header">
                <h5 class="modal-title" data-translate="prizePoolDetails">獎池詳情</h5>
                <button type="button" class="close-btn" onclick="closePrizePool()">×</button>
            </div>
            <div class="modal-body">
                <table>
                    <thead>
                        <tr>
                            <th data-translate="rarity">稀有度</th>
                            <th data-translate="probability">機率</th>
                            <th data-translate="possibleItems">可能獲得物品</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 獲取類別、顏色和物品（包括自定義概率）
                        $stmt = $conn->prepare("
                            SELECT 
                                c.name as category, 
                                c.defaultProbability as categoryProb,
                                c.color,
                                i.name as itemName,
                                COALESCE(i.probability, c.defaultProbability) as itemProb
                            FROM categories c
                            LEFT JOIN items i ON i.category = c.name
                            ORDER BY c.defaultProbability DESC, i.probability DESC
                        ");
                        $stmt->execute();
                        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // 整理數據：按類別分組
                        $categories = [];
                        foreach ($items as $item) {
                            if (!isset($categories[$item['category']])) {
                                $categories[$item['category']] = [
                                    'color' => $item['color'],
                                    'categoryProb' => $item['categoryProb'] * 100,
                                    'items' => []
                                ];
                            }
                            if ($item['itemName']) {
                                $categories[$item['category']]['items'][] = [
                                    'name' => $item['itemName'],
                                    'probability' => $item['itemProb'] * 100
                                ];
                            }
                        }

                        // 顯示每類別和其物品
                        foreach ($categories as $categoryName => $category): 
                            $color = $category['color'];
                            $bgColor1 = $color . '1A'; // 10% 透明度
                            $bgColor2 = $color . '33'; // 20% 透明度
                        ?>
                            <tr class="<?php echo strtolower($categoryName); ?>">
                                <td style="border: 2px solid <?php echo $color; ?>;
                                           background: linear-gradient(45deg, <?php echo $bgColor1; ?>, <?php echo $bgColor2; ?>);">
                                    <?php echo htmlspecialchars($categoryName); ?>
                                </td>
                                <td style="border: 2px solid <?php echo $color; ?>;
                                           background: linear-gradient(45deg, <?php echo $bgColor1; ?>, <?php echo $bgColor2; ?>);
                                           color: <?php echo $color; ?>">
                                    <?php echo number_format($category['categoryProb'], 1); ?>%
                                </td>
                                <td style="border: 2px solid <?php echo $color; ?>;
                                           background: linear-gradient(45deg, <?php echo $bgColor1; ?>, <?php echo $bgColor2; ?>);">
                                    <div class="items-list">
                                        <?php 
                                        if (!empty($category['items'])) {
                                            foreach ($category['items'] as $item) {
                                                echo '<span>' . htmlspecialchars($item['name']) . 
                                                     ' (' . number_format($item['probability'], 1) . '%)</span>';
                                            }
                                        } else {
                                            echo '<span data-translate="noItems">暫無物品</span>';
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // 首先在 script 開始時定義 category_colors
        const category_colors = <?php echo json_encode($category_colors); ?>;

        function performGacha(draws) {
            const popup = document.getElementById('gachaPopup');
            const animation = popup.querySelector('.gacha-animation');
            const results = popup.querySelector('.gacha-results');
            
            // 顯示彈窗和動畫
            popup.style.display = 'flex';
            animation.style.display = 'block';
            results.style.display = 'none';
            
            // 發送抽獎請求
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `draw=${draws}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        // 隱藏動畫，顯示結果
                        animation.style.display = 'none';
                        
                        // 生成結果 HTML
                        const container = document.getElementById('resultsContainer');
                        container.innerHTML = `
                            <div class="results-scroll">
                                ${data.results.map(result => `
                                    <div class="result-item" style="border: 2px solid ${category_colors[result.category]}; background: linear-gradient(45deg, ${category_colors[result.category]}1A, ${category_colors[result.category]}33)">
                                        <span class="item-name">${result.name}</span>
                                        <span class="item-type" style="background-color: ${category_colors[result.category]}">${result.category}</span>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="quick-gacha-buttons">
                                <button onclick="performGacha(1)" class="gacha-btn" ${data.newTokens < 1 ? 'disabled' : ''}>
                                    <span data-translate="drawAgain">再抽一次</span>
                                    <span class="cost" data-translate="consumeTokens" data-tokens="1">消耗1代幣</span>
                                </button>
                                <button onclick="performGacha(5)" class="gacha-btn" ${data.newTokens < 5 ? 'disabled' : ''}>
                                    <span data-translate="drawFive">抽 5 次</span>
                                    <span class="cost" data-translate="consumeTokens" data-tokens="5">消耗5代幣</span>
                                </button>
                                <button onclick="performGacha(10)" class="gacha-btn" ${data.newTokens < 10 ? 'disabled' : ''}>
                                    <span data-translate="drawTen">抽 10 次</span>
                                    <span class="cost" data-translate="consumeTokens" data-tokens="10">消耗10代幣</span>
                                </button>
                            </div>
                        `;
                        
                        // 更新代幣顯示
                        document.querySelector('.token-count').textContent = data.newTokens;
                        
                        // 顯示結果
                        results.style.display = 'block';
                        
                        // 更新按鈕狀態
                        updateButtons(data.newTokens);
                        
                        // 應用當前主題
                        applyCurrentTheme();
                        
                        // 應用語言翻譯
                        changeLanguage(localStorage.getItem('language') || 'zh');
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(translations[localStorage.getItem('language') || 'en'].errors.gachaFailed);
            });
        }

        function closeGachaPopup() {
            document.getElementById('gachaPopup').style.display = 'none';
            // 重新載歷史記錄
            location.reload();
        }

        function updateButtons(tokens) {
            const buttons = document.querySelectorAll('.pull-btn');
            buttons.forEach(button => {
                const cost = button.innerText.includes('10') ? 10 : 
                            button.innerText.includes('5') ? 5 : 1;
                button.disabled = tokens < cost;
            });
        }

        function showInventory() {
            document.getElementById('inventoryPopup').style.display = 'flex';
        }

        function showHistory() {
            document.getElementById('historyPopup').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function showTasks() {
            document.getElementById('tasksPopup').style.display = 'flex';
            loadTasks('daily'); // 默認加載每日任務
        }

        function switchTaskTab(type) {
            // 切換標籤樣式
            document.querySelectorAll('.task-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // 直接使用傳入的類型，不需要轉換
            loadTasks(type);  // 直接使用 'daily', 'monthly', 'one_time'
        }

        async function loadTasks(type) {
            try {
                console.log('Loading tasks for type:', type);
                const response = await fetch(`tasks.php?type=${type}`);
                const data = await response.json();
                console.log('Tasks data:', data);  // 添加調試輸出
                
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                
                const taskList = document.querySelector('.task-list');
                if (!taskList) {
                    console.error('Task list container not found');
                    return;
                }
                
                if (!data.tasks || !Array.isArray(data.tasks)) {
                    console.error('Invalid tasks data:', data);
                    return;
                }
                
                taskList.innerHTML = data.tasks.map(task => `
                    <div class="task-item">
                        <div class="task-info">
                            <h4>${task.name}</h4>
                            <p>${task.description}</p>
                            <span class="task-reward" data-translate="reward">獎勵: ${task.reward_tokens} 代幣</span>
                        </div>
                        ${task.completed ? 
                            '<button disabled class="completed-btn" data-translate="completed">已完成</button>' : 
                            task.url ? 
                                `<button onclick="window.open('${task.url}', '_blank'); completeTask(${task.id})" class="task-btn" data-translate="completeTask">前往完成</button>` :
                                `<button onclick="completeTask(${task.id})" class="task-btn" data-translate="completeTask">領取獎勵</button>`
                        }
                    </div>
                `).join('');
            } catch (error) {
                console.error('載入任務失敗:', error);
            }
        }

        async function completeTask(taskId) {
            try {
                const response = await fetch('complete_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ taskId })
                });
                
                const data = await response.json();
                
                if (data.error) {
                    alert(data.error);
                    return;
                }
                
                if (data.success) {
                    // 更新代幣顯示
                    document.querySelector('.token-count').textContent = data.newTokens;
                    
                    // 顯示成功訊息
                    const popup = document.createElement('div');
                    popup.className = 'gacha-popup';
                    popup.style.display = 'flex';
                    popup.innerHTML = `
                                                <div class="popup-content">
                            <h3 data-translate="taskCompleted">任務完成！</h3>
                            <p class="reward-text" data-translate="rewardText">獲得 ${data.reward_tokens} 代幣</p>
                            <button onclick="closeTaskPopupAndReload(this)" class="confirm-btn" data-translate="confirm">確定</button>
                        </div>
                    `;
                    document.body.appendChild(popup);
                }
            } catch (error) {
                console.error('完成任務失敗:', error);
                alert('完成任務失敗，請稍後再試');
            }
        }

        // 新增一個專門的關閉彈窗並重新載入任務列表的函數
        async function closeTaskPopupAndReload(button) {
            // 先關閉彈窗
            const popup = button.closest('.gacha-popup');
            if (popup) {
                popup.remove();
            }
            
            // 重新載入當前任務列表
            const activeTab = document.querySelector('.task-tab.active');
            if (activeTab) {
                const type = activeTab.getAttribute('data-type');
                await loadTasks(type);
            }
        }

        function showRechargeModal() {
            document.getElementById('rechargeModal').style.display = 'flex';
        }

        async function selectRechargeAmount(optionId) {
            try {
                // 先獲取充值選項詳情
                const response = await fetch(`get_recharge_option.php?id=${optionId}`);
                const option = await response.json();
                
                if (option.error) {
                    alert(option.error);
                    return;
                }

                // 創建 PayPal 支付
                const paypalResponse = await fetch('create_paypal_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        optionId: optionId,
                        amount: option.price
                    })
                });
                
                const paypalData = await paypalResponse.json();
                
                if (paypalData.error) {
                    alert(paypalData.error);
                    return;
                }

                // 重定向到 PayPal 支付頁面
                window.location.href = paypalData.approvalUrl;
                
            } catch (error) {
                console.error('Error:', error);
                alert('創建支付訂單失敗，請稍再試');
            }
        }

        function closeRechargePopup() {
            const popup = document.querySelector('.gacha-popup');
            if (popup) {
                popup.remove();  // 完全移除彈窗
            }
        }

        function showPrizePool() {
            document.getElementById('prizePoolModal').style.display = 'flex';
        }

        function closePrizePool() {
            document.getElementById('prizePoolModal').style.display = 'none';
        }

        // 修改事件監聽器
        document.getElementById('showPrizePoolBtn').addEventListener('click', showPrizePool);
    </script>

    <!-- 在主題切換按鈕前添加語言切換按鈕 -->
    <button class="language-toggle" onclick="toggleLanguage()" title="切換語言">
        <span id="language-icon">🇹🇼</span>
    </button>
    <button class="theme-toggle" onclick="toggleTheme()" title="切換主題">
        <span id="theme-icon">🌞</span>
    </button>

    <!-- 在 script 標籤內添加語言切換功能 -->
    <script>
    // 在 script 開始處添加
    let translations = {};

    // 加載語言文件
    async function loadTranslations(lang) {
        try {
            const response = await fetch(`locale/${lang}.json`);
            translations[lang] = await response.json();
        } catch (error) {
            console.error('Failed to load translations:', error);
        }
    }

    // 初始化語言設置
    async function initializeLanguage() {
        const defaultLang = 'en';
        const currentLang = localStorage.getItem('language') || defaultLang;
        
        // 加載默認語言和當前語言
        await loadTranslations('en');
        if (currentLang !== 'en') {
            await loadTranslations(currentLang);
        }
        
        // 設置語言圖標
        const languageIcon = document.getElementById('language-icon');
        languageIcon.textContent = currentLang === 'zh' ? '🇹🇼' : '🇺🇸';
        
        // 應用翻譯
        changeLanguage(currentLang);
    }

    // 修改切換語言函數
    async function toggleLanguage() {
        const languageIcon = document.getElementById('language-icon');
        const currentLang = localStorage.getItem('language') || 'en';
        const newLang = currentLang === 'zh' ? 'en' : 'zh';
        
        // 如果還沒有加載目標語言的翻譯，則加載
        if (!translations[newLang]) {
            await loadTranslations(newLang);
        }
        
        languageIcon.textContent = newLang === 'zh' ? '🇹🇼' : '🇺🇸';
        localStorage.setItem('language', newLang);
        changeLanguage(newLang);
    }

    // 修改翻譯應用函數
    function changeLanguage(lang) {
        if (!translations[lang]) return;

        document.querySelectorAll('[data-translate]').forEach(element => {
            const key = element.getAttribute('data-translate');
            let text = getNestedTranslation(translations[lang], key);
            
            if (text) {
                // 處理需要替換參數的文本
                if (key === 'consumeTokens') {
                    const tokens = element.getAttribute('data-tokens');
                    text = text.replace('{n}', tokens);
                }
                element.textContent = text;
            }
        });
    }

    // 用於獲取嵌套的翻譯值
    function getNestedTranslation(obj, path) {
        return path.split('.').reduce((prev, curr) => {
            return prev ? prev[curr] : null;
        }, obj);
    }

    // 在頁面加載時初始化語言
    document.addEventListener('DOMContentLoaded', initializeLanguage);
    </script>

    <!-- 在 script 標籤內修改主題切換功能 -->
    <script>
    // 檢查本地存儲的主題設置
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-mode');
        document.getElementById('theme-icon').textContent = '🌙';
    }

    function toggleTheme() {
        const body = document.body;
        const themeIcon = document.getElementById('theme-icon');
        
        if (body.classList.contains('dark-mode')) {
            body.classList.remove('dark-mode');
            themeIcon.textContent = '🌞';
            localStorage.setItem('theme', 'light');
        } else {
            body.classList.add('dark-mode');
            themeIcon.textContent = '🌙';
            localStorage.setItem('theme', 'dark');
        }
    }

    // 確保在生成新內容後也應用主題
    function applyCurrentTheme() {
        const currentTheme = localStorage.getItem('theme') || 'light';
        const body = document.body;
        const themeIcon = document.getElementById('theme-icon');
        
        if (currentTheme === 'dark') {
            body.classList.add('dark-mode');
            themeIcon.textContent = '🌙';
        } else {
            body.classList.remove('dark-mode');
            themeIcon.textContent = '🌞';
        }
    }

    // 在生成新內容後調用
    document.addEventListener('DOMContentLoaded', applyCurrentTheme);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 