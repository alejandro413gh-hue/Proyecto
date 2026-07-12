<?php
/**
 * OllamaClient v3.0 - Fallback Inteligente
 * 
 * Sistema robusto con:
 * - Fallback automático Groq → Ollama local
 * - Caché para evitar gastar cuota
 * - Logging detallado
 * - Configuración flexible
 * 
 * Configuración en config/config.php:
 * 
 * // Provider principal (puede ser 'remote' o 'ollama')
 * define('AI_REPORT_PROVIDER', 'remote');
 * 
 * // API Remota
 * define('AI_REMOTE_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions');
 * define('AI_REMOTE_TOKEN', 'gsk_xxx');
 * 
 * // Ollama Local (fallback)
 * define('AI_OLLAMA_BASE_URL', 'http://localhost:11434');
 * define('AI_OLLAMA_MODEL', 'gemma2:latest');
 * define('AI_ENABLE_FALLBACK', true); // Activar fallback automático
 * define('AI_ENABLE_CACHE', true);   // Activar caché
 * define('AI_CACHE_TTL', 86400);     // TTL caché (24 horas)
 */

require_once __DIR__ . '/../config/config.php';

class OllamaClient {
    
    private string $provider;
    private ?string $remoteEndpoint = null;
    private ?string $remoteToken = null;
    private string $primaryModel;
    private string $remoteModel;
    private string $localModel;
    private float $temperature;
    private int $timeout;
    private bool $enableFallback;
    private bool $enableCache;
    private int $cacheTTL;
    private string $cacheDir;
    private array $requestLog = [];
    private array $stats = [
        'remote_success' => 0,
        'remote_failed' => 0,
        'fallback_used' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
    ];
    
