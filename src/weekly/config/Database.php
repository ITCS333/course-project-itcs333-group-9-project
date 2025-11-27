<?php
class Database {
    private $host = 'localhost';
    private $db   = 'course';
    private $user = 'admin';
    private $pass = 'password123';
    private $conn;

    public function getConnection() {
        if ($this->conn) return $this->conn;

        $dsn = "mysql:host={$this->host};dbname={$this->db};charset=utf8";

        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // echo "Connected successfully!"; // <-- temporary test
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage(); // <-- see exact error
            exit;
        }

        return $this->conn;
    }
}
?>
