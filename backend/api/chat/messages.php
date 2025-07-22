<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../models/Chat.php';
require_once __DIR__ . '/../../middleware/CorsMiddleware.php';
require_once __DIR__ . '/../../middleware/JwtMiddleware.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->load();

// Handle CORS
CorsMiddleware::handle();
CorsMiddleware::setJsonHeaders();

// Require authentication
$userData = JwtMiddleware::requireAuth();

/**
 * Get Chat Messages API
 * 
 * GET /api/chat/messages/{session_id}
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get session ID from URL
$uri = $_SERVER['REQUEST_URI'];
$uriParts = explode('/', trim($uri, '/'));
$sessionId = end($uriParts);

if (!is_numeric($sessionId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session ID']);
    exit();
}

$sessionId = (int)$sessionId;

try {
    // Initialize models
    $chatSession = new ChatSession();
    $message = new Message();
    
    // Verify session belongs to user
    $session = $chatSession->getSession($sessionId, $userData->user_id);
    if (!$session) {
        http_response_code(404);
        echo json_encode(['error' => 'Chat session not found']);
        exit();
    }
    
    // Get messages
    $messages = $message->getSessionMessages($sessionId);
    
    echo json_encode([
        'success' => true,
        'session' => $session,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    error_log("Get messages error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
