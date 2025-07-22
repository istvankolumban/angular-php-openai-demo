<?php
/**
 * Enhanced Chat Sessions API
 * 
 * Handles CRUD operations for chat sessions with thread management
 */

require_once __DIR__ . '/../../middleware/CorsMiddleware.php';
require_once __DIR__ . '/../../middleware/JwtMiddleware.php';
require_once __DIR__ . '/../../models/ChatEnhanced.php';

// Handle CORS
CorsMiddleware::handle();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Require authentication for all endpoints
$userData = JwtMiddleware::authenticate();
if (!$userData) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = explode('/', trim($uri, '/'));

// Initialize models
$chatSession = new ChatSession();

try {
    switch ($method) {
        case 'GET':
            handleGetRequests($chatSession, $userData['user_id']);
            break;
            
        case 'POST':
            handlePostRequests($chatSession, $userData['user_id']);
            break;
            
        case 'PUT':
            handlePutRequests($chatSession, $userData['user_id']);
            break;
            
        case 'DELETE':
            handleDeleteRequests($chatSession, $userData['user_id']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Handle GET requests
 */
function handleGetRequests($chatSession, $userId) {
    $query = $_GET;
    
    // Get sessions with optional filtering
    if (isset($query['action']) && $query['action'] === 'stats') {
        // Get user statistics
        $stats = $chatSession->getUserStats($userId);
        $categories = $chatSession->getUserCategories($userId);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'categories' => $categories
        ]);
        return;
    }
    
    if (isset($query['action']) && $query['action'] === 'categories') {
        // Get available categories
        $categories = $chatSession->getUserCategories($userId);
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
        return;
    }
    
    // Get sessions with filtering
    $status = $query['status'] ?? 'active';
    $category = $query['category'] ?? null;
    
    $sessions = $chatSession->getUserSessions($userId, $status, $category);
    
    echo json_encode([
        'success' => true,
        'sessions' => $sessions,
        'total' => count($sessions)
    ]);
}

/**
 * Handle POST requests
 */
function handlePostRequests($chatSession, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }
    
    // Create new session
    $title = $input['title'] ?? 'New Chat';
    $category = $input['category'] ?? 'General';
    $threadId = $input['thread_id'] ?? null;
    
    $sessionId = $chatSession->create($userId, $title, $category, $threadId);
    
    if ($sessionId) {
        $session = $chatSession->getSession($sessionId, $userId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Session created successfully',
            'session' => $session
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to create session. You may have reached the maximum number of active threads (10).']);
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequests($chatSession, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['session_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID required']);
        return;
    }
    
    $sessionId = $input['session_id'];
    
    // Handle different update actions
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'archive':
                $success = $chatSession->archiveSession($sessionId, $userId);
                $message = 'Session archived successfully';
                break;
                
            case 'restore':
                $success = $chatSession->restoreSession($sessionId, $userId);
                $message = 'Session restored successfully';
                break;
                
            case 'update_thread':
                if (!isset($input['thread_id'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Thread ID required']);
                    return;
                }
                $success = $chatSession->updateThreadId($sessionId, $userId, $input['thread_id']);
                $message = 'Thread ID updated successfully';
                break;
                
            default:
                // General update
                $updates = [];
                if (isset($input['title'])) $updates['title'] = $input['title'];
                if (isset($input['category'])) $updates['category'] = $input['category'];
                if (isset($input['status'])) $updates['status'] = $input['status'];
                
                if (empty($updates)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'No valid fields to update']);
                    return;
                }
                
                $success = $chatSession->updateSession($sessionId, $userId, $updates);
                $message = 'Session updated successfully';
                break;
        }
    } else {
        // General update
        $updates = [];
        if (isset($input['title'])) $updates['title'] = $input['title'];
        if (isset($input['category'])) $updates['category'] = $input['category'];
        if (isset($input['status'])) $updates['status'] = $input['status'];
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update']);
            return;
        }
        
        $success = $chatSession->updateSession($sessionId, $userId, $updates);
        $message = 'Session updated successfully';
    }
    
    if ($success) {
        $session = $chatSession->getSession($sessionId, $userId);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'session' => $session
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to update session']);
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequests($chatSession, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['session_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID required']);
        return;
    }
    
    $sessionId = $input['session_id'];
    $success = $chatSession->deleteSession($sessionId, $userId);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Session deleted successfully'
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to delete session']);
    }
}
?>
