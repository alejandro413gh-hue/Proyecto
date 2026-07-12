<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/TelegramBot.php';

$bot = new TelegramBot();

// Construir reporte
$message = $bot->buildMessage('ambos', 3, 10);

// Enviar directamente sin deduplicación
$url = TELEGRAM_API_BASE_URL . '/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
$payload = [
    'chat_id' => TELEGRAM_GROUP_CHAT_ID,
    'text' => $message,
    'parse_mode' => 'HTML'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => ($httpCode == 200 && ($result['ok'] ?? false)),
    'http_code' => $httpCode,
    'response' => $result
], JSON_UNESCAPED_UNICODE);
?>