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
 * Chat Sessions API
 * 
 * GET /api/chat/sessions - Get all user's chat sessions
 * POST /api/chat/sessions - Create new chat session
 */

$method = $_SERVER['REQUEST_METHOD'];
$chatSession = new ChatSession();

if ($method === 'GET') {
    // Get all user's chat sessions
    $sessions = $chatSession->getUserSessions($userData->user_id);
    
    echo json_encode([
        'success' => true,
        'sessions' => $sessions
    ]);
    
} elseif ($method === 'POST') {
    // Create new chat session
    $input = json_decode(file_get_contents('php://input'), true);
    
    $title = isset($input['title']) ? trim($input['title']) : 'New Chat';
    
    $sessionId = $chatSession->create($userData->user_id, $title);
    
    if ($sessionId) {
        echo json_encode([
            'success' => true,
            'message' => 'Chat session created',
            'session_id' => $sessionId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create chat session']);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
