<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'mahdiism_adminpanel';
    private $username = 'blackmirror';
    private $password = 'U5qb@VjjFH@RDtANzs85wG';
    private $conn;

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
        return $this->conn;
    }
}

