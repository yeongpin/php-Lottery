<?php
session_start();
session_destroy();  // 清除所有會話數據
header("Location: index.php");  // 重定向到登入頁面
exit();