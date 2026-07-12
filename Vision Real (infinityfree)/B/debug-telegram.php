<?php
require_once __DIR__ . '/config/config.php';

echo "=== DEBUG TELEGRAM ===\n\n";

echo "1. Verificar configuración:\n";
echo "   TELEGRAM_BOT_TOKEN: " . substr(TELEGRAM_BOT_TOKEN, 0, 20) . "...\n";
echo "   TELEGRAM_CHAT_ID: " . TELEGRAM_CHAT_ID . "\n";
echo "   TELEGRAM_GROUP_CHAT_ID: " . TELEGRAM_GROUP_CHAT_ID . "\n";
echo "   TELEGRAM_API_BASE_URL: " . TELEGRAM_API_BASE_URL . "\n";

echo "\n2. Construir URL:\n";
$url = TELEGRAM_API_BASE_URL . '/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
echo "   URL: " . $url . "\n";

echo "\n3. Intentar enviar mensaje de prueba:\n";
$payload = [
    'chat_id' => TELEGRAM_GROUP_CHAT_ID,
    'text' => '🧪 DEBUG: ' . date('Y-m-d H:i:s')
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: " . $httpCode . "\n";
echo "   Response: " . $response . "\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data['ok']) {
        echo "\n✅ SUCCESS - Mensaje enviado al grupo!\n";
    } else {
        echo "\n❌ ERROR Telegram: " . ($data['description'] ?? 'Desconocido') . "\n";
    }
} else {
    echo "\n❌ ERROR HTTP: " . $httpCode . "\n";
}
?>
