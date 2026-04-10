<?php
if (!defined('DB_HOST'))    define('DB_HOST',    'localhost');
if (!defined('DB_USER'))    define('DB_USER',    'root');
if (!defined('DB_PASS'))    define('DB_PASS',    '');
if (!defined('DB_NAME'))    define('DB_NAME',    'vision_real');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            // Registrar el error real en el log del servidor (no mostrarlo al usuario)
            error_log('DB Connection Error: ' . $this->connection->connect_error);
            die('<div style="font-family:sans-serif;padding:30px;color:#c0392b">
                <h2>Error de conexión</h2>
                <p>No se puede conectar a la base de datos. Contacta al administrador.</p>
            </div>');
        }
        $this->connection->set_charset(DB_CHARSET);
    }

    public static function getInstance() {
        if (self::$instance === null) self::$instance = new Database();
        return self::$instance;
    }

    public function getConnection() { return $this->connection; }
    public function query($sql)     { return $this->connection->query($sql); }
    public function prepare($sql)   { return $this->connection->prepare($sql); }
    public function escape($v)      { return $this->connection->real_escape_string($v); }
    public function lastInsertId()  { return $this->connection->insert_id; }
    public function affectedRows()  { return $this->connection->affected_rows; }
}
?>
