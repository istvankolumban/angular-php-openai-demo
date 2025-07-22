<?php
/**
 * CORS (Cross-Origin Resource Sharing) Middleware
 * 
 * This handles CORS headers to allow our Angular frontend to communicate 
 * with our PHP backend API. CORS is a security feature implemented by 
 * web browsers to restrict web pages from making requests to a different 
 * domain than the one serving the web page.
 */
class CorsMiddleware {
    
    /**
     * Handle CORS headers
     * 
     * This method sets the necessary headers to allow cross-origin requests
     * from our Angular frontend.
     */
    public static function handle() {
        // Get the frontend URL from environment variables
        $frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:4200';
        
        // Set CORS headers
        header("Access-Control-Allow-Origin: " . $frontendUrl);
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400"); // 24 hours
        
        // Handle preflight OPTIONS requests
        // Browsers send an OPTIONS request before the actual request 
        // to check if the cross-origin request is allowed
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * Set JSON content type header
     */
    public static function setJsonHeaders() {
        header('Content-Type: application/json; charset=utf-8');
    }
}
?>
