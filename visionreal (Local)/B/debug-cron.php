<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/TelegramBot.php';

$bot = new TelegramBot();
$result = $bot->sendCronDigestIfChanged(3, 10);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>