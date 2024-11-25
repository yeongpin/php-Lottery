<?php
class Database {
    private $host = "localhost";
    private $username = "test";
    private $password = "root";
    private $database = "lottry";
    private $conn;

    // 默認管理員帳號密碼
    private $admin_username = "admin";
    private $admin_password = "admin";

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        return $this->conn;
    }

    public function verifyAdmin($username, $password) {
        return ($username === $this->admin_username && $password === $this->admin_password);
    }
}
?> 