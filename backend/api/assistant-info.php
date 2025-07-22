<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/OpenAIService.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Handle CORS
CorsMiddleware::handle();
CorsMiddleware::setJsonHeaders();

/**
 * Assistant Info Endpoint
 * 
 * GET /api/assistant/info - Get information about the configured assistant
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    $openai = new OpenAIService();
    
    if (!$openai->isConfigured()) {
        echo json_encode([
            'configured' => false,
            'message' => 'OpenAI API key not configured'
        ]);
        exit();
    }
    
    $assistantInfo = $openai->getAssistantInfo();
    
    if ($assistantInfo['success']) {
        echo json_encode([
            'configured' => true,
            'assistant' => $assistantInfo['assistant']
        ]);
    } else {
        echo json_encode([
            'configured' => false,
            'error' => $assistantInfo['error']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Assistant info error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
