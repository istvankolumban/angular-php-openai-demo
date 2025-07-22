<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/UsageTracker.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';
require_once __DIR__ . '/../middleware/JwtMiddleware.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Handle CORS
CorsMiddleware::handle();
CorsMiddleware::setJsonHeaders();

// Require authentication
$userData = JwtMiddleware::requireAuth();

/**
 * Usage Statistics API
 * 
 * GET /api/usage - Get usage statistics
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    $usageTracker = new UsageTracker();
    
    // Get query parameters
    $type = $_GET['type'] ?? 'user'; // 'user' or 'system'
    $period = $_GET['period'] ?? 'monthly'; // 'daily', 'monthly', 'total'
    
    if ($type === 'user') {
        // Get current user's usage
        $monthlyUsage = $usageTracker->getMonthlyUsage($userData->user_id);
        
        echo json_encode([
            'success' => true,
            'user_id' => $userData->user_id,
            'username' => $userData->username,
            'current_month' => date('Y-m'),
            'usage' => $monthlyUsage,
            'limits' => [
                'monthly_cost_limit' => 50.00, // $50 limit
                'remaining_budget' => max(0, 50.00 - $monthlyUsage['total_cost'])
            ]
        ]);
        
    } elseif ($type === 'system') {
        // Get system-wide usage (admin only - for demo purposes, allow all)
        $systemUsage = $usageTracker->getSystemUsage($period);
        $topUsers = $usageTracker->getTopUsers(5);
        
        echo json_encode([
            'success' => true,
            'period' => $period,
            'system_usage' => $systemUsage,
            'top_users' => $topUsers,
            'generated_at' => date('Y-m-d H:i:s')
        ]);
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type parameter']);
    }
    
} catch (Exception $e) {
    error_log("Usage statistics error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
