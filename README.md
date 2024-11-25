# 🎲 Gacha System (抽獎系統)

一個基於 PHP 和 MySQL 的現代化抽獎系統，具有完整的用戶管理、抽獎機制、任務系統和支付整合。

## 🌟 系統特點

### 用戶系統
- 安全的用戶註冊和登入
- 支持深色/淺色主題切換
- 多語言支持（中文/英文）
- 個人化儀表板
- 自動保存用戶偏好設置

### 抽獎系統
- 多種抽獎選項（單抽/五連抽/十連抽）
- 五種稀有度（神話/傳說/史詩/稀有/普通）
- 動態抽獎動畫效果
- 詳細的獎池資訊顯示
- 抽獎歷史記錄
- 物品庫存管理

### 任務系統
- 每日任務
- 每月任務
- 限定任務
- 任務獎勵自動發放
- 任務進度追蹤
- 即時獎勵通知

### 支付系統
- PayPal 支付整合
- 多種充值選項
- 贈送代幣機制
- 安全的交易處理
- 交易歷史記錄

## 🎨 界面設計

### 主題支持
- 自適應深色/淺色模式
- 平滑的主題切換動畫
- 符合人體工程學的色彩搭配
- 一致的視覺風格

### 響應式設計
- 完全支持移動端
- 自適應各種屏幕尺寸
- 觸控優化界面
- 流暢的動畫效果

### 用戶體驗
- 直觀的操作界面
- 清晰的視覺反饋
- 豐富的動畫效果
- 友好的錯誤提示

## 🛠️ 技術實現

### 前端技術
- HTML5 + CSS3
- JavaScript (ES6+)
- Bootstrap 5
- Animate.css
- 自定義動畫效果

### 後端技術
- PHP 7+
- MySQL 數據庫
- PDO 數據庫操作
- RESTful API 設計

### 安全特性
- 密碼加密存儲
- SQL 注入防護
- XSS 攻擊防護
- CSRF 防護
- 安全的會話管理

## 📦 系統要求

- PHP 7.0 或更高版本
- MySQL 5.7 或更高版本
- Apache/Nginx 網頁服務器
- SSL 證書（用於支付功能）
- 現代瀏覽器支持

## 🚀 安裝步驟

1. 克隆專案：
   ```bash
   git clone https://github.com/your-username/gacha-system.git
   ```

2. 配置數據庫：
   ```sql
   CREATE DATABASE gacha_system;
   USE gacha_system;
   SOURCE database.sql;
   ```

3. 配置環境變量：
   ```bash
   cp .env.example .env
   ```

4. 設置權限：
   ```bash
   chmod 755 -R public/
   chmod 644 -R config/
   ```

## 📝 配置說明

### 數據庫配置
```php
// config/database.php
return [
    'host' => 'localhost',
    'dbname' => 'gacha_system',
    'username' => 'your_username',
    'password' => 'your_password',
];
```

### PayPal 配置
```env
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_CLIENT_SECRET=your_client_secret
PAYPAL_MODE=sandbox # 或 'live' 用於生產環境
```

## 🔧 開發指南

### 目錄結構
```plaintext
gacha/
├── config/        # 配置文件
├── models/        # 數據模型
├── public/        # 公開訪問目錄
│   ├── admin/     # 管理後台
│   ├── styles/    # CSS 文件
│   └── locale/    # 語言文件
├── screenshots/   # 截圖
└── README.md      # 說明文檔
```

### 開發規範
- 遵循 PSR-4 自動加載規範
- 使用 PDO 預處理語句
- 統一的錯誤處理機制
- 完整的日誌記錄
- 代碼註釋規範

## 📄 開源協議

本項目採用 MIT 協議 - 查看 [LICENSE](LICENSE) 文件了解詳情。

## 💻 代碼示例

### 任務完成提示樣式
```css
/* 任務完成提示樣式 */
.task-completed-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #1a1a1a; /* 深色背景 */
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
    text-align: center;
    z-index: 1000;
    min-width: 300px;
}
.task-completed-popup h3 {
    color: #fff; /* 白色文字 */
    margin-bottom: 15px;
    font-size: 1.3em;
    font-weight: bold;
}
.task-completed-popup p {
    color: #fff; /* 白色文字 */
    margin-bottom: 20px;
    font-size: 1.1em;
}
.confirm-btn {
    padding: 12px 30px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1.1em;
    font-weight: 500;
    margin-top: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.confirm-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    background: var(--accent-color);
}
```

### 使用示例
```php
<div class="popup-content">
    <h3 data-translate="taskCompleted">任務完成！</h3>
    <p class="reward-text" data-translate="rewardText">獲得 ${data.reward_tokens} 代幣</p>
    <button onclick="closeTaskPopupAndReload(this)" class="confirm-btn" data-translate="confirm">確定</button>
</div>
```

## 🔄 更新日誌

### v1.0.0 (2024-01-XX)
- 初始版本發布
- 實現基本功能
- 整合支付系統

## 👥 貢獻指南

1. Fork 本專案
2. 創建新的功能分支
3. 提交更改
4. 發起 Pull Request

## 🙏 致謝

- [Bootstrap](https://getbootstrap.com/)
- [PayPal API](https://developer.paypal.com/)
- [Animate.css](https://animate.style/)

## 📱 聯繫方式

- **作者**：[您的名字]
- **郵箱**：[您的郵箱]
- **網站**：[URL]
