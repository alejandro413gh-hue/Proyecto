<?php
require_once __DIR__ . '/config/config.php';

$url = TELEGRAM_API_BASE_URL . '/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
$payload = [
    'chat_id' => TELEGRAM_GROUP_CHAT_ID,
    'text' => '✅ Test al grupo - ' . date('Y-m-d H:i:s')
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
echo $response;
?>