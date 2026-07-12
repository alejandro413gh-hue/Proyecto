<?php
// Test básico
echo "✅ PHP funciona\n";

require_once __DIR__ . '/config/config.php';
echo "✅ Config cargado\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "TELEGRAM_API_BASE_URL: " . TELEGRAM_API_BASE_URL . "\n";
?>