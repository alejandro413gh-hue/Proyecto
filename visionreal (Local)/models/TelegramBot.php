<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/tienda/Pedido.php';

class TelegramBot {
    private Database $db;
    private string $token;
    private string $fallbackChatId;
    private string $groupChatId;
    private string $apiBaseUrl;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->token = defined('TELEGRAM_BOT_TOKEN') ? trim((string) TELEGRAM_BOT_TOKEN) : '';
        $this->fallbackChatId = defined('TELEGRAM_CHAT_ID') ? trim((string) TELEGRAM_CHAT_ID) : '';
        $this->groupChatId = defined('TELEGRAM_GROUP_CHAT_ID') ? trim((string) TELEGRAM_GROUP_CHAT_ID) : '';
        $this->apiBaseUrl = defined('TELEGRAM_API_BASE_URL') ? rtrim(trim((string) TELEGRAM_API_BASE_URL), '/') : 'https://api.telegram.org';
        $this->initTables();
    }

    public function configured(): bool {
        return (defined('TELEGRAM_REPORT_ENABLED') ? (bool) TELEGRAM_REPORT_ENABLED : true)
            && !empty($this->token);
    }

    public function sendMessage(string $message, ?string $parseMode = null): array {
        $message = trim((string) $message);
        if ($message === '') {
            return [
                'success' => false,
                'sent' => 0,
                'chunks' => 0,
                'failed' => [],
                'errors' => ['El mensaje está vacío.'],
            ];
        }

        $parts = $this->splitMessage($message);
        $result = [
            'success' => true,
            'sent' => 0,
            'chunks' => count($parts),
            'failed' => [],
            'errors' => [],
        ];

        foreach ($parts as $index => $part) {
            $chunkResult = $this->broadcast($part, $parseMode);
            if (($chunkResult['success'] ?? false) === true) {
                $result['sent'] += (int)($chunkResult['sent'] ?? 0);
                continue;
            }

            $result['success'] = false;
            $result['failed'][] = $index + 1;
            if (!empty($chunkResult['error'])) {
                $result['errors'][] = $chunkResult['error'];
            }
        }

        if (!$result['success'] && empty($result['errors'])) {
            $result['errors'][] = 'No se pudo enviar el mensaje.';
        }

        return $result;
    }

    private function initTables(): void {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS telegram_subscribers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                chat_id VARCHAR(64) NOT NULL UNIQUE,
                first_name VARCHAR(150) DEFAULT NULL,
                last_name VARCHAR(150) DEFAULT NULL,
                username VARCHAR(150) DEFAULT NULL,
                chat_type VARCHAR(50) DEFAULT NULL,
                language_code VARCHAR(20) DEFAULT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                last_seen_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS telegram_alert_state (
                id INT AUTO_INCREMENT PRIMARY KEY,
                alert_key VARCHAR(80) NOT NULL UNIQUE,
                alert_hash VARCHAR(64) NOT NULL,
                payload_hash VARCHAR(64) DEFAULT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->ensureTableCollation('telegram_subscribers');
        $this->db->ensureTableCollation('telegram_alert_state');
    }

    public function handleWebhookUpdate(array $update): array {
        $message = $update['message'] ?? $update['edited_message'] ?? $update['channel_post'] ?? null;
        if (!is_array($message)) {
            return ['success' => true, 'ignored' => true];
        }

        $chat = $message['chat'] ?? [];
        $chatId = (string)($chat['id'] ?? '');
        if ($chatId === '') {
            return ['success' => true, 'ignored' => true];
        }

        $this->registerSubscriber($chatId, [
            'first_name' => (string)($chat['first_name'] ?? ($message['from']['first_name'] ?? '')),
            'last_name' => (string)($chat['last_name'] ?? ($message['from']['last_name'] ?? '')),
            'username' => (string)($chat['username'] ?? ($message['from']['username'] ?? '')),
            'chat_type' => (string)($chat['type'] ?? 'private'),
            'language_code' => (string)($message['from']['language_code'] ?? ''),
        ]);

        $text = trim((string)($message['text'] ?? ''));
        if ($text === '') {
            return ['success' => true, 'registered' => true];
        }

        if ($this->isCommand($text, 'stop') || $this->isCommand($text, 'unsubscribe')) {
            $this->unregisterSubscriber($chatId);
            $this->sendMessageToChat($chatId, "Listo, ya no recibirás reportes de Visión Real.");
            return ['success' => true, 'unsubscribed' => true];
        }

        if ($this->isCommand($text, 'start') || $this->isCommand($text, 'subscribe')) {
            $this->sendMessageToChat($chatId, $this->buildWelcomeMessage());
            return ['success' => true, 'registered' => true];
        }

        return ['success' => true, 'registered' => true];
    }

    public function registerSubscriber(string $chatId, array $data = []): void {
        $chatType = trim((string)($data['chat_type'] ?? 'private'));
        $firstName = trim((string)($data['first_name'] ?? ''));
        $lastName = trim((string)($data['last_name'] ?? ''));
        $username = trim((string)($data['username'] ?? ''));
        $language = trim((string)($data['language_code'] ?? ''));

        $stmt = $this->db->prepare(
            "INSERT INTO telegram_subscribers (chat_id, first_name, last_name, username, chat_type, language_code, active, last_seen_at)
             VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                username = VALUES(username),
                chat_type = VALUES(chat_type),
                language_code = VALUES(language_code),
                active = 1,
                last_seen_at = NOW()"
        );
        if ($stmt) {
            $stmt->bind_param('ssssss', $chatId, $firstName, $lastName, $username, $chatType, $language);
            $stmt->execute();
        }
    }

    public function unregisterSubscriber(string $chatId): void {
        $stmt = $this->db->prepare("UPDATE telegram_subscribers SET active = 0, updated_at = NOW() WHERE chat_id = ?");
        if ($stmt) {
            $stmt->bind_param('s', $chatId);
            $stmt->execute();
        }
    }

    public function getSubscribers(bool $activeOnly = true): array {
        $sql = "SELECT * FROM telegram_subscribers";
        if ($activeOnly) {
            $sql .= " WHERE active = 1";
        }
        $sql .= " ORDER BY updated_at DESC, created_at DESC";

        $result = $this->db->query($sql);
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function broadcast(string $message, ?string $parseMode = null): array {
        $chatIds = $this->getRecipientChatIds();
        if (empty($chatIds)) {
            return ['success' => false, 'error' => 'No hay suscriptores ni chat de respaldo configurado.'];
        }

        $sent = 0;
        $failed = [];
        $errors = [];
        foreach ($chatIds as $chatId) {
            $result = $this->sendMessageToChat($chatId, $message, $parseMode);
            if (($result['success'] ?? false) === true) {
                $sent++;
            } else {
                $failed[] = $chatId;
                if (!empty($result['error'])) {
                    $errors[$chatId] = $result['error'];
                }
            }
        }

        return [
            'success' => $sent > 0,
            'error' => $sent > 0 ? null : ($errors ? reset($errors) : 'No se pudo enviar el reporte.'),
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors,
            'total' => count($chatIds),
        ];
    }

    public function enviarReporte(string $tipo = 'ambos', int $lowStockLimit = 5): array {
        if (!$this->configured()) {
            return ['success' => false, 'error' => 'Telegram no está configurado.'];
        }

        $tipo = in_array($tipo, ['stock', 'pedidos', 'ambos'], true) ? $tipo : 'ambos';
        $message = $this->buildMessage($tipo, $lowStockLimit, defined('TELEGRAM_REVIEW_MINUTES') ? (int) TELEGRAM_REVIEW_MINUTES : 15);
        return $this->broadcast($message);
    }

    public function sendCronDigestIfChanged(int $lowStockLimit = 5, int $reviewMinutes = 15): array {
        if (!$this->configured()) {
            return ['success' => false, 'error' => 'Telegram no está configurado.'];
        }

        $sections = [];
        $snapshots = [];

        $stockItems = $this->getLowStockItems($lowStockLimit);
        $stockHash = $this->hashForRows($stockItems);
        if ($this->shouldSendSection('low_stock', $stockHash)) {
            $sections[] = $this->buildStockSection($stockItems, $lowStockLimit);
            $snapshots['low_stock'] = $stockHash;
        }

        $pendingOrders = $this->getPendingOrders();
        $pendingHash = $this->hashForRows($pendingOrders);
        if ($this->shouldSendSection('pending_orders', $pendingHash)) {
            $sections[] = $this->buildPendingOrdersSection($pendingOrders);
            $snapshots['pending_orders'] = $pendingHash;
        }

        $reviewOrders = $this->getReviewOrders($reviewMinutes);
        $reviewHash = $this->hashForRows($reviewOrders);
        if ($this->shouldSendSection('review_orders', $reviewHash)) {
            $sections[] = $this->buildReviewOrdersSection($reviewOrders, $reviewMinutes);
            $snapshots['review_orders'] = $reviewHash;
        }

        if (empty($sections)) {
            return ['success' => true, 'sent' => false, 'message' => 'Sin cambios nuevos.'];
        }

        $message = $this->buildHeader() . "\n\n" . implode("\n\n", $sections) . "\n\n" . $this->buildFooter();
        $result = $this->broadcast($message);
        if (($result['success'] ?? false) === true) {
            foreach ($snapshots as $alertKey => $hash) {
                $this->setAlertState($alertKey, $hash);
            }
        }
        $result['message'] = 'Reporte enviado por cron.';
        return $result;
    }

    public function buildMessage(string $tipo, int $lowStockLimit = 5, int $reviewMinutes = 15): string {
        $parts = [];
        if ($tipo === 'ambos' || $tipo === 'stock') {
            $parts[] = $this->buildStockSection($this->getLowStockItems($lowStockLimit), $lowStockLimit);
        }
        if ($tipo === 'ambos' || $tipo === 'pedidos') {
            $parts[] = $this->buildPendingOrdersSection($this->getPendingOrders());
            $parts[] = $this->buildReviewOrdersSection($this->getReviewOrders($reviewMinutes), $reviewMinutes);
        }

        $parts = array_filter($parts, fn($part) => trim((string)$part) !== '');
        if (empty($parts)) {
            return $this->buildHeader() . "\n\nSin datos para reportar.";
        }

        return $this->buildHeader() . "\n\n" . implode("\n\n", $parts);
    }

    private function buildStockSection(array $items, int $lowStockLimit): string {
        $lines = [];
        $lines[] = '📦 INVENTARIO - STOCK BAJO';
        $lines[] = '━━━━━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = sprintf('⚠️ Productos críticos: %d', count($items));
        $lines[] = sprintf('🔴 Umbral de alerta: ≤ %d unidades', $lowStockLimit);

        if (empty($items)) {
            $lines[] = '✅ Todo está en nivel saludable';
            return implode("\n", $lines);
        }

        $lines[] = '';
        $critical = 0;
        $low = 0;
        
        foreach (array_slice($items, 0, 15) as $item) {
            $stock = (int)($item['stock'] ?? 0);
            $nombre = $item['nombre'] ?? 'Producto sin nombre';
            $categoria = $item['categoria_nombre'] ?? 'Sin categoría';
            
            if ($stock <= 2) {
                $icon = '🔴';
                $critical++;
            } else {
                $icon = '🟠';
                $low++;
            }
            
            $lines[] = sprintf(
                '%s #%s | %d uds | %s',
                $icon,
                substr($nombre, 0, 20),
                $stock,
                $categoria
            );
        }

        if (count($items) > 15) {
            $lines[] = '';
            $lines[] = sprintf('⚠️ Y %d producto(s) más con stock bajo...', count($items) - 15);
        }
        
        $lines[] = '';
        $lines[] = sprintf('💔 Críticos (≤2): %d | 🔶 Bajos: %d', $critical, $low);

        return implode("\n", $lines);
    }

    private function buildPendingOrdersSection(array $orders): string {
        $lines = [];
        $lines[] = '📋 PEDIDOS PENDIENTES';
        $lines[] = '━━━━━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = sprintf('🔔 Esperando procesar: %d', count($orders));

        if (empty($orders)) {
            $lines[] = '✅ Sin pedidos pendientes';
            return implode("\n", $lines);
        }

        $lines[] = '';
        $total_ingresos = 0;
        
        foreach (array_slice($orders, 0, 12) as $order) {
            $cliente = trim((string)($order['cliente_nombre'] ?? $order['envio_nombre'] ?? 'Cliente anónimo'));
            $cliente = strlen($cliente) > 16 ? substr($cliente, 0, 13) . '...' : $cliente;
            $total = (float)($order['total'] ?? 0);
            $total_ingresos += $total;
            $id = $order['numero_pedido'] ?? $order['id'];
            
            $lines[] = sprintf(
                '🆔 #%s | 👤 %s | 💰 $%s',
                $id,
                $cliente,
                number_format($total, 0, ',', '.')
            );
        }

        if (count($orders) > 12) {
            $lines[] = '';
            $lines[] = sprintf('⚠️ Y %d pedido(s) más sin procesar...', count($orders) - 12);
        }
        
        $lines[] = '';
        $lines[] = sprintf('💵 Ingresos esperados: $%s', number_format($total_ingresos, 0, ',', '.'));

        return implode("\n", $lines);
    }

    private function buildReviewOrdersSection(array $orders, int $reviewMinutes): string {
        $lines = [];
        $lines[] = '🔍 PEDIDOS EN REVISIÓN';
        $lines[] = '━━━━━━━━━━━━━━━━━━━━━━━━━';
        $lines[] = sprintf('⏱️ Estancados: > %d minutos sin actualizar', $reviewMinutes);
        $lines[] = sprintf('🚨 Total: %d pedido(s)', count($orders));

        if (empty($orders)) {
            $lines[] = '✅ Sin pedidos estancados';
            return implode("\n", $lines);
        }

        $lines[] = '';
        
        foreach (array_slice($orders, 0, 10) as $order) {
            $cliente = trim((string)($order['cliente_nombre'] ?? $order['envio_nombre'] ?? 'Cliente'));
            $cliente = strlen($cliente) > 14 ? substr($cliente, 0, 11) . '...' : $cliente;
            $total = (float)($order['total'] ?? 0);
            $estado = strtoupper($order['estado'] ?? 'PENDIENTE');
            $id = $order['numero_pedido'] ?? $order['id'];
            
            $estado_icon = match($estado) {
                'CONFIRMADO', 'CONFIRMED' => '✔️',
                'COMPLETADO', 'COMPLETED' => '✅',
                'CANCELADO', 'CANCELLED' => '❌',
                'ENVIADO', 'SHIPPED' => '📦',
                default => '⏳'
            };
            
            $lines[] = sprintf(
                '%s #%s | %s | %s | $%s',
                $estado_icon,
                $id,
                $cliente,
                $estado,
                number_format($total, 0, ',', '.')
            );
        }

        if (count($orders) > 10) {
            $lines[] = '';
            $lines[] = sprintf('⚠️ Y %d pedido(s) más en revisión...', count($orders) - 10);
        }
        
        $lines[] = '';
        $lines[] = '⚡ Acción requerida: revisar estado y continuar procesamiento';

        return implode("\n", $lines);
    }

    private function buildWelcomeMessage(): string {
        return "Bienvenido a Visión Real.\n"
            . "Te suscribiste a los reportes automáticos.\n"
            . "Recibirás avisos de stock bajo, pedidos pendientes y pedidos para revisión.";
    }

    private function buildHeader(): string {
        return '🏪 VISIÓN REAL - REPORTE AUTOMÁTICO' . "\n"
            . '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . "\n"
            . '📅 ' . date('d/m/Y') . ' ⏰ ' . date('H:i') . ' (Bogotá)';
    }
    
    private function buildFooter(): string {
        return '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . "\n"
            . '✉️ Sistema de alertas automático' . "\n"
            . '📱 Responde con /stop para desuscribirse';
    }

    private function splitMessage(string $message, int $limit = 3800): array {
        $message = trim($message);
        if ($message === '') {
            return [''];
        }

        if ($this->messageLength($message) <= $limit) {
            return [$message];
        }

        $lines = preg_split("/
|
|/", $message) ?: [$message];
        $chunks = [];
        $current = '';

        foreach ($lines as $line) {
            $candidate = $current === '' ? $line : $current . "
" . $line;
            if ($this->messageLength($candidate) <= $limit) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $chunks[] = $current;
                $current = '';
            }

            if ($this->messageLength($line) <= $limit) {
                $current = $line;
                continue;
            }

            $chunks = array_merge($chunks, $this->splitLongLine($line, $limit));
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return array_values(array_filter($chunks, fn($chunk) => trim((string) $chunk) !== ''));
    }

    private function splitLongLine(string $line, int $limit): array {
        $chunks = [];
        $length = $this->messageLength($line);
        $offset = 0;

        while ($offset < $length) {
            $chunks[] = $this->messageSubstring($line, $offset, $limit);
            $offset += $limit;
        }

        return $chunks;
    }

    private function messageLength(string $text): int {
        return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    }

    private function messageSubstring(string $text, int $start, int $length): string {
        return function_exists('mb_substr') ? mb_substr($text, $start, $length, 'UTF-8') : substr($text, $start, $length);
    }

    private function getRecipientChatIds(): array {
        if ($this->groupChatId !== '') {
            return [$this->groupChatId];
        }

        $chatIds = [];

        foreach ($this->getSubscribers(true) as $subscriber) {
            $chatId = trim((string)($subscriber['chat_id'] ?? ''));
            if ($chatId !== '') {
                $chatIds[] = $chatId;
            }
        }

        if ($this->fallbackChatId !== '') {
            $chatIds[] = $this->fallbackChatId;
        }

        $chatIds = array_values(array_unique(array_filter($chatIds, fn($v) => $v !== '')));
        return $chatIds;
    }

    private function sendMessageToChat(string $chatId, string $text, ?string $parseMode = null): array {
        if ($this->token === '' || $chatId === '') {
            return ['success' => false, 'error' => 'Telegram no está configurado.'];
        }

        $url = sprintf('%s/bot%s/sendMessage', $this->apiBaseUrl, $this->token);
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => true,
        ];
        if ($parseMode !== null && $parseMode !== '') {
            $payload['parse_mode'] = $parseMode;
        }

        $response = $this->requestJson($url, $payload);
        if (($response['ok'] ?? false) === true) {
            return ['success' => true, 'result' => $response['result'] ?? []];
        }

        return [
            'success' => false,
            'error' => $this->normalizeTelegramError($response),
        ];
    }

    private function normalizeTelegramError(array $response): string {
        $description = trim((string)($response['description'] ?? ''));
        if ($description === '') {
            return 'No se pudo enviar el mensaje.';
        }

        if (strcasecmp($description, 'Unauthorized') === 0) {
            return 'Telegram rechazó la autenticación del bot. Revisa TELEGRAM_BOT_TOKEN o configura TELEGRAM_API_BASE_URL con tu proxy de Cloudflare Worker.';
        }

        return $description;
    }

    private function requestJson(string $url, array $payload): array {
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        if (function_exists('curl_init')) {
            $result = $this->curlPostJson($url, $payload);
            if (($result['ok'] ?? false) === true || empty($result['description'])) {
                return $result;
            }

            $error = strtolower((string) $result['description']);
            if ($host === 'api.telegram.org' && strpos($error, 'could not resolve host') !== false) {
                $resolved = $this->curlPostJsonWithResolvedHost($url, $payload);
                if (($resolved['ok'] ?? false) === true || empty($resolved['description'])) {
                    return $resolved;
                }
                $streamResolved = $this->streamPostJsonWithResolvedHost($url, $payload);
                if (($streamResolved['ok'] ?? false) === true || empty($streamResolved['description'])) {
                    return $streamResolved;
                }
                return $streamResolved;
            }

            if ($host === 'api.telegram.org') {
                $streamResolved = $this->streamPostJsonWithResolvedHost($url, $payload);
                if (($streamResolved['ok'] ?? false) === true || empty($streamResolved['description'])) {
                    return $streamResolved;
                }
                return $streamResolved;
            }

            return $result;
        } else {
            $streamResolved = $this->streamPostJsonWithResolvedHost($url, $payload);
            if (($streamResolved['ok'] ?? false) === true || empty($streamResolved['description'])) {
                return $streamResolved;
            }
            return $streamResolved;
        }
    }

    private function curlPostJson(string $url, array $payload, array $extraOptions = []): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, array_replace([
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 5,
        ], $extraOptions));
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['ok' => false, 'description' => $error ?: 'Error de conexión con Telegram'];
        }
        curl_close($ch);

        $decoded = json_decode((string)$raw, true);
        return is_array($decoded) ? $decoded : ['ok' => false, 'description' => 'Respuesta inválida de Telegram'];
    }

    private function curlPostJsonWithResolvedHost(string $url, array $payload): array {
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        if ($host === '') {
            return ['ok' => false, 'description' => 'Host inválido'];
        }

        $candidates = [
            '149.154.167.220',
            '149.154.167.51',
        ];

        foreach ($candidates as $ip) {
            $resolved = $this->curlPostJson($url, $payload, [
                CURLOPT_RESOLVE => ["{$host}:443:{$ip}"],
            ]);
            if (($resolved['ok'] ?? false) === true) {
                return $resolved;
            }
        }

        return ['ok' => false, 'description' => 'Could not resolve host: api.telegram.org'];
    }

    private function streamPostJsonWithResolvedHost(string $url, array $payload): array {
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        if ($host === '') {
            return ['ok' => false, 'description' => 'Host inválido'];
        }

        $query = parse_url($url, PHP_URL_QUERY);
        if (!empty($query)) {
            $path .= '?' . $query;
        }

        $candidates = [
            '149.154.167.220',
            '149.154.167.51',
        ];

        $body = http_build_query($payload);
        foreach ($candidates as $ip) {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", [
                        'Content-Type: application/x-www-form-urlencoded',
                        'Host: ' . $host,
                    ]) . "\r\n",
                    'content' => $body,
                    'timeout' => 5,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'peer_name' => $host,
                    'SNI_enabled' => true,
                ],
            ]);

            $raw = @file_get_contents('https://' . $ip . $path, false, $context);
            if ($raw !== false) {
                $decoded = json_decode((string) $raw, true);
                return is_array($decoded) ? $decoded : ['ok' => false, 'description' => 'Respuesta inválida de Telegram'];
            }
        }

        return ['ok' => false, 'description' => 'Could not resolve host: api.telegram.org'];
    }

    private function isCommand(string $text, string $command): bool {
        return (bool) preg_match('~^/' . preg_quote($command, '~') . '(?:@[\w_]+)?(?:\s|$)~i', trim($text));
    }

    private function getLowStockItems(int $limit): array {
        $pm = new Producto();
        return $pm->getLowStock($limit);
    }

    private function getPendingOrders(int $limit = 20): array {
        $limit = max(1, min(100, $limit));
        $sql = "SELECT p.*, COALESCE(co.nombre, p.envio_nombre) AS cliente_nombre
                FROM pedidos p
                LEFT JOIN clientes_online co ON p.cliente_online_id = co.id
                WHERE p.estado = 'pendiente'
                ORDER BY p.creado_at DESC
                LIMIT {$limit}";
        $result = $this->db->query($sql);
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    private function getReviewOrders(int $reviewMinutes, int $limit = 20): array {
        $reviewMinutes = max(1, $reviewMinutes);
        $limit = max(1, min(100, $limit));

        $sql = "SELECT p.*, COALESCE(co.nombre, p.envio_nombre) AS cliente_nombre
                FROM pedidos p
                LEFT JOIN clientes_online co ON p.cliente_online_id = co.id
                WHERE p.estado = 'pendiente'
                  AND TIMESTAMPDIFF(MINUTE, p.actualizado_at, NOW()) >= ?
                ORDER BY p.actualizado_at ASC
                LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $reviewMinutes);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    private function hashForRows(array $rows): string {
        if (empty($rows)) {
            return md5('empty');
        }

        $normalized = [];
        foreach ($rows as $row) {
            $normalized[] = [
                'id' => $row['id'] ?? null,
                'estado' => $row['estado'] ?? null,
                'stock' => $row['stock'] ?? null,
                'total' => $row['total'] ?? null,
                'creado_at' => $row['creado_at'] ?? null,
                'actualizado_at' => $row['actualizado_at'] ?? null,
                'numero_pedido' => $row['numero_pedido'] ?? null,
            ];
        }

        return md5(json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function shouldSendSection(string $alertKey, string $hash): bool {
        $last = $this->getAlertState($alertKey);
        return $last !== $hash && $hash !== md5('empty');
    }

    private function getAlertState(string $alertKey): ?string {
        $stmt = $this->db->prepare("SELECT alert_hash FROM telegram_alert_state WHERE alert_key = ?");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $alertKey);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['alert_hash'] ?? null;
    }

    private function setAlertState(string $alertKey, string $hash): void {
        $stmt = $this->db->prepare(
            "INSERT INTO telegram_alert_state (alert_key, alert_hash, sent_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE alert_hash = VALUES(alert_hash), sent_at = NOW()"
        );
        if ($stmt) {
            $stmt->bind_param('ss', $alertKey, $hash);
            $stmt->execute();
        }
    }
}
