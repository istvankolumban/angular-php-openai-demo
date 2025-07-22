<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JWT (JSON Web Token) Middleware
 * 
 * JWT is a compact, URL-safe means of representing claims to be transferred 
 * between two parties. We use it for user authentication - when a user logs in,
 * we give them a JWT token that they send with each request to prove their identity.
 */
class JwtMiddleware {
    
    private static $secret;
    
    /**
     * Initialize with JWT secret from environment
     */
    public static function init() {
        self::$secret = $_ENV['JWT_SECRET'];
    }
    
    /**
     * Generate JWT token for user
     * 
     * @param array $userData User data to encode in token
     * @return string JWT token
     */
    public static function generateToken($userData) {
        self::init();
        
        $payload = [
            'iat' => time(), // Issued at time
            'exp' => time() + (24 * 60 * 60), // Expiration time (24 hours)
            'user_id' => $userData['id'],
            'username' => $userData['username'],
            'email' => $userData['email']
        ];
        
        return JWT::encode($payload, self::$secret, 'HS256');
    }
    
    /**
     * Verify and decode JWT token
     * 
     * @param string $token JWT token to verify
     * @return object|null Decoded token data or null if invalid
     */
    public static function verifyToken($token) {
        self::init();
        
        try {
            $decoded = JWT::decode($token, new Key(self::$secret, 'HS256'));
            return $decoded;
        } catch (Exception $e) {
            error_log("JWT verification failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Extract token from Authorization header
     * 
     * @return string|null Token or null if not found
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        
        // Check for Authorization header
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            
            // Token should be in format: "Bearer <token>"
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Middleware to protect routes - requires valid JWT token
     * 
     * @return object|null User data from token or null if unauthorized
     */
    public static function requireAuth() {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'No token provided']);
            exit();
        }
        
        $userData = self::verifyToken($token);
        
        if (!$userData) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit();
        }
        
        return $userData;
    }
}
?>
