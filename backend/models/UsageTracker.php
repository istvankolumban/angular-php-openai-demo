<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * Usage Tracking Model
 * 
 * Track API usage and costs for monitoring and billing
 */
class UsageTracker {
    private $db;
    private $table = 'usage_logs';

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->createTableIfNotExists();
    }

    /**
     * Create usage logs table if it doesn't exist
     */
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_id INT DEFAULT NULL,
            message_id INT DEFAULT NULL,
            input_tokens INT DEFAULT 0,
            output_tokens INT DEFAULT 0,
            cost_usd DECIMAL(10,6) DEFAULT 0.000000,
            model VARCHAR(50) DEFAULT 'gpt-4o-mini',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Usage table creation error: " . $e->getMessage());
        }
    }

    /**
     * Log API usage
     * 
     * @param int $userId
     * @param int $sessionId
     * @param int $messageId
     * @param int $inputTokens
     * @param int $outputTokens
     * @param string $model
     * @return bool
     */
    public function logUsage($userId, $sessionId, $messageId, $inputTokens, $outputTokens, $model = 'gpt-4o-mini') {
        try {
            // Calculate cost based on current pricing
            $cost = $this->calculateCost($inputTokens, $outputTokens, $model);
            
            $query = "INSERT INTO {$this->table} 
                     (user_id, session_id, message_id, input_tokens, output_tokens, cost_usd, model) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$userId, $sessionId, $messageId, $inputTokens, $outputTokens, $cost, $model]);
            
        } catch (PDOException $e) {
            error_log("Usage logging error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate cost based on token usage and model
     * 
     * @param int $inputTokens
     * @param int $outputTokens
     * @param string $model
     * @return float
     */
    private function calculateCost($inputTokens, $outputTokens, $model) {
        $pricing = [
            'gpt-4o-mini' => [
                'input' => 0.000150,   // per 1K tokens
                'output' => 0.000600   // per 1K tokens
            ],
            'gpt-4o' => [
                'input' => 0.005,
                'output' => 0.015
            ],
            'gpt-3.5-turbo' => [
                'input' => 0.001,
                'output' => 0.002
            ]
        ];

        $modelPricing = $pricing[$model] ?? $pricing['gpt-4o-mini'];
        
        $inputCost = ($inputTokens / 1000) * $modelPricing['input'];
        $outputCost = ($outputTokens / 1000) * $modelPricing['output'];
        
        return $inputCost + $outputCost;
    }

    /**
     * Get user's monthly usage
     * 
     * @param int $userId
     * @param string $month Format: 'YYYY-MM'
     * @return array
     */
    public function getMonthlyUsage($userId, $month = null) {
        try {
            if (!$month) {
                $month = date('Y-m');
            }
            
            $query = "SELECT 
                        COUNT(*) as message_count,
                        SUM(input_tokens) as total_input_tokens,
                        SUM(output_tokens) as total_output_tokens,
                        SUM(cost_usd) as total_cost
                      FROM {$this->table} 
                      WHERE user_id = ? 
                      AND DATE_FORMAT(created_at, '%Y-%m') = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $month]);
            
            return $stmt->fetch() ?: [
                'message_count' => 0,
                'total_input_tokens' => 0,
                'total_output_tokens' => 0,
                'total_cost' => 0.00
            ];
            
        } catch (PDOException $e) {
            error_log("Get monthly usage error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get system-wide usage statistics
     * 
     * @param string $period 'daily', 'monthly', 'total'
     * @return array
     */
    public function getSystemUsage($period = 'monthly') {
        try {
            $dateCondition = '';
            switch ($period) {
                case 'daily':
                    $dateCondition = "WHERE DATE(created_at) = CURDATE()";
                    break;
                case 'monthly':
                    $dateCondition = "WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
                    break;
                case 'total':
                default:
                    $dateCondition = "";
                    break;
            }
            
            $query = "SELECT 
                        COUNT(DISTINCT user_id) as active_users,
                        COUNT(*) as total_messages,
                        SUM(input_tokens) as total_input_tokens,
                        SUM(output_tokens) as total_output_tokens,
                        SUM(cost_usd) as total_cost,
                        AVG(cost_usd) as avg_cost_per_message
                      FROM {$this->table} {$dateCondition}";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch() ?: [];
            
        } catch (PDOException $e) {
            error_log("Get system usage error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user exceeds monthly limit
     * 
     * @param int $userId
     * @param float $monthlyLimit USD
     * @return bool
     */
    public function checkUserLimit($userId, $monthlyLimit = 10.00) {
        $usage = $this->getMonthlyUsage($userId);
        return $usage['total_cost'] >= $monthlyLimit;
    }

    /**
     * Get top users by usage
     * 
     * @param int $limit
     * @param string $month
     * @return array
     */
    public function getTopUsers($limit = 10, $month = null) {
        try {
            if (!$month) {
                $month = date('Y-m');
            }
            
            $query = "SELECT 
                        u.username,
                        ul.user_id,
                        COUNT(*) as message_count,
                        SUM(ul.cost_usd) as total_cost
                      FROM {$this->table} ul
                      JOIN users u ON ul.user_id = u.id
                      WHERE DATE_FORMAT(ul.created_at, '%Y-%m') = ?
                      GROUP BY ul.user_id, u.username
                      ORDER BY total_cost DESC
                      LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$month, $limit]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get top users error: " . $e->getMessage());
            return [];
        }
    }
}
?>
