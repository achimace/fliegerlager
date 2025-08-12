<?php
// Database.php

class Database
{
    private $connection;
    private $config;

    public function __construct()
    {
        $this->config = include 'config.php';
        $this->connect();
    }

    private function connect()
    {
        $this->connection = new mysqli(
            $this->config['db']['host'],
            $this->config['db']['username'],
            $this->config['db']['password'],
            $this->config['db']['dbname']
        );

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function __destruct()
    {
        $this->connection->close();
    }
}
?>