    public function __construct() {
        $this->provider = strtolower((string) (defined('AI_REPORT_PROVIDER') ? AI_REPORT_PROVIDER : 'ollama'));
        $this->remoteEndpoint = trim((string) (defined('AI_REMOTE_ENDPOINT') ? AI_REMOTE_ENDPOINT : ''));
        $this->remoteToken = trim((string) (defined('AI_REMOTE_TOKEN') ? AI_REMOTE_TOKEN : ''));
        // Modelo remoto (Groq) y modelo local (Ollama) son distintos catálogos de modelos,
        // así que se separan para no pedirle a Groq un modelo de Ollama o viceversa.
        $this->remoteModel = (string) (defined('AI_REMOTE_MODEL') ? AI_REMOTE_MODEL : 'openai/gpt-oss-20b');
        $this->localModel = (string) (defined('AI_OLLAMA_MODEL') ? AI_OLLAMA_MODEL : 'gemma4:latest');
        $this->primaryModel = $this->localModel;
        $this->temperature = (float) (defined('AI_OLLAMA_TEMPERATURE') ? AI_OLLAMA_TEMPERATURE : 0.2);
        $this->timeout = (int) (defined('AI_OLLAMA_TIMEOUT') ? AI_OLLAMA_TIMEOUT : 120);
        
        $this->enableFallback = (bool) (defined('AI_ENABLE_FALLBACK') ? AI_ENABLE_FALLBACK : true);
        $this->enableCache = (bool) (defined('AI_ENABLE_CACHE') ? AI_ENABLE_CACHE : true);
        $this->cacheTTL = (int) (defined('AI_CACHE_TTL') ? AI_CACHE_TTL : 86400);
        $this->cacheDir = sys_get_temp_dir() . '/ai_cache';
        
        if ($this->enableCache && !is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Método principal de generación
     * Intenta: Caché → API Remota → Ollama Local
     */
    public function generate(string $prompt, array $context = []): array {
        if (empty($prompt)) {
            return ['success' => false, 'error' => 'El prompt no puede estar vacío.'];
        }

        // Validar requisitos
        $validation = $this->validateRequirements();
        if (!$validation['success']) {
            return $validation;
        }

        $cacheKey = $this->getCacheKey($prompt, $context);

        // 1. Intentar caché
        if ($this->enableCache) {
            $cached = $this->getFromCache($cacheKey);
            if ($cached) {
                $this->stats['cache_hits']++;
                $this->logRequest('cache_hit', $cacheKey, true);
                return $cached;
            }
            $this->stats['cache_misses']++;
        }

        // 2. Usar provider principal
        $result = null;
        
        if ($this->provider === 'remote' && !empty($this->remoteEndpoint)) {
            $result = $this->callRemoteAPI($prompt, $context);
            
            if ($result['success']) {
                $this->stats['remote_success']++;
            } else {
                $this->stats['remote_failed']++;
            }
        } else {
            $result = $this->callLocalOllama($prompt, $context);
        }

        // 3. Fallback a Ollama local si falla API remota
        if (!$result['success'] && $this->enableFallback && $this->provider === 'remote') {
            $remoteError = $result['error'] ?? 'Unknown error';
            $this->logRequest('fallback_activated', $remoteError, false);
            $result = $this->callLocalOllama($prompt, $context);
            
            if ($result['success']) {
                $this->stats['fallback_used']++;
                $result['fallback_used'] = true;
            } else {
                // No ocultar el error original de Groq: mostrar ambos
                $localError = $result['error'] ?? 'Unknown error';
                $result['error'] = "Groq falló: $remoteError | Ollama local también falló: $localError";
            }
        }

        // 4. Guardar en caché si fue exitoso
        if ($result['success'] && $this->enableCache) {
            $this->saveToCache($cacheKey, $result);
        }

        return $result;
    }

    /**
     * Llamar API remota (Groq)
     */
    private function callRemoteAPI(string $prompt, array $context = []): array {
        if (empty($this->remoteEndpoint)) {
            return ['success' => false, 'error' => 'No hay endpoint remoto configurado.'];
        }

        if (empty($this->remoteToken)) {
            return ['success' => false, 'error' => 'No hay token de autenticación configurado.'];
        }

        $model = $context['model'] ?? $this->remoteModel;
        $system = (string) ($context['system'] ?? '');
        $apiType = $this->detectAPIType($this->remoteEndpoint);

        // Construir payload según tipo de API
        $payload = match ($apiType) {
            'groq', 'openai', 'openrouter' => $this->buildOpenAIPayload($prompt, $model, $system),
            'google' => $this->buildGooglePayload($prompt, $model, $system),
            'huggingface' => $this->buildHuggingFacePayload($prompt, $model),
            default => $this->buildOpenAIPayload($prompt, $model, $system),
        };

        return $this->makeRequest(
            $this->remoteEndpoint,
            $payload,
            'remote_api',
            $apiType,
            true
        );
    }

    /**
     * Llamar Ollama local (con Gemma 4 como fallback)
     */
    private function callLocalOllama(string $prompt, array $context = []): array {
        $baseUrl = rtrim((string) (defined('AI_OLLAMA_BASE_URL') ? AI_OLLAMA_BASE_URL : 'http://localhost:11434'), '/');
        $model = $context['model'] ?? $this->localModel;

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'system' => (string) ($context['system'] ?? ''),
            'stream' => false,
            'think' => false,
            'options' => [
                'temperature' => $this->temperature,
                'num_predict' => 500,
            ],
        ];

        $result = $this->makeRequest(
            $baseUrl . '/api/generate',
            $payload,
            'ollama_local',
            'ollama'
        );

        // Si falla el modelo local configurado, intentar una vez más con gemma2:latest
        // (solo si no es ya el modelo que se acaba de intentar)
        if (!$result['success'] && $model !== 'gemma2:latest') {
            $this->logRequest('local_fallback_attempted', "Intentando con gemma2:latest", false);
            
            $payload['model'] = 'gemma2:latest';
            $result = $this->makeRequest(
                $baseUrl . '/api/generate',
                $payload,
                'ollama_local_gemma',
                'ollama'
            );
        }

        return $result;
    }

    /**
     * Construir payload estilo OpenAI (Groq)
     */
    private function buildOpenAIPayload(string $prompt, string $model, string $system): array {
        return [
            'model' => $model,
            // array_values reindexa las claves: sin esto, cuando no hay system prompt,
            // array_filter deja huecos en el array y json_encode lo serializa como
            // objeto {"1":...} en vez de lista [...], lo que Groq rechaza.
            'messages' => array_values(array_filter([
                $system ? ['role' => 'system', 'content' => $system] : null,
                ['role' => 'user', 'content' => $prompt],
            ])),
            'temperature' => $this->temperature,
            'max_tokens' => 1000,
        ];
    }

    /**
     * Construir payload para Google Gemini
     */
    private function buildGooglePayload(string $prompt, string $model, string $system): array {
        $content = $system ? "$system\n\n$prompt" : $prompt;
        
        return [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $content]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $this->temperature,
                'maxOutputTokens' => 1000,
            ]
        ];
    }

    /**
     * Construir payload para Hugging Face
     */
    private function buildHuggingFacePayload(string $prompt, string $model): array {
        return [
            'inputs' => $prompt,
            'parameters' => [
                'max_length' => 500,
            ]
        ];
    }

    /**
     * Detectar tipo de API por URL
     */
    private function detectAPIType(string $endpoint): string {
        $endpoint = strtolower($endpoint);
        
        if (strpos($endpoint, 'groq.com') !== false) {
            return 'groq';
        }
        if (strpos($endpoint, 'generativelanguage.googleapis.com') !== false) {
            return 'google';
        }
        if (strpos($endpoint, 'openai.com') !== false) {
            return 'openai';
        }
        if (strpos($endpoint, 'openrouter.ai') !== false) {
            return 'openrouter';
        }
        if (strpos($endpoint, 'huggingface.co') !== false) {
            return 'huggingface';
        }
        if (strpos($endpoint, '/api/generate') !== false) {
            return 'ollama';
        }
        
        return 'openai'; // Default
    }

    /**
     * Realizar petición HTTP
     */
    private function makeRequest(
        string $url,
        array $payload,
        string $source,
        string $apiType = 'ollama',
        bool $isRemote = false
    ): array {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'error' => 'cURL no disponible.'];
        }

        $headers = $this->buildHeaders($isRemote, $apiType);
        $startTime = microtime(true);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $duration = microtime(true) - $startTime;
        
        curl_close($ch);

        // Loguear petición
        $this->logRequest($source, [
            'duration' => round($duration * 1000) . 'ms',
            'httpCode' => $httpCode,
            'model' => $payload['model'] ?? 'unknown',
        ], $errno === 0 && $httpCode === 200);

        if ($raw === false || $errno !== 0) {
            return [
                'success' => false,
                'error' => $error ?: 'No se pudo conectar con el servidor.',
                'source' => $source,
                'http_code' => $httpCode,
                'duration' => $duration,
            ];
        }

        return $this->parseResponse($raw, $apiType, $source, $httpCode, $duration);
    }

    /**
     * Construir headers según tipo de API
     */
    private function buildHeaders(bool $isRemote, string $apiType): array {
        $headers = ['Content-Type: application/json'];

        if (!$isRemote || empty($this->remoteToken)) {
            return $headers;
        }

        if (in_array($apiType, ['groq', 'openai', 'openrouter'])) {
            $headers[] = 'Authorization: Bearer ' . $this->remoteToken;
        } elseif ($apiType === 'huggingface') {
            $headers[] = 'Authorization: Bearer ' . $this->remoteToken;
        }

        return $headers;
    }

    /**
     * Parsear respuesta según tipo de API
     */
    private function parseResponse(
        string $raw,
        string $apiType,
        string $source,
        int $httpCode,
        float $duration
    ): array {
        $decoded = @json_decode($raw, true);

        if (!is_array($decoded)) {
            return [
                'success' => false,
                'error' => 'Respuesta inválida del servidor.',
                'source' => $source,
                'http_code' => $httpCode,
                'duration' => $duration,
            ];
        }

        // Extraer texto según tipo de API
        $text = match ($apiType) {
            'groq', 'openai', 'openrouter' => $decoded['choices'][0]['message']['content'] ?? '',
            'google' => $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '',
            'huggingface' => $decoded[0]['generated_text'] ?? '',
            'ollama' => $decoded['response'] ?? '',
            default => $decoded['response'] ?? $decoded['text'] ?? '',
        };

        if (!is_string($text)) {
            $text = '';
        }

        $text = trim($text);

        // Validar error
        if ($httpCode >= 400) {
            return [
                'success' => false,
                'error' => $decoded['error']['message'] ?? "Error HTTP $httpCode",
                'source' => $source,
                'http_code' => $httpCode,
                'duration' => $duration,
            ];
        }

        if (empty($text)) {
            return [
                'success' => false,
                'error' => 'El servidor no devolvió texto.',
                'source' => $source,
                'http_code' => $httpCode,
                'duration' => $duration,
            ];
        }

        return [
            'success' => true,
            'text' => $text,
            'source' => $source,
            'http_code' => $httpCode,
            'duration' => $duration,
            'model' => $decoded['model'] ?? 'unknown',
            'provider' => $this->provider,
        ];
    }

    /**
     * ============ CACHÉ ============
     */

    private function getCacheKey(string $prompt, array $context): string {
        $model = $context['model'] ?? $this->primaryModel;
        $key = md5($prompt . '|' . $model);
        return $key;
    }

    private function getFromCache(string $key): ?array {
        if (!$this->enableCache) {
            return null;
        }

        $file = $this->cacheDir . '/' . $key . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }

        if (time() - filemtime($file) > $this->cacheTTL) {
            @unlink($file);
            return null;
        }

        $data = @file_get_contents($file);
        if ($data === false) {
            return null;
        }

        $cached = @json_decode($data, true);
        if (is_array($cached)) {
            $cached['from_cache'] = true;
            return $cached;
        }

        return null;
    }

    private function saveToCache(string $key, array $result): void {
        if (!$this->enableCache) {
            return;
        }

        $file = $this->cacheDir . '/' . $key . '.cache';
        $toCache = array_filter($result, fn($k) => !in_array($k, ['from_cache']), ARRAY_FILTER_USE_KEY);
        
        @file_put_contents($file, json_encode($toCache, JSON_UNESCAPED_UNICODE));
    }

    public function clearCache(): bool {
        if (!is_dir($this->cacheDir)) {
            return false;
        }

        $files = @glob($this->cacheDir . '/*.cache');
        $count = 0;

        foreach ($files as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }

        return $count > 0;
    }

    /**
     * ============ LOGGING ============
     */

    private function logRequest(string $action, $data, bool $success): void {
        $this->requestLog[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'data' => $data,
            'success' => $success,
        ];

        // Opcional: guardar en log file
        // $logFile = __DIR__ . '/../logs/ai_requests.log';
        // file_put_contents($logFile, json_encode(...) . PHP_EOL, FILE_APPEND);
    }

    public function getRequestLog(): array {
        return $this->requestLog;
    }

    public function getStats(): array {
        return array_merge($this->stats, [
            'provider' => $this->provider,
            'fallback_enabled' => $this->enableFallback,
            'cache_enabled' => $this->enableCache,
        ]);
    }

    /**
     * ============ VALIDACIÓN ============
     */

    private function validateRequirements(): array {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'error' => 'cURL no disponible.'];
        }

        if (!function_exists('json_encode')) {
            return ['success' => false, 'error' => 'JSON no disponible.'];
        }

        return ['success' => true];
    }

    /**
     * ============ DEBUG ============
     */

    public function testConnection(): array {
        $test = $this->generate('Responde solo con "OK"');
        
        return [
            'provider' => $this->provider,
            'remote_enabled' => !empty($this->remoteEndpoint),
            'fallback_enabled' => $this->enableFallback,
            'cache_enabled' => $this->enableCache,
            'model' => $this->primaryModel,
            'test_result' => $test,
            'is_working' => $test['success'] ?? false,
        ];
    }

    public function getInfo(): array {
        return [
            'provider' => $this->provider,
            'remote_endpoint' => $this->remoteEndpoint ? substr($this->remoteEndpoint, 0, 40) . '...' : 'none',
            'model' => $this->primaryModel,
            'fallback_enabled' => $this->enableFallback,
            'cache_enabled' => $this->enableCache,
            'cache_ttl' => $this->cacheTTL . ' segundos',
            'stats' => $this->getStats(),
            'request_log' => $this->getRequestLog(),
        ];
    }
}
?>
