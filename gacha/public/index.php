<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';
$draw_result = null;

// 檢查試玩次數
if (!isset($_SESSION['trial_used'])) {
    $_SESSION['trial_used'] = false;
}

// 處理試玩抽獎
if (isset($_POST['try_gacha'])) {
    header('Content-Type: application/json');
    ob_clean(); // 清除之前的輸出緩衝
    
    if ($_SESSION['trial_used']) {
        echo json_encode(['error' => "試玩次數已用完，請登入繼續玩！"]);
        exit;
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // 抽獎邏輯
    $categories = [
        ['name' => 'LEGENDARY', 'probability' => 0.20],
        ['name' => 'EPIC', 'probability' => 0.30],
        ['name' => 'RARE', 'probability' => 0.25],
        ['name' => 'COMMON', 'probability' => 0.25]
    ];
    
    $rand = mt_rand(1, 10000) / 10000;
    $cumulative = 0;
    $selected_category = '';
    
    foreach ($categories as $category) {
        $cumulative += $category['probability'];
        if ($rand <= $cumulative) {
            $selected_category = $category['name'];
            break;
        }
    }
    
    $stmt = $conn->prepare("SELECT name FROM items WHERE category = ? ORDER BY RAND() LIMIT 1");
    $stmt->execute([$selected_category]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        $default_items = [
            'LEGENDARY' => '傳說寶箱',
            'EPIC' => '史詩寶箱',
            'RARE' => '稀有寶箱',
            'COMMON' => '普通寶箱'
        ];
        
        $draw_result = [
            'name' => $default_items[$selected_category],
            'category' => $selected_category
        ];
    } else {
        $draw_result = [
            'name' => $item['name'],
            'category' => $selected_category
        ];
    }
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("INSERT INTO draw_history (userId, itemName, category) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $draw_result['name'], $draw_result['category']]);
    }

    $_SESSION['trial_used'] = true;
    
    echo json_encode([
        'success' => true,
        'result' => $draw_result
    ]);
    exit;
}

// 處理註冊請求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "密碼不匹配";
    } else {
        try {
            // 檢查用戶名是否已存在
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $error = "用戶名已存在";
            } else {
                // 創建新用戶
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $hashed_password]);
                $success = "註冊成功！請登入";
            }
        } catch(PDOException $e) {
            $error = "註冊失敗：" . $e->getMessage();
        }
    }
}

