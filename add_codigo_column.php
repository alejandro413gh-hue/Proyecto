<?php
require_once __DIR__ . '/../config/config.php';

// Agregar columna 'codigo' a productos si no existe
$db = Database::getInstance()->getConnection();
$check = $db->query("SHOW COLUMNS FROM productos LIKE 'codigo'");
if ($check->num_rows === 0) {
    $db->query("ALTER TABLE productos ADD COLUMN codigo VARCHAR(20) UNIQUE NULL AFTER id");
    echo "Columna 'codigo' agregada a productos.\n";
} else {
    echo "Columna 'codigo' ya existe.\n";
}
?>