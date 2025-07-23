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
 * User Login Endpoint
 * 
 * This endpoint handles user authentication.
 * It expects a POST request with JSON data containing email and password.
 */

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['email']) || !isset($input['password'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Email and password are required']);
    exit();
}

$email = trim($input['email']);
$password = $input['password'];

// Basic validation
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and password are required'
    ]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email format'
    ]);
    exit();
}

try {
    // Create user instance
    $user = new User();
    
    // Find user by email
    $userData = $user->findByEmail($email);
    
    if (!$userData) {
        http_response_code(401); // Unauthorized
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid credentials'
        ]);
        exit();
    }
    
    // Verify password
    if (!$user->verifyPassword($password, $userData['password_hash'])) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid credentials'
        ]);
        exit();
    }
    
    // Generate JWT token
    $tokenData = [
        'id' => $userData['id'],
        'username' => $userData['username'],
        'email' => $userData['email']
    ];
    
    $token = JwtMiddleware::generateToken($tokenData);
    
    // Return success response
    http_response_code(200); // OK
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'data' => [
            'user' => [
                'id' => $userData['id'],
                'name' => $userData['username'],
                'email' => $userData['email'],
                'created_at' => $userData['created_at'] ?? date('Y-m-d H:i:s')
            ],
            'token' => $token
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error'
    ]);
}
?>