// 處理登入請求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "用戶名或密碼錯誤";
        }
    } catch(PDOException $e) {
        $error = "登入失敗：" . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="login.title">登入</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="container">
        <!-- 试玩抽奖区域 -->
        <div class="try-gacha animate__animated animate__fadeInDown">
            <div class="section-header">
                <h2 data-translate="gachaSystem">試玩抽獎</h2>
                <p class="section-description" data-translate="trialDescription">體驗一下抽獎的樂趣！有機會抽中傳說級物品！</p>
            </div>
            <form method="post" id="gachaForm">
                <button type="button" name="try_gacha" 
                        class="gacha-btn <?php echo $_SESSION['trial_used'] ? 'disabled' : ''; ?>" 
                        <?php echo $_SESSION['trial_used'] ? 'disabled' : ''; ?> 
                        onclick="handleGachaSubmit()">
                    <span class="btn-icon">🎲</span>
                    <span class="btn-text" data-translate="freeTrial">免費抽獎</span>
                </button>
            </form>
        </div>

        <!-- 登入注册区域 -->
        <div class="login-container animate__animated animate__fadeInUp">
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('login')" data-translate="login">登入</button>
                <button class="tab-btn" onclick="switchTab('register')" data-translate="register">註冊</button>
            </div>
            
            <!-- 登入表单 -->
            <form id="login-form" method="post" action="" class="form-active">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="login-username">
                        <span class="label-icon">👤</span>
                        <span data-translate="username">帳號</span>
                    </label>
                    <input type="text" id="login-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="login-password">
                        <span class="label-icon">🔒</span>
                        <span data-translate="password">密碼</span>
                    </label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                <button type="submit" data-translate="login">登入</button>
            </form>
            
            <!-- 注册表单 -->
            <form id="register-form" method="post" action="" style="display: none;">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label for="register-username">
                        <span class="label-icon">👤</span>
                        <span data-translate="username">帳號</span>
                    </label>
                    <input type="text" id="register-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="register-password">
                        <span class="label-icon">🔒</span>
                        <span data-translate="password">密碼</span>
                    </label>
                    <input type="password" id="register-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">
                        <span class="label-icon">🔒</span>
                        <span data-translate="confirmPassword">確認密碼</span>
                    </label>
                    <input type="password" id="confirm-password" name="confirm_password" required>
                </div>
                <button type="submit" data-translate="register">註冊</button>
            </form>

            <?php if($error): ?>
                <div class="error-message animate__animated animate__shakeX">
                    <span class="message-icon">❌</span>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="success-message animate__animated animate__bounceIn">
                    <span class="message-icon">✅</span>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 抽奖动画 -->
    <div class="gacha-animation" id="gachaAnimation">
        <div class="gacha-animation-content animate__animated animate__bounce">
            <div class="spinning-icon">🎲</div>
            <p class="spinning-text" data-translate="spinningText">抽獎中...</p>
        </div>
    </div>

    <!-- 功能按钮 -->
    <div class="floating-buttons">
        <button class="floating-btn language-toggle" onclick="toggleLanguage()" title="切換語言">
            <span id="language-icon">🇺🇸</span>
        </button>
        <button class="floating-btn theme-toggle" onclick="toggleTheme()" title="切換主題">
            <span id="theme-icon">🌞</span>
        </button>
    </div>

    <script>
        function switchTab(tab) {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const tabs = document.querySelectorAll('.tab-btn');
            
            if (tab === 'login') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                tabs[0].classList.remove('active');
                tabs[1].classList.add('active');
            }
        }

        function showLoginForm() {
            document.querySelector('.login-container').style.display = 'block';
            switchTab('login');
            document.querySelector('.login-container').scrollIntoView({ 
                behavior: 'smooth' 
            });
        }

        <?php if ($error && !isset($_SESSION['user_id'])): ?>
        showLoginForm();
        <?php endif; ?>

        async function handleGachaSubmit() {
            const gachaBtn = document.querySelector('.gacha-btn');
            
            if (gachaBtn.disabled) {
                const popup = document.createElement('div');
                popup.className = 'gacha-popup';
                popup.innerHTML = `
                    <div class="popup-content">
                        <h3 data-translate="trialUsed">免費次數已用完</h3>
                        <p class="notice-text" data-translate="trialUsedText">您的免費抽獎次數已經用完了！</p>
                        <p class="notice-text" data-translate="trialUsedText2">登入即可獲得更多抽獎機會！</p>
                        <div class="popup-buttons">
                            <button onclick="closeModal('gachaPopup')" class="close-btn" data-translate="close">關閉</button>
                            <button onclick="showLoginForm(); closeModal('gachaPopup')" class="close-btn" data-translate="login">立即登入</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(popup);
                return;
            }

            const animation = document.getElementById('gachaAnimation');
            animation.style.display = 'flex';
            
            try {
                const formData = new FormData();
                formData.append('try_gacha', '1');
                
                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.error) {
                    alert(data.error);
                    animation.style.display = 'none';
                    return;
                }
                
                // 3秒後顯示結果
                setTimeout(() => {
                    animation.style.display = 'none';
                    
                    // 創建並顯示彈窗
                    const popup = document.createElement('div');
                    popup.className = 'gacha-popup';
                    popup.style.display = 'flex';  // 確保彈窗顯示
                    popup.innerHTML = `
                        <div class="popup-content">
                            <h3 data-translate="congratulations">恭喜獲得！</h3>
                            <p class="item-name">${data.result.name}</p>
                            <p class="item-category">${data.result.category}</p>
                            <button onclick="closeModal('gachaPopup')" class="close-btn" data-translate="close">關閉</button>
                        </div>
                    `;
                    document.body.appendChild(popup);
                    
                    // 禁用抽獎按鈕
                    gachaBtn.disabled = true;
                }, 3000);
                
            } catch (error) {
                console.error('Error:', error);
                animation.style.display = 'none';
                alert('抽獎失敗，請稍後再試');
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            } else {
                // 如果找不到指定的 modal，試所有 gacha-popup
                const popups = document.querySelectorAll('.gacha-popup');
                popups.forEach(popup => {
                    popup.remove();
                });
            }
        }

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
            
            await loadTranslations('en');
            if (currentLang !== 'en') {
                await loadTranslations(currentLang);
            }
            
            const languageIcon = document.getElementById('language-icon');
            languageIcon.textContent = currentLang === 'zh' ? '🇹🇼' : '🇺🇸';
            
            changeLanguage(currentLang);
        }

        // 切換語言
        async function toggleLanguage() {
            const languageIcon = document.getElementById('language-icon');
            const currentLang = localStorage.getItem('language') || 'en';
            const newLang = currentLang === 'zh' ? 'en' : 'zh';
            
            if (!translations[newLang]) {
                await loadTranslations(newLang);
            }
            
            languageIcon.textContent = newLang === 'zh' ? '🇹🇼' : '🇺🇸';
            localStorage.setItem('language', newLang);
            changeLanguage(newLang);
        }

        // 應用翻譯
        function changeLanguage(lang) {
            if (!translations[lang]) return;

            document.querySelectorAll('[data-translate]').forEach(element => {
                const key = element.getAttribute('data-translate');
                let text = getNestedTranslation(translations[lang], key);
                
                if (text) {
                    element.textContent = text;
                }
            });
        }

        // 獲取嵌套的翻譯值
        function getNestedTranslation(obj, path) {
            return path.split('.').reduce((prev, curr) => {
                return prev ? prev[curr] : null;
            }, obj);
        }

        // 主題切換相關
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

        // 在頁面加載時初始化
        document.addEventListener('DOMContentLoaded', initializeLanguage);
    </script>
</body>
</html> 