<?php
/**
 * Thread Management API
 * 
 * Provides bulk operations and maintenance for threads
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

// Require authentication
$userData = JwtMiddleware::authenticate();
if (!$userData) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            handleBulkOperations($userData['user_id']);
            break;
            
        case 'GET':
            handleMaintenanceInfo($userData['user_id']);
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
 * Handle bulk operations
 */
function handleBulkOperations($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Action required']);
        return;
    }
    
    $chatSession = new ChatSession();
    
    switch ($input['action']) {
        case 'archive_inactive':
            $days = $input['days'] ?? 30;
            $archived = $chatSession->autoArchiveInactive($days);
            
            echo json_encode([
                'success' => true,
                'message' => "Archived $archived inactive sessions",
                'archived_count' => $archived
            ]);
            break;
            
        case 'bulk_archive':
            if (!isset($input['session_ids']) || !is_array($input['session_ids'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Session IDs array required']);
                return;
            }
            
            $archived = 0;
            foreach ($input['session_ids'] as $sessionId) {
                if ($chatSession->archiveSession($sessionId, $userId)) {
                    $archived++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Archived $archived sessions",
                'archived_count' => $archived
            ]);
            break;
            
        case 'bulk_delete':
            if (!isset($input['session_ids']) || !is_array($input['session_ids'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Session IDs array required']);
                return;
            }
            
            $deleted = 0;
            foreach ($input['session_ids'] as $sessionId) {
                if ($chatSession->deleteSession($sessionId, $userId)) {
                    $deleted++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Deleted $deleted sessions",
                'deleted_count' => $deleted
            ]);
            break;
            
        case 'bulk_categorize':
            if (!isset($input['session_ids']) || !is_array($input['session_ids']) || !isset($input['category'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Session IDs and category required']);
                return;
            }
            
            $updated = 0;
            foreach ($input['session_ids'] as $sessionId) {
                if ($chatSession->updateSession($sessionId, $userId, ['category' => $input['category']])) {
                    $updated++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Updated $updated sessions to category: {$input['category']}",
                'updated_count' => $updated
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
            break;
    }
}

/**
 * Handle maintenance info requests
 */
function handleMaintenanceInfo($userId) {
    $chatSession = new ChatSession();
    $messageModel = new Message();
    
    // Get overall statistics
    $stats = $chatSession->getUserStats($userId);
    $categories = $chatSession->getUserCategories($userId);
    
    // Get sessions that could be archived (inactive for 30+ days)
    $inactiveSessions = $chatSession->getUserSessions($userId, 'active');
    $candidatesForArchive = [];
    
    $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
    
    foreach ($inactiveSessions as $session) {
        $lastActivity = $session['last_message_at'] ?: $session['created_at'];
        if ($lastActivity < $thirtyDaysAgo) {
            $candidatesForArchive[] = $session;
        }
    }
    
    // Calculate storage usage (approximate)
    $totalMessages = $stats['total_messages'] ?? 0;
    $avgMessageSize = 200; // bytes
    $estimatedStorageKB = ($totalMessages * $avgMessageSize) / 1024;
    
    echo json_encode([
        'success' => true,
        'maintenance_info' => [
            'total_sessions' => $stats['total_sessions'] ?? 0,
            'active_sessions' => $stats['active_sessions'] ?? 0,
            'archived_sessions' => $stats['archived_sessions'] ?? 0,
            'total_messages' => $totalMessages,
            'categories_used' => $stats['categories_used'] ?? 0,
            'last_activity' => $stats['last_activity'] ?? null,
            'candidates_for_archive' => count($candidatesForArchive),
            'estimated_storage_kb' => round($estimatedStorageKB, 2),
            'archive_candidates' => $candidatesForArchive
        ],
        'categories' => $categories
    ]);
}
?>
