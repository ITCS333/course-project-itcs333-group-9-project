<?php
/**
 * Database.php: Corrected to use static methods/properties 
 * to match the static call Database::getConnection() in index.php.
 */
class Database {
    // 1. Properties MUST be static
    private static $host = 'localhost';
    private static $db   = 'course';
    private static $user = 'admin';       
    private static $pass = 'password123'; 
    private static $charset = 'utf8';

    // 2. The connection property must also be static
    private static $conn;

    // 3. The method MUST be static
    public static function getConnection() {
        if (self::$conn) return self::$conn;

        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;

        try {
            // Use self:: to access static properties
            self::$conn = new PDO($dsn, self::$user, self::$pass);
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return self::$conn;

        } catch (\PDOException $e) {
            // CRITICAL: Exit cleanly to prevent leaking HTML and breaking JSON
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal Server Error: Database connection failed.']);
            exit(); 
        }
    }
}
?>