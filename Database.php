<?php

declare(strict_types=1);

# Singleton mysqli wrapper; reads includes/db_config.php when present.

class Database
{
    private static ?Database $instance = null;

    private \mysqli $connection;

    private function __construct()
    {
        $configPath = __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "db_config.php";
        $cfg = is_file($configPath)
            ? require $configPath
            : ["host" => "localhost", "user" => "root", "pass" => "", "name" => "login"];

        $hostname = $cfg["host"] ?? "localhost";
        $username = $cfg["user"] ?? "root";
        $password = $cfg["pass"] ?? "";
        $dbname   = $cfg["name"] ?? "login";

        $this->connection = new \mysqli($hostname, $username, $password, $dbname);

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8mb4");
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    public function getConnection(): \mysqli
    {
        return $this->connection;
    }

    public function close(): void
    {
        if ($this->connection->ping()) {
            $this->connection->close();
        }
    }
}
