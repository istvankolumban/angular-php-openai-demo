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
 * Test Endpoint
 * 
 * This is a simple endpoint to test if our PHP API is working correctly.
 */

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(200);
    echo json_encode([
        'message' => 'Backend API is working!',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'status' => 'success'
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
