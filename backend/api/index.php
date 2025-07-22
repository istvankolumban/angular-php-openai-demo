<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Handle CORS
CorsMiddleware::handle();
CorsMiddleware::setJsonHeaders();

/**
 * Simple API Router
 * 
 * This file routes API requests to the appropriate endpoints
 */

// Get the request URI and parse it
$requestUri = $_SERVER['REQUEST_URI'];

// Remove query string and get clean path
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Split path into segments
$segments = explode('/', $path);

// Route to appropriate endpoint
switch ($segments[0]) {
    case 'auth':
        if (isset($segments[1])) {
            $authFile = __DIR__ . '/auth/' . $segments[1] . '.php';
            if (file_exists($authFile)) {
                include $authFile;
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Auth endpoint not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Auth endpoint not specified']);
        }
        break;
        
    case 'chat':
        if (isset($segments[1])) {
            $chatFile = __DIR__ . '/chat/' . $segments[1] . '.php';
            if (file_exists($chatFile)) {
                include $chatFile;
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Chat endpoint not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Chat endpoint not specified']);
        }
        break;
        
    case 'test':
        include __DIR__ . '/test.php';
        break;
        
    case '':
        // API root - show available endpoints
        echo json_encode([
            'message' => 'Angular PHP OpenAI Demo API',
            'version' => '1.0.0',
            'endpoints' => [
                'auth' => [
                    'POST /api/auth/register' => 'User registration',
                    'POST /api/auth/login' => 'User login',
                    'GET /api/auth/me' => 'Get current user info'
                ],
                'chat' => [
                    'GET /api/chat/sessions' => 'Get user chat sessions',
                    'POST /api/chat/sessions' => 'Create new chat session',
                    'GET /api/chat/messages/{session_id}' => 'Get messages for session',
                    'POST /api/chat/message' => 'Send message and get AI response'
                ],
                'test' => [
                    'GET /api/test' => 'Test API connection'
                ]
            ]
        ]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}
?>
