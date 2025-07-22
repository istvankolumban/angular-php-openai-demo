<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * Chat Session Model
 * 
 * This handles chat sessions - collections of messages between user and AI
 */
class ChatSession {
    private $db;
    private $table = 'chat_sessions';

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Create a new chat session
     * 
     * @param int $userId
     * @param string $title
     * @return int|false Session ID or false on failure
     */
    public function create($userId, $title = 'New Chat') {
        try {
            $query = "INSERT INTO " . $this->table . " (user_id, title) VALUES (?, ?)";
            $stmt = $this->db->prepare($query);
            
            if ($stmt->execute([$userId, $title])) {
                return $this->db->lastInsertId();
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Chat session creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all chat sessions for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getUserSessions($userId) {
        try {
            $query = "SELECT id, title, created_at, updated_at FROM " . $this->table . " 
                     WHERE user_id = ? ORDER BY updated_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get user sessions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a specific chat session
     * 
     * @param int $sessionId
     * @param int $userId
     * @return array|null
     */
    public function getSession($sessionId, $userId) {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$sessionId, $userId]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Get session error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update session title
     * 
     * @param int $sessionId
     * @param int $userId
     * @param string $title
     * @return bool
     */
    public function updateTitle($sessionId, $userId, $title) {
        try {
            $query = "UPDATE " . $this->table . " SET title = ? WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute([$title, $sessionId, $userId]);
            
        } catch (PDOException $e) {
            error_log("Update session title error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a chat session
     * 
     * @param int $sessionId
     * @param int $userId
     * @return bool
     */
    public function delete($sessionId, $userId) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute([$sessionId, $userId]);
            
        } catch (PDOException $e) {
            error_log("Delete session error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Message Model
 * 
 * This handles individual messages within chat sessions
 */
class Message {
    private $db;
    private $table = 'messages';

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Add a message to a chat session
     * 
     * @param int $sessionId
     * @param string $role 'user' or 'assistant'
     * @param string $content
     * @return int|false Message ID or false on failure
     */
    public function create($sessionId, $role, $content) {
        try {
            $query = "INSERT INTO " . $this->table . " (session_id, role, content) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            
            if ($stmt->execute([$sessionId, $role, $content])) {
                return $this->db->lastInsertId();
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Message creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all messages for a chat session
     * 
     * @param int $sessionId
     * @return array
     */
    public function getSessionMessages($sessionId) {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                     WHERE session_id = ? ORDER BY created_at ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$sessionId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get session messages error: " . $e->getMessage());
            return [];
        }
    }
}
?>
