<?php
if (!defined('APP_ENV')) {
    $host = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_X_ORIGINAL_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));

    $isLocalHost = (
        php_sapi_name() === 'cli'
        || strpos($host, 'localhost') !== false
        || strpos($host, '127.0.0.1') !== false
        || strpos($host, '::1') !== false
        || strpos($host, '.devtunnels.ms') !== false
        || strpos($host, 'devtunnels.ms') !== false
    );

    define('APP_ENV', $isLocalHost ? 'local' : 'production');
}

if (!defined('BASE_URL')) {
    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['HTTP_X_FORWARDED_SSL'] ?? $_SERVER['REQUEST_SCHEME'] ?? ''));
    $forwardedHost = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_X_ORIGINAL_HOST'] ?? ''));

    if ($forwardedHost !== '' && strpos($forwardedHost, ',') !== false) {
        $forwardedHost = trim(explode(',', $forwardedHost)[0]);
    }

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $forwardedProto === 'https'
        || $forwardedProto === 'on'
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
    );

    $scheme = $isHttps ? 'https' : 'http';
    $host = $forwardedHost !== '' ? $forwardedHost : trim((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost'));
    $envBase = trim((string) getenv('APP_BASE_URL'));

    if ($envBase !== '') {
        define('BASE_URL', rtrim($envBase, '/'));
    } else {
        $path = '';

        if (php_sapi_name() === 'cli') {
            $projectDir = basename(dirname(__DIR__));
            if ($projectDir !== '' && $projectDir !== '.' && $projectDir !== DIRECTORY_SEPARATOR) {
                $path = '/' . trim(str_replace('\\', '/', $projectDir), '/');
            }
        } else {
            $scriptName = trim(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '')), '/');
            if ($scriptName !== '') {
                $segments = explode('/', $scriptName);
                $firstSegment = $segments[0] ?? '';
                if ($firstSegment !== '' && strpos($firstSegment, '.') === false) {
                    $path = '/' . $firstSegment;
                }
            }
        }

        define('BASE_URL', rtrim($scheme . '://' . $host . $path, '/'));
    }
}

define('APP_NAME', 'Visión Real');

define('WHATSAPP_API_URL', '');
define('WHATSAPP_API_TOKEN', '');
define('WHATSAPP_PHONE_ID', '');
define('WHATSAPP_DEFAULT_COUNTRY_CODE', '57');
define('WHATSAPP_SEND_NOTIFICATIONS', false);

// Contacto de la tienda (wa.me) para que el cliente pueda abrir chat.
// Formato: codigoPais + numero sin signos, ej: 573101234567
// Telegram
define('TELEGRAM_BOT_TOKEN', '8931961594:AAHAjqB19Yjxz94fcJZqCgLy14ceA1rTy1U');
define('TELEGRAM_CHAT_ID', '8712771307');
define('TELEGRAM_GROUP_CHAT_ID', '-1004474319158');
define('TELEGRAM_API_BASE_URL', 'https://telegram-proxy-visionreal.alejandro413gh.workers.dev');
define('TELEGRAM_REPORT_SECRET', 'VR-REPORT-2026-9f3c8a7b5d');
define('TELEGRAM_LOW_STOCK_THRESHOLD', 3);
define('TELEGRAM_REVIEW_MINUTES', 10);
define('TELEGRAM_REPORT_ENABLED', true);

// IA / Ollama con fallback inteligente (Groq -> Ollama local)
define('AI_REPORT_ENABLED', true);
define('AI_REPORT_PROVIDER', 'remote'); // 'remote' = Groq primero, con fallback automático a Ollama

// API remota (Groq) - gratis
define('AI_REMOTE_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions');
define('AI_REMOTE_TOKEN', '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');
// Modelo remoto: mixtral-8x7b-32768 fue descontinuado por Groq, se usa gpt-oss-20b (rápido y gratuito)
define('AI_REMOTE_MODEL', 'openai/gpt-oss-20b');

// Ollama local (solo se usa si Groq falla)
define('AI_OLLAMA_BASE_URL', 'http://localhost:11434');
define('AI_OLLAMA_MODEL', 'gemma4:latest');
define('AI_OLLAMA_TIMEOUT', 120);
define('AI_OLLAMA_TEMPERATURE', 0.2);

// Fallback y caché
define('AI_ENABLE_FALLBACK', true);
define('AI_ENABLE_CACHE', true);
define('AI_CACHE_TTL', 86400);

define('AI_GATEWAY_TOKEN', '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');

date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/database.php';

