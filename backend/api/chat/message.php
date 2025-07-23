<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../models/Chat.php';
require_once __DIR__ . '/../../models/OpenAIService.php';
require_once __DIR__ . '/../../models/UsageTracker.php';
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
 * Chat Messages API
 * 
 * This endpoint handles sending messages and getting AI responses
 * POST /api/chat/message
 * 
 * Expected JSON body: {
 *   "session_id": number,
 *   "message": "string"
 * }
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['session_id']) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: session_id, message'
    ]);
    exit();
}

$sessionId = (int)$input['session_id'];
$userMessage = trim($input['message']);

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Message cannot be empty'
    ]);
    exit();
}

try {
    // Initialize models
    $chatSession = new ChatSession();
    $message = new Message();
    $openai = new OpenAIService();
    $usageTracker = new UsageTracker();
    
    // Check user's monthly usage limit (optional)
    if ($usageTracker->checkUserLimit($userData->user_id, 50.00)) { // $50 monthly limit
        http_response_code(429); // Too Many Requests
        echo json_encode([
            'status' => 'error',
            'message' => 'Monthly usage limit exceeded'
        ]);
        exit();
    }
    
    // Verify session belongs to user
    $session = $chatSession->getSession($sessionId, $userData->user_id);
    if (!$session) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Chat session not found'
        ]);
        exit();
    }
    
    // Save user message
    $userMessageId = $message->create($sessionId, 'user', $userMessage);
    if (!$userMessageId) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to save user message'
        ]);
        exit();
    }
    
    // Get conversation history and thread ID
    $messages = $message->getSessionMessages($sessionId);
    $threadId = $session['thread_id'];
    
    // Check if OpenAI is configured
    if (!$openai->isConfigured()) {
        // Return a mock response if OpenAI is not configured
        $aiResponse = "I'm a demo bot! Your OpenAI API key is not configured yet. Please add your OpenAI API key to the .env file to enable real AI responses.";
        
        $aiMessageId = $message->create($sessionId, 'assistant', $aiResponse);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Message sent successfully',
            'data' => [
                'response' => $aiResponse,
                'user_message_id' => $userMessageId,
                'ai_message_id' => $aiMessageId,
                'is_demo' => true
            ]
        ]);
        exit();
    }
    
    // Send message to OpenAI Assistant
    $aiResult = $openai->sendMessageToAssistant($userMessage, $threadId);
    
    if (!$aiResult['success']) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $aiResult['error']
        ]);
        exit();
    }
    
    // Update session with thread ID if it's new
    if ($threadId === null && isset($aiResult['thread_id'])) {
        $chatSession->updateThreadId($sessionId, $userData->user_id, $aiResult['thread_id']);
    }
    
    // Save AI response
    $aiResponse = $aiResult['message'];
    $aiMessageId = $message->create($sessionId, 'assistant', $aiResponse);
    
    if (!$aiMessageId) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to save AI response'
        ]);
        exit();
    }
    
    // Log usage for cost tracking (estimate tokens for now)
    $inputTokens = strlen($userMessage) / 4; // Rough estimation: 4 chars = 1 token
    $outputTokens = strlen($aiResponse) / 4;
    $usageTracker->logUsage($userData->user_id, $sessionId, $aiMessageId, $inputTokens, $outputTokens);
    
    // Return successful response
    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent successfully',
        'data' => [
            'response' => $aiResponse,
            'user_message_id' => $userMessageId,
            'ai_message_id' => $aiMessageId,
            'thread_id' => $aiResult['thread_id'],
            'run_id' => $aiResult['run_id'] ?? null,
            'usage' => [
                'estimated_input_tokens' => $inputTokens,
                'estimated_output_tokens' => $outputTokens,
                'estimated_cost' => ($inputTokens * 0.00015 + $outputTokens * 0.0006) / 1000
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Chat message error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error'
    ]);
}
?>
