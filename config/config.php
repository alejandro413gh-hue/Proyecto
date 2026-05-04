<?php
// ✅ CORREGIDO: Detectar automáticamente el dominio
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_dir = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . '://' . $host . rtrim($base_dir, '/'));

define('APP_NAME', 'Visión Real');
date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
?>