if (!function_exists('store_settings_defaults')) {
    function store_settings_defaults(): array {
        return [
            'store_name' => 'Visión Real',
            'whatsapp_number' => '573125420576',
            'support_email' => 'rufay0813@gmail.com',
            'physical_address' => '',
            'latitude' => '',
            'longitude' => '',
            'google_maps_url' => '',
        ];
    }
}

if (!function_exists('store_settings_table_ready')) {
    function store_settings_table_ready(): void {
        static $ready = false;
        if ($ready) {
            return;
        }
        try {
            $db = Database::getInstance()->getConnection();
            $db->query(
                "CREATE TABLE IF NOT EXISTS store_settings (
                    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
                    setting_value TEXT DEFAULT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $ready = true;
        } catch (Throwable $e) {
            error_log('store_settings_table_ready: ' . $e->getMessage());
        }
    }
}

if (!function_exists('store_settings_all')) {
    function store_settings_all(): array {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = store_settings_defaults();
        try {
            store_settings_table_ready();
            $db = Database::getInstance()->getConnection();
            $result = $db->query("SELECT setting_key, setting_value FROM store_settings");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $key = (string) ($row['setting_key'] ?? '');
                    if ($key !== '') {
                        $cache[$key] = (string) ($row['setting_value'] ?? '');
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('store_settings_all: ' . $e->getMessage());
        }

        return $cache;
    }
}

if (!function_exists('store_setting')) {
    function store_setting(string $key, $default = '') {
        $settings = store_settings_all();
        return $settings[$key] ?? $default;
    }
}

if (!function_exists('store_build_maps_url')) {
    function store_build_maps_url(?string $latitude = null, ?string $longitude = null, string $fallbackUrl = ''): string {
        $latitude = trim((string) ($latitude ?? ''));
        $longitude = trim((string) ($longitude ?? ''));
        if ($latitude === '' || $longitude === '') {
            return trim($fallbackUrl);
        }
        return 'https://www.google.com/maps?q=' . rawurlencode($latitude . ',' . $longitude);
    }
}

if (!function_exists('store_build_maps_embed_url')) {
    function store_build_maps_embed_url(?string $latitude = null, ?string $longitude = null): string {
        $latitude = trim((string) ($latitude ?? ''));
        $longitude = trim((string) ($longitude ?? ''));
        if ($latitude === '' || $longitude === '') {
            return '';
        }
        return 'https://maps.google.com/maps?output=embed&q=' . rawurlencode($latitude . ',' . $longitude);
    }
}

if (!function_exists('store_build_maps_embed_from_url')) {
    function store_build_maps_embed_from_url(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (stripos($url, 'output=embed') !== false) {
            return $url;
        }
        if (preg_match('/[?&](?:q|query)=([^&]+)/i', $url, $m)) {
            return 'https://maps.google.com/maps?output=embed&q=' . $m[1];
        }
        if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $url, $m)) {
            return 'https://maps.google.com/maps?output=embed&q=' . rawurlencode($m[1] . ',' . $m[2]);
        }
        if (preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $url, $m)) {
            return 'https://maps.google.com/maps?output=embed&q=' . rawurlencode($m[1] . ',' . $m[2]);
        }
        return '';
    }
}

if (!defined('STORE_NAME')) {
    define('STORE_NAME', store_setting('store_name', 'Visión Real'));
}
if (!defined('STORE_WHATSAPP_NUMBER')) {
    define('STORE_WHATSAPP_NUMBER', store_setting('whatsapp_number', '573125420576'));
}
if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', store_setting('support_email', 'rufay0813@gmail.com'));
}
if (!defined('STORE_PHYSICAL_ADDRESS')) {
    define('STORE_PHYSICAL_ADDRESS', store_setting('physical_address', ''));
}
if (!defined('STORE_LATITUDE')) {
    define('STORE_LATITUDE', store_setting('latitude', ''));
}
if (!defined('STORE_LONGITUDE')) {
    define('STORE_LONGITUDE', store_setting('longitude', ''));
}
if (!defined('STORE_MAPS_URL')) {
    define('STORE_MAPS_URL', store_build_maps_url(STORE_LATITUDE, STORE_LONGITUDE, store_setting('google_maps_url', '')));
}
if (!defined('STORE_MAPS_EMBED_URL')) {
    define('STORE_MAPS_EMBED_URL', !empty(STORE_LATITUDE) && !empty(STORE_LONGITUDE)
        ? store_build_maps_embed_url(STORE_LATITUDE, STORE_LONGITUDE)
        : store_build_maps_embed_from_url(store_setting('google_maps_url', '')));
}

if (!defined('APP_SKIP_SESSION_BOOTSTRAP') || APP_SKIP_SESSION_BOOTSTRAP !== true) {
    require_once __DIR__ . '/session.php';
}
