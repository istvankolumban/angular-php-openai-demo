<?php
/**
 * Message Search API
 * 
 * Provides search functionality across user's messages and threads
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    $query = $_GET['q'] ?? '';
    $limit = min((int)($_GET['limit'] ?? 20), 50); // Max 50 results
    
    if (empty($query) || strlen($query) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Search query must be at least 2 characters']);
        exit();
    }
    
    $messageModel = new Message();
    $results = $messageModel->searchUserMessages($userData['user_id'], $query, $limit);
    
    // Group results by session for better presentation
    $groupedResults = [];
    foreach ($results as $result) {
        $sessionId = $result['session_id'];
        if (!isset($groupedResults[$sessionId])) {
            $groupedResults[$sessionId] = [
                'session_id' => $sessionId,
                'session_title' => $result['session_title'],
                'category' => $result['category'],
                'messages' => []
            ];
        }
        
        $groupedResults[$sessionId]['messages'][] = [
            'id' => $result['id'],
            'content' => $result['content'],
            'role' => $result['role'],
            'created_at' => $result['created_at'],
            'snippet' => createSnippet($result['content'], $query)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'total_results' => count($results),
        'sessions' => array_values($groupedResults)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
}

/**
 * Create a snippet around the search term
 */
function createSnippet($content, $searchTerm, $contextLength = 100) {
    $pos = stripos($content, $searchTerm);
    if ($pos === false) {
        return substr($content, 0, $contextLength * 2) . (strlen($content) > $contextLength * 2 ? '...' : '');
    }
    
    $start = max(0, $pos - $contextLength);
    $length = $contextLength * 2 + strlen($searchTerm);
    
    $snippet = substr($content, $start, $length);
    
    if ($start > 0) $snippet = '...' . $snippet;
    if ($start + $length < strlen($content)) $snippet .= '...';
    
    // Highlight the search term
    $snippet = str_ireplace($searchTerm, "<mark>$searchTerm</mark>", $snippet);
    
    return $snippet;
}
?>
