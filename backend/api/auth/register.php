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
 * User Registration Endpoint
 * 
 * This endpoint handles user registration.
 * It expects a POST request with JSON data containing username, email, and password.
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
if (!isset($input['name']) || !isset($input['email']) || !isset($input['password'])) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'error',
        'message' => 'Name, email, and password are required'
    ]);
    exit();
}

$username = trim($input['name']);
$email = trim($input['email']);
$password = $input['password'];

// Basic validation
if (empty($username) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required'
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

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 6 characters long'
    ]);
    exit();
}

try {
    // Create user instance
    $user = new User();
    
    // Check if email already exists
    if ($user->emailExists($email)) {
        http_response_code(409); // Conflict
        echo json_encode([
            'status' => 'error',
            'message' => 'Email already exists'
        ]);
        exit();
    }
    
    // Check if username already exists
    if ($user->usernameExists($username)) {
        http_response_code(409);
        echo json_encode([
            'status' => 'error',
            'message' => 'Username already exists'
        ]);
        exit();
    }
    
    // Create the user
    if ($user->create($username, $email, $password)) {
        // Generate JWT token
        $userData = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email
        ];
        
        $token = JwtMiddleware::generateToken($userData);
        
        // Return success response
        http_response_code(201); // Created
        echo json_encode([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->username,
                    'email' => $user->email,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                'token' => $token
            ]
        ]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create user'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error'
    ]);
}
?>
