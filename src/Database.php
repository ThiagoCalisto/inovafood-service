<?php

namespace App;

use PDO;
use PDOException;

class Database {
    private $host = 'localhost';
    private $db_name = 'avaliacao_db';
    private $username = 'username'; // Substitua por seu nome de usuário real
    private $password = '85857946'; // Substitua pela sua senha real
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->db_name}", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
