<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 獲取抽獎次數
$draws = isset($_GET['draws']) ? (int)$_GET['draws'] : 1;

// 獲取所有類別及其概率
$stmt = $conn->prepare("SELECT name, defaultProbability FROM categories ORDER BY defaultProbability DESC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 執行抽獎
function drawItem($conn, $categories) {
    // 隨機數決定類別
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
    
    // 從選中的類別中隨機選擇物品
    $stmt = $conn->prepare("
        SELECT id, name 
        FROM items 
        WHERE category = ? 
        ORDER BY RAND() 
        LIMIT 1
    ");
    $stmt->execute([$selected_category]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        // 如果該類別沒有物品，使用預設物品
        $default_items = [
            'LEGENDARY' => '傳說寶箱',
            'EPIC' => '史詩寶箱',
            'RARE' => '稀有寶箱',
            'COMMON' => '普通寶箱'
        ];
        return [
            'name' => $default_items[$selected_category],
            'category' => $selected_category
        ];
    }
    
    return [
        'name' => $item['name'],
        'category' => $selected_category
    ];
}

// 開始抽獎流程
try {
    $conn->beginTransaction();
    
    // 執行抽獎並記錄結果
    $results = [];
    for ($i = 0; $i < $draws; $i++) {
        $result = drawItem($conn, $categories);
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
} catch (Exception $e) {
    $conn->rollBack();
    error_log($e->getMessage());
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="gachaResults">抽獎結果</title>
    <link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>
    <div class="result-popup">
        <div class="popup-content">
            <div class="result-animation">🎉</div>
            <div class="result-grid">
                <?php foreach ($results as $result): ?>
                    <div class="result-item <?php echo strtolower($result['category']); ?>">
                        <span class="item-name"><?php echo htmlspecialchars($result['name']); ?></span>
                        <span class="item-type"><?php echo htmlspecialchars($result['category']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="dashboard.php" class="back-btn" data-translate="back">返回</a>
        </div>
    </div>

    <!-- 添加語言切換相關的腳本 -->
    <script>
    // 檢查本地存儲的語言設置
    const currentLang = localStorage.getItem('language') || 'zh';
    if (currentLang === 'en') {
        changeLanguage('en');
    }

    function changeLanguage(lang) {
        const translations = {
            zh: {
                gachaResults: '抽獎結果',
                back: '返回'
            },
            en: {
                gachaResults: 'Gacha Results',
                back: 'Back'
            }
        };

        document.querySelectorAll('[data-translate]').forEach(element => {
            const key = element.getAttribute('data-translate');
            if (translations[lang][key]) {
                element.textContent = translations[lang][key];
            }
        });

        // 更新頁面標題
        document.title = translations[lang]['gachaResults'];
    }
    </script>
</body>
</html> 