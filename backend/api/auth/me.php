<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../middleware/CorsMiddleware.php';
require_once __DIR__ . '/../../middleware/JwtMiddleware.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

// Handle CORS
CorsMiddleware::handle();
CorsMiddleware::setJsonHeaders();

/**
 * Get Current User Endpoint
 * 
 * This endpoint returns information about the currently authenticated user.
 * It requires a valid JWT token in the Authorization header.
 */

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Require authentication - this will exit if no valid token
    $tokenData = JwtMiddleware::requireAuth();
    
    // Create user instance and get user data
    $user = new User();
    $userData = $user->findById($tokenData->user_id);
    
    if (!$userData) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'User not found']);
        exit();
    }
    
    // Return user data (without sensitive information)
    http_response_code(200); // OK
    echo json_encode([
        'user' => [
            'id' => $userData['id'],
            'username' => $userData['username'],
            'email' => $userData['email'],
            'created_at' => $userData['created_at']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get user error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
