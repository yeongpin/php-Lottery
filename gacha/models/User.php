<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $username;
    public $password;
    public $tokens;
    public $lastClaim;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // 用户注册
    public function register($username, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO " . $this->table . " 
                 (username, password, tokens) 
                 VALUES (:username, :password, 500)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashedPassword);
            
            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }

    // 用户登录
    public function login($username, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    // 更新代币数量
    public function updateTokens($userId, $amount) {
        $query = "UPDATE " . $this->table . "
                 SET tokens = tokens + :amount
                 WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':id', $userId);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    // 获取用户信息
    public function getUser($userId) {
        $query = "SELECT id, username, tokens, lastClaim 
                 FROM " . $this->table . " 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 更新最后领取时间
    public function updateLastClaim($userId) {
        $query = "UPDATE " . $this->table . "
                 SET lastClaim = NOW()
                 WHERE id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }
}
?> 