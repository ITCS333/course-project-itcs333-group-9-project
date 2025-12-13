<?php
class Database {
    private static $host = 'localhost';
    private static $db   = 'course';
    private static $user = 'admin';       
    private static $pass = 'password123'; 
    private static $charset = 'utf8';

    private static $conn;

    public static function getConnection() {
        if (self::$conn) return self::$conn;

        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;

        try {
            self::$conn = new PDO($dsn, self::$user, self::$pass);
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return self::$conn;

        } catch (\PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal Server Error: Database connection failed.']);
            exit(); 
        }
    }
}
?>
