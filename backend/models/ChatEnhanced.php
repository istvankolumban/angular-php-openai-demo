<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * Enhanced Chat Session Model with Thread Management
 * 
 * Supports multiple threads per user for better organization and cost control
 */
class ChatSession {
    private $db;
    private $table = 'chat_sessions';

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Create a new chat session with thread
     * 
     * @param int $userId
     * @param string $title
     * @param string $category Optional category/topic
     * @param string $threadId Optional OpenAI thread ID
     * @return int|false Session ID or false on failure
     */
    public function create($userId, $title = 'New Chat', $category = 'General', $threadId = null) {
        try {
            // Check user's thread limit (max 10 active threads)
            if ($this->getUserActiveThreadCount($userId) >= 10) {
                throw new Exception("Maximum number of active threads reached (10)");
            }

            $query = "INSERT INTO " . $this->table . " (user_id, title, category, thread_id, status) VALUES (?, ?, ?, ?, 'active')";
            $stmt = $this->db->prepare($query);
            
            if ($stmt->execute([$userId, $title, $category, $threadId])) {
                return $this->db->lastInsertId();
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Chat session creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all chat sessions for a user with filtering
     * 
     * @param int $userId
     * @param string $status Filter by status (active, archived, all)
     * @param string $category Filter by category
     * @return array
     */
    public function getUserSessions($userId, $status = 'active', $category = null) {
        try {
            $whereClause = "WHERE user_id = ?";
            $params = [$userId];
            
            if ($status !== 'all') {
                $whereClause .= " AND status = ?";
                $params[] = $status;
            }
            
            if ($category) {
                $whereClause .= " AND category = ?";
                $params[] = $category;
            }
            
            $query = "SELECT id, title, category, status, thread_id, message_count, 
                            created_at, updated_at, last_message_at 
                     FROM " . $this->table . " 
                     $whereClause 
                     ORDER BY last_message_at DESC, updated_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get user sessions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user's active thread count
     * 
     * @param int $userId
     * @return int
     */
    public function getUserActiveThreadCount($userId) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE user_id = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Get active thread count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get session statistics for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getUserStats($userId) {
        try {
            $query = "SELECT 
                        COUNT(*) as total_sessions,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_sessions,
                        SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_sessions,
                        SUM(message_count) as total_messages,
                        COUNT(DISTINCT category) as categories_used,
                        MAX(last_message_at) as last_activity
                      FROM " . $this->table . " 
                      WHERE user_id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            
            return $stmt->fetch() ?: [];
            
        } catch (PDOException $e) {
            error_log("Get user stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available categories for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getUserCategories($userId) {
        try {
            $query = "SELECT DISTINCT category, COUNT(*) as session_count 
                     FROM " . $this->table . " 
                     WHERE user_id = ? AND status = 'active'
                     GROUP BY category 
                     ORDER BY session_count DESC, category ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get user categories error: " . $e->getMessage());
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
     * Update session details
     * 
     * @param int $sessionId
     * @param int $userId
     * @param array $updates Array of field => value pairs
     * @return bool
     */
    public function updateSession($sessionId, $userId, $updates) {
        try {
            $allowedFields = ['title', 'category', 'status'];
            $setParts = [];
            $params = [];
            
            foreach ($updates as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $setParts[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($setParts)) {
                return false;
            }
            
            $params[] = $sessionId;
            $params[] = $userId;
            
            $query = "UPDATE " . $this->table . " SET " . implode(', ', $setParts) . " WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("Update session error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Archive a session (soft delete)
     * 
     * @param int $sessionId
     * @param int $userId
     * @return bool
     */
    public function archiveSession($sessionId, $userId) {
        return $this->updateSession($sessionId, $userId, ['status' => 'archived']);
    }

    /**
     * Restore an archived session
     * 
     * @param int $sessionId
     * @param int $userId
     * @return bool
     */
    public function restoreSession($sessionId, $userId) {
        return $this->updateSession($sessionId, $userId, ['status' => 'active']);
    }

    /**
     * Delete a session permanently
     * 
     * @param int $sessionId
     * @param int $userId
     * @return bool
     */
    public function deleteSession($sessionId, $userId) {
        try {
            // First delete all messages in this session
            $messageModel = new Message();
            $messageModel->deleteSessionMessages($sessionId);
            
            // Then delete the session
            $query = "DELETE FROM " . $this->table . " WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute([$sessionId, $userId]);
            
        } catch (PDOException $e) {
            error_log("Delete session error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update session thread ID
     * 
     * @param int $sessionId
     * @param int $userId
     * @param string $threadId
     * @return bool
     */
    public function updateThreadId($sessionId, $userId, $threadId) {
        try {
            $query = "UPDATE " . $this->table . " SET thread_id = ? WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute([$threadId, $sessionId, $userId]);
            
        } catch (PDOException $e) {
            error_log("Update thread ID error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update session activity and message count
     * 
     * @param int $sessionId
     * @return bool
     */
    public function updateActivity($sessionId) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET last_message_at = NOW(), 
                         message_count = (SELECT COUNT(*) FROM messages WHERE session_id = ?)
                     WHERE id = ?";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute([$sessionId, $sessionId]);
            
        } catch (PDOException $e) {
            error_log("Update session activity error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Auto-archive inactive sessions
     * 
     * @param int $daysInactive Number of days of inactivity
     * @return int Number of sessions archived
     */
    public function autoArchiveInactive($daysInactive = 30) {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET status = 'archived' 
                     WHERE status = 'active' 
                     AND (last_message_at < DATE_SUB(NOW(), INTERVAL ? DAY) 
                          OR (last_message_at IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)))";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$daysInactive, $daysInactive]);
            
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Auto archive inactive sessions error: " . $e->getMessage());
            return 0;
        }
    }
}

/**
 * Enhanced Message Model
 */
class Message {
    private $db;
    private $table = 'messages';

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Create a new message
     * 
     * @param int $sessionId
     * @param string $content
     * @param string $role (user|assistant|system)
     * @param array $metadata Optional metadata
     * @return int|false Message ID or false on failure
     */
    public function create($sessionId, $content, $role = 'user', $metadata = null) {
        try {
            $query = "INSERT INTO " . $this->table . " (session_id, content, role, metadata) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            
            $metadataJson = $metadata ? json_encode($metadata) : null;
            
            if ($stmt->execute([$sessionId, $content, $role, $metadataJson])) {
                // Update session activity
                $chatSession = new ChatSession();
                $chatSession->updateActivity($sessionId);
                
                return $this->db->lastInsertId();
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Message creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get messages for a session with pagination
     * 
     * @param int $sessionId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getSessionMessages($sessionId, $limit = 50, $offset = 0) {
        try {
            $query = "SELECT id, content, role, metadata, created_at 
                     FROM " . $this->table . " 
                     WHERE session_id = ? 
                     ORDER BY created_at ASC 
                     LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$sessionId, $limit, $offset]);
            
            $messages = $stmt->fetchAll();
            
            // Decode metadata
            foreach ($messages as &$message) {
                if ($message['metadata']) {
                    $message['metadata'] = json_decode($message['metadata'], true);
                }
            }
            
            return $messages;
            
        } catch (PDOException $e) {
            error_log("Get session messages error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get message count for a session
     * 
     * @param int $sessionId
     * @return int
     */
    public function getSessionMessageCount($sessionId) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE session_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$sessionId]);
            
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("Get session message count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete all messages for a session
     * 
     * @param int $sessionId
     * @return bool
     */
    public function deleteSessionMessages($sessionId) {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE session_id = ?";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute([$sessionId]);
            
        } catch (PDOException $e) {
            error_log("Delete session messages error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search messages across sessions for a user
     * 
     * @param int $userId
     * @param string $searchTerm
     * @param int $limit
     * @return array
     */
    public function searchUserMessages($userId, $searchTerm, $limit = 20) {
        try {
            $query = "SELECT m.id, m.content, m.role, m.created_at, 
                            cs.id as session_id, cs.title as session_title, cs.category
                     FROM " . $this->table . " m
                     JOIN chat_sessions cs ON m.session_id = cs.id
                     WHERE cs.user_id = ? AND m.content LIKE ?
                     ORDER BY m.created_at DESC
                     LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, "%$searchTerm%", $limit]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Search user messages error: " . $e->getMessage());
            return [];
        }
    }
}
?>
