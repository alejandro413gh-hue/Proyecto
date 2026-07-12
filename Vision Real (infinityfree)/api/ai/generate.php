<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

define('APP_SKIP_SESSION_BOOTSTRAP', true);
require_once __DIR__ . '/../../config/config.php';

function ai_gateway_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    if (ob_get_length() > 0) {
        ob_clean();
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$expectedToken = '';
if (defined('AI_GATEWAY_TOKEN') && trim((string) AI_GATEWAY_TOKEN) !== '') {
    $expectedToken = trim((string) AI_GATEWAY_TOKEN);
} elseif (defined('AI_REMOTE_TOKEN') && trim((string) AI_REMOTE_TOKEN) !== '') {
    $expectedToken = trim((string) AI_REMOTE_TOKEN);
}
if ($expectedToken !== '') {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $providedToken = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ($headers['X-AI-Gateway-Token'] ?? $headers['x-ai-gateway-token'] ?? '')));
    if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
        ai_gateway_response(['success' => false, 'error' => 'Token inválido o ausente.'], 403);
    }
}

$input = file_get_contents('php://input');
$payload = [];
if (is_string($input) && trim($input) !== '') {
    $decoded = json_decode($input, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}
if (empty($payload)) {
    $payload = $_POST;
}

$prompt = trim((string) ($payload['prompt'] ?? ''));
if ($prompt === '') {
    ai_gateway_response(['success' => false, 'error' => 'Prompt requerido.'], 400);
}

$model = trim((string) ($payload['model'] ?? (defined('AI_OLLAMA_MODEL') ? AI_OLLAMA_MODEL : 'gemma4:latest')));
$system = trim((string) ($payload['system'] ?? ''));
$temperature = isset($payload['options']['temperature']) ? (float) $payload['options']['temperature'] : (defined('AI_OLLAMA_TEMPERATURE') ? (float) AI_OLLAMA_TEMPERATURE : 0.2);
$baseUrl = rtrim((string) (defined('AI_OLLAMA_BASE_URL') ? AI_OLLAMA_BASE_URL : 'http://localhost:11434'), '/');
$timeout = (int) (defined('AI_OLLAMA_TIMEOUT') ? AI_OLLAMA_TIMEOUT : 120);

if (!function_exists('curl_init')) {
    ai_gateway_response(['success' => false, 'error' => 'La extensión cURL no está disponible.'], 500);
}

$ollamaPayload = [
    'model' => $model,
    'prompt' => $prompt,
    'stream' => false,
    'options' => [
        'temperature' => $temperature,
    ],
];
if ($system !== '') {
    $ollamaPayload['system'] = $system;
}

$ch = curl_init($baseUrl . '/api/generate');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($ollamaPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => $timeout,
]);

$raw = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false || $errno !== 0) {
    ai_gateway_response(['success' => false, 'error' => $error ?: 'No se pudo conectar con Ollama.'], 502);
}

$decoded = json_decode((string) $raw, true);
if (!is_array($decoded)) {
    ai_gateway_response(['success' => false, 'error' => 'Respuesta inválida de Ollama.'], 502);
}

if (($decoded['response'] ?? '') === '') {
    ai_gateway_response(['success' => false, 'error' => 'Ollama no devolvió texto utilizable.', 'raw' => $decoded], 502);
}

ai_gateway_response([
    'success' => true,
    'response' => $decoded['response'],
    'model' => $decoded['model'] ?? $model,
    'source' => 'ollama_gateway',
    'http_code' => $httpCode,
], 200);
?>
