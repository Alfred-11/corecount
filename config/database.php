<?php
/**
 * Database Configuration File
 * CoreCount Fitness Planner
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'corecount';
    private $username = 'root';
    private $password = ''; // Default WAMP password is empty
    private $conn;
    
    /**
     * Get database connection
     * @return PDO connection object
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        
        return $this->conn;
    }
}
?>