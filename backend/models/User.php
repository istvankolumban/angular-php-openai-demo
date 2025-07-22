<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * User Model
 * 
 * This class handles all user-related database operations.
 * In PHP, models are classes that represent data and business logic.
 * This follows the MVC (Model-View-Controller) pattern.
 */
class User {
    private $conn;
    private $table = 'users';
    
    // User properties
    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $created_at;
    public $updated_at;
    
    /**
     * Constructor - initialize database connection
     */
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Create a new user
     * 
     * @param string $username
     * @param string $email  
     * @param string $password
     * @return bool True on success, false on failure
     */
    public function create($username, $email, $password) {
        // Hash the password using PHP's password_hash function
        // This uses bcrypt by default, which is secure
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO " . $this->table . " 
                  (username, email, password_hash) 
                  VALUES (:username, :email, :password_hash)";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters to prevent SQL injection
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $passwordHash);
        
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            $this->username = $username;
            $this->email = $email;
            return true;
        }
        
        return false;
    }
    
    /**
     * Find user by email
     * 
     * @param string $email
     * @return array|null User data or null if not found
     */
    public function findByEmail($email) {
        $query = "SELECT id, username, email, password_hash, created_at 
                  FROM " . $this->table . " 
                  WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password_hash = $row['password_hash'];
            $this->created_at = $row['created_at'];
            return $row;
        }
        
        return null;
    }
    
    /**
     * Find user by ID
     * 
     * @param int $id
     * @return array|null User data or null if not found
     */
    public function findById($id) {
        $query = "SELECT id, username, email, created_at 
                  FROM " . $this->table . " 
                  WHERE id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verify user password
     * 
     * @param string $password Plain text password
     * @param string $hash Hashed password from database
     * @return bool True if password matches, false otherwise
     */
    public function verifyPassword($password, $hash) {
        // password_verify() checks if the password matches the hash
        return password_verify($password, $hash);
    }
    
    /**
     * Check if email already exists
     * 
     * @param string $email
     * @return bool True if exists, false otherwise
     */
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Check if username already exists
     * 
     * @param string $username
     * @return bool True if exists, false otherwise
     */
    public function usernameExists($username) {
        $query = "SELECT id FROM " . $this->table . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>
