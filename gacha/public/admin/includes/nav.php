<nav class="admin-nav">
    <h1>管理後台</h1>
    <ul>
        <li><a href="index.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'class="active"' : ''; ?>>儀表板</a></li>
        <li><a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>用戶管理</a></li>
        <li><a href="items.php" <?php echo basename($_SERVER['PHP_SELF']) == 'items.php' ? 'class="active"' : ''; ?>>物品管理</a></li>
        <li><a href="categories.php" <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'class="active"' : ''; ?>>類別管理</a></li>
        <li><a href="history.php" <?php echo basename($_SERVER['PHP_SELF']) == 'history.php' ? 'class="active"' : ''; ?>>抽獎歷史</a></li>
        <li><a href="statistics.php" <?php echo basename($_SERVER['PHP_SELF']) == 'statistics.php' ? 'class="active"' : ''; ?>>統計報表</a></li>
        <li><a href="tasks_manage.php" <?php echo basename($_SERVER['PHP_SELF']) == 'tasks_manage.php' ? 'class="active"' : ''; ?>>任務管理</a></li>
        <li><a href="recharge_options.php" <?php echo basename($_SERVER['PHP_SELF']) == 'recharge_options.php' ? 'class="active"' : ''; ?>>充值選項</a></li>
        <li><a href="logout.php">登出</a></li>
    </ul>
</nav> 