<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

/**
 * Database Configuration Class
 * 
 * This class handles the database connection using PDO.
 * PDO (PHP Data Objects) is a database access layer providing a uniform method 
 * of access to multiple databases.
 */
class Database {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $connection;

    public function __construct() {
        // Get database credentials from environment variables
        $this->host = $_ENV['DB_HOST'];
        $this->dbname = $_ENV['DB_NAME'];
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASS'];
    }

    /**
     * Create database connection
     * 
     * @return PDO|null Returns PDO connection object or null on failure
     */
    public function connect() {
        $this->connection = null;

        try {
            // Create PDO connection
            // PDO constructor: PDO(dsn, username, password, options)
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Return associative arrays
                PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            if ($_ENV['DEBUG'] === 'true') {
                error_log("Database connected successfully");
            }
            
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }

        return $this->connection;
    }

    /**
     * Get the current connection
     * 
     * @return PDO|null
     */
    public function getConnection() {
        if ($this->connection === null) {
            return $this->connect();
        }
        return $this->connection;
    }
}
?>
