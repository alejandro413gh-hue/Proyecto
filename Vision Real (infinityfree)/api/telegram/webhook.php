<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$raw = file_get_contents('php://input');
$update = null;
if (!empty($raw)) {
    $update = json_decode($raw, true);
}
if (!is_array($update)) {
    $update = $_POST;
}

if (!is_array($update) || empty($update)) {
    echo json_encode(['success' => true, 'ignored' => true]);
    exit;
}

if (defined('TELEGRAM_WEBHOOK_SECRET') && TELEGRAM_WEBHOOK_SECRET !== '') {
    $secretHeader = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if (!hash_equals(TELEGRAM_WEBHOOK_SECRET, (string) $secretHeader)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }
}

$message = $update['message'] ?? $update['edited_message'] ?? $update['channel_post'] ?? null;
if (!is_array($message)) {
    echo json_encode(['success' => true, 'ignored' => true]);
    exit;
}

$chat = $message['chat'] ?? [];
$chatId = (string)($chat['id'] ?? '');
$text = trim((string)($message['text'] ?? ''));
if ($chatId === '') {
    echo json_encode(['success' => true, 'ignored' => true]);
    exit;
}

require_once __DIR__ . '/../../models/TelegramBot.php';

function telegramSendMessageDirect(string $token, string $chatId, string $text): void {
    if ($token === '' || $chatId === '' || $text === '') {
        return;
    }

    $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $token);
    $payload = http_build_query([
        'chat_id' => $chatId,
        'text' => $text,
        'disable_web_page_preview' => true,
    ]);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]);
        curl_exec($ch);
        curl_close($ch);
        return;
    }

    @file_get_contents($url, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $payload,
            'timeout' => 20,
        ],
    ]));
}

try {
    $bot = new TelegramBot();
    $bot->registerSubscriber($chatId, [
        'first_name' => (string)($chat['first_name'] ?? ($message['from']['first_name'] ?? '')),
        'last_name' => (string)($chat['last_name'] ?? ($message['from']['last_name'] ?? '')),
        'username' => (string)($chat['username'] ?? ($message['from']['username'] ?? '')),
        'chat_type' => (string)($chat['type'] ?? 'private'),
        'language_code' => (string)($message['from']['language_code'] ?? ''),
    ]);

    if ($text !== '' && preg_match('~^/(start|subscribe)(?:@[\w_]+)?(?:\s|$)~i', $text)) {
        telegramSendMessageDirect(
            TELEGRAM_BOT_TOKEN,
            $chatId,
            "Bienvenido a Visión Real.\nTe suscribiste a los reportes automáticos.\nRecibirás avisos de stock bajo, pedidos pendientes y pedidos para revisión."
        );
    } elseif ($text !== '' && preg_match('~^/(stop|unsubscribe)(?:@[\w_]+)?(?:\s|$)~i', $text)) {
        $bot->unregisterSubscriber($chatId);
        telegramSendMessageDirect(
            TELEGRAM_BOT_TOKEN,
            $chatId,
            "Listo, ya no recibirás reportes de Visión Real."
        );
    }
} catch (Throwable $e) {
    error_log('[telegram webhook] ' . $e->getMessage());
}

echo json_encode(['success' => true]);
