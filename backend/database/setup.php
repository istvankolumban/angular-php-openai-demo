<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

echo "Setting up database for Angular PHP OpenAI Demo...\n\n";

try {
    // Connect to MySQL without specifying database first
    $host = $_ENV['DB_HOST'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];
    $dbname = $_ENV['DB_NAME'];
    
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server...\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "Database '$dbname' created or already exists...\n";
    
    // Select the database
    $pdo->exec("USE $dbname");
    
    // Read and execute schema file
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    
    // Remove comments and split by semicolon
    $schema = preg_replace('/--.*$/m', '', $schema); // Remove comments
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement: " . $statement . "\n";
            }
        }
    }
    
    echo "Database schema created successfully!\n\n";
    
    // Test connection with Database class
    require_once __DIR__ . '/../config/Database.php';
    $db = new Database();
    $conn = $db->connect();
    
    if ($conn) {
        echo "✅ Database connection test successful!\n";
        
        // Check if tables exist
        $tables = ['users', 'chat_sessions', 'messages'];
        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table '$table' exists\n";
            } else {
                echo "❌ Table '$table' missing\n";
            }
        }
    } else {
        echo "❌ Database connection failed!\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
}
?>
