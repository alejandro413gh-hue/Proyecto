<?php
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

function reportes_json_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    if (ob_get_length() > 0) {
        ob_clean();
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($error['type'] ?? 0, $fatalTypes, true)) {
        return;
    }

    error_log('Telegram reportes fatal: ' . ($error['message'] ?? 'Error desconocido'));

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    if (ob_get_length() > 0) {
        ob_clean();
    }

    echo json_encode([
        'success' => false,
        'error' => 'Error interno al generar el reporte.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

try {
    $baseDir = dirname(__DIR__, 2);

    define('APP_SKIP_SESSION_BOOTSTRAP', true);
    define('DB_THROW_EXCEPTIONS', true);

    require_once $baseDir . '/config/config.php';
    require_once $baseDir . '/models/TelegramBot.php';

    $secret = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
    $expectedSecret = defined('TELEGRAM_REPORT_SECRET') ? trim((string) TELEGRAM_REPORT_SECRET) : '';
    if ($expectedSecret !== '' && (!hash_equals($expectedSecret, $secret))) {
        reportes_json_response(['success' => false, 'error' => 'Token inválido o ausente.'], 403);
    }

    $action = strtolower(trim((string) ($_GET['action'] ?? $_POST['action'] ?? 'ambos')));
    $allowed = ['ambos', 'stock', 'pedidos', 'ia', 'ai', 'analysis'];
    if (!in_array($action, $allowed, true)) {
        $action = 'ambos';
    }

    if (in_array($action, ['ia', 'ai', 'analysis'], true)) {
        require_once $baseDir . '/models/ReporteIAService.php';
        $service = new ReporteIAService();
        $resultado = $service->generarAnalisis(true);
        if (!($resultado['success'] ?? false)) {
            reportes_json_response($resultado, 500);
        }

        reportes_json_response([
            'success' => true,
            'message' => $resultado['message'] ?? 'Análisis IA generado correctamente.',
            'provider' => $resultado['provider'] ?? null,
            'model' => $resultado['model'] ?? null,
            'telegram' => $resultado['telegram'] ?? null,
            'markdown_length' => $resultado['markdown_length'] ?? null,
        ]);
    }

    $bot = new TelegramBot();
    if (!$bot->configured()) {
        reportes_json_response(['success' => false, 'error' => 'Telegram no configurado'], 500);
    }

    $resultado = $bot->enviarReporte($action, 5);
    reportes_json_response(is_array($resultado) ? $resultado : [
        'success' => false,
        'error' => 'Respuesta inválida del bot.',
    ]);
} catch (Throwable $t) {
    error_log('Telegram reportes error: ' . $t->getMessage());
    reportes_json_response([
        'success' => false,
        'error' => $t->getMessage(),
    ], 500);
}
?>
