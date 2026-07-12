<?php
if (!defined('APP_ENV')) {
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    define('APP_ENV', (php_sapi_name() === 'cli' || strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || strpos($host, '::1') !== false) ? 'local' : 'production');
}

if (APP_ENV === 'local') {
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'if0_41735490_vision_real');
} else {
    define('DB_HOST',    'sql209.infinityfree.com');  // Host correcto de InfinityFree
    define('DB_USER',    'if0_41735490');              // Una sola 'f' (corregido)
    define('DB_PASS',    '1091972715gh');
    define('DB_NAME',    'if0_41735490_vision_real');  // Una sola 'f' (corregido)
}
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT',    3306);

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($this->connection->connect_error) {
            error_log('DB Connection Error: ' . $this->connection->connect_error);
            if (defined('DB_THROW_EXCEPTIONS') && DB_THROW_EXCEPTIONS === true) {
                throw new RuntimeException('No se pudo conectar a la base de datos.');
            }
            die('<div style="font-family:sans-serif;padding:30px;color:#c0392b">
                <h2>Error de conexión a BD</h2>
                <p>No se puede conectar a la base de datos.</p>
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

    public function ensureTableCollation(string $tableName): void {
        $sql = "SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $this->connection->real_escape_string(DB_NAME) . "' AND TABLE_NAME = '" . $this->connection->real_escape_string($tableName) . "'";
        $result = $this->connection->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!in_array($row['TABLE_COLLATION'], ['utf8mb4_unicode_ci', 'utf8mb4_general_ci'], true)) {
                $this->connection->query("ALTER TABLE `{$tableName}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
    }
}
?>
