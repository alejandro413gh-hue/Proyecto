<?php
/**
 * SCRIPT DE TESTING PARA MIGRACIÓN DE OLLAMA A API GRATUITA
 * 
 * Uso:
 *   php test_migration.php
 * 
 * Verifica:
 * - Configuración correcta
 * - Conectividad con la API
 * - Funcionamiento de la generación de IA
 * - Comparación entre Ollama local y API remota
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('APP_SKIP_SESSION_BOOTSTRAP', true);
require_once __DIR__ . '/config/config.php';

class MigrationTester {
    private array $results = [];
    private array $errors = [];
    private array $warnings = [];

    public function run(): void {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════╗\n";
        echo "║  TEST DE MIGRACIÓN: OLLAMA → API GRATUITA             ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n\n";

        $this->testEnvironment();
        $this->testConfig();
        $this->testConnectivity();
        $this->testAPICall();
        $this->compareProviders();
        
        $this->displayResults();
    }

    private function testEnvironment(): void {
        echo "📋 Verificando entorno...\n";

        // PHP Version
        $phpVersion = phpversion();
        $this->addResult("PHP Version", $phpVersion, version_compare($phpVersion, '7.4', '>='));

        // Extensions
        $curl = extension_loaded('curl');
        $this->addResult("cURL Extension", $curl ? "✓ Instalada" : "✗ No instalada", $curl);

        $json = extension_loaded('json');
        $this->addResult("JSON Extension", $json ? "✓ Instalada" : "✗ No instalada", $json);

        $fileSystem = is_writable(__DIR__);
        $this->addResult("Permisos de escritura", $fileSystem ? "✓ Disponible" : "✗ No disponible", $fileSystem);

        echo "\n";
    }

    private function testConfig(): void {
        echo "⚙️  Verificando configuración...\n";

        // AI Report Enabled
        $aiEnabled = defined('AI_REPORT_ENABLED') && AI_REPORT_ENABLED;
        $this->addResult("AI_REPORT_ENABLED", $aiEnabled ? "✓ true" : "✗ false", $aiEnabled);

        // Provider
        $provider = strtolower(defined('AI_REPORT_PROVIDER') ? AI_REPORT_PROVIDER : 'ollama');
        $this->addResult("AI_REPORT_PROVIDER", "→ $provider", true);

        // Configuración específica
        if ($provider === 'remote') {
            $endpoint = defined('AI_REMOTE_ENDPOINT') ? AI_REMOTE_ENDPOINT : '';
            $hasEndpoint = !empty($endpoint);
            $this->addResult(
                "AI_REMOTE_ENDPOINT",
                $hasEndpoint ? "✓ Configurado: " . substr($endpoint, 0, 40) . "..." : "✗ No configurado",
                $hasEndpoint
            );

            $token = defined('AI_REMOTE_TOKEN') ? AI_REMOTE_TOKEN : '';
            $hasToken = !empty($token) && $token !== 'gsk_XXXXXXXXXXXXXX';
            $this->addResult(
                "AI_REMOTE_TOKEN",
                $hasToken ? "✓ Presente: " . substr($token, 0, 10) . "..." : "✗ No configurado o placeholder",
                $hasToken,
                !$hasToken ? "CRÍTICO: Debes poner tu API key real" : null
            );
        } else {
            $baseUrl = defined('AI_OLLAMA_BASE_URL') ? AI_OLLAMA_BASE_URL : '';
            $this->addResult(
                "AI_OLLAMA_BASE_URL",
                "✓ " . $baseUrl,
                true
            );
        }

        $model = defined('AI_OLLAMA_MODEL') ? AI_OLLAMA_MODEL : '';
        $this->addResult("AI_OLLAMA_MODEL", "→ $model", !empty($model));

        $temp = defined('AI_OLLAMA_TEMPERATURE') ? AI_OLLAMA_TEMPERATURE : '';
        $this->addResult("AI_OLLAMA_TEMPERATURE", "→ $temp", true);

        echo "\n";
    }

    private function testConnectivity(): void {
        echo "🌐 Pruebas de conectividad...\n";

        $provider = strtolower(defined('AI_REPORT_PROVIDER') ? AI_REPORT_PROVIDER : 'ollama');

        if ($provider === 'remote') {
            $endpoint = defined('AI_REMOTE_ENDPOINT') ? AI_REMOTE_ENDPOINT : '';
            $this->testRemoteEndpoint($endpoint);
        } else {
            $baseUrl = defined('AI_OLLAMA_BASE_URL') ? AI_OLLAMA_BASE_URL : 'http://localhost:11434';
            $this->testLocalOllama($baseUrl);
        }

        echo "\n";
    }

    private function testRemoteEndpoint(string $endpoint): void {
        if (empty($endpoint)) {
            $this->addResult("Endpoint remoto", "✗ No configurado", false);
            return;
        }

        echo "  Probando: $endpoint\n";

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['test' => true]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->addResult(
                "Conectividad",
                "✗ Error: $error",
                false,
                "No se puede alcanzar el servidor. Verifica tu conexión a internet."
            );
        } else {
            $status = ($httpCode >= 200 && $httpCode < 500) ? "✓" : "✗";
            $this->addResult(
                "Conectividad",
                "$status HTTP $httpCode",
                $httpCode < 500
            );
        }
    }

    private function testLocalOllama(string $baseUrl): void {
        echo "  Probando: $baseUrl\n";

        $ch = curl_init($baseUrl . '/api/tags');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->addResult(
                "Ollama Local",
                "✗ Error: $error",
                false,
                "Asegúrate de tener Ollama corriendo: ollama serve"
            );
        } else if ($httpCode === 200) {
            $models = json_decode($response, true);
            $count = isset($models['models']) ? count($models['models']) : 0;
            $this->addResult(
                "Ollama Local",
                "✓ Conectado ($count modelos)",
                true
            );
        } else {
            $this->addResult(
                "Ollama Local",
                "✗ HTTP $httpCode",
                false
            );
        }
    }

    private function testAPICall(): void {
        echo "🤖 Probando llamada a IA...\n";

        if (!class_exists('OllamaClient')) {
            require_once __DIR__ . '/models/OllamaClient.php';
        }

        $client = new OllamaClient();
        $prompt = "Responde con una palabra: excelente";

        $start = microtime(true);
        $result = $client->generate($prompt);
        $duration = microtime(true) - $start;

        if ($result['success']) {
            $this->addResult(
                "Generación de IA",
                "✓ Éxito (" . round($duration * 1000) . "ms)",
                true
            );
            $this->addResult(
                "  Respuesta",
                "→ " . substr($result['text'], 0, 60) . (strlen($result['text']) > 60 ? "..." : ""),
                true
            );
            $this->addResult(
                "  Proveedor",
                "→ " . ($result['source'] ?? 'unknown'),
                true
            );
        } else {
            $this->addResult(
                "Generación de IA",
                "✗ Error: " . ($result['error'] ?? 'Unknown'),
                false,
                "Verifica la configuración y la API key"
            );
        }

        echo "\n";
    }

    private function compareProviders(): void {
        echo "⚖️  Comparación de proveedores...\n";

        $providers = [
            'ollama' => [
                'name' => 'Ollama Local',
                'endpoint' => defined('AI_OLLAMA_BASE_URL') ? AI_OLLAMA_BASE_URL : 'http://localhost:11434',
                'model' => 'gemma4:latest',
                'available' => true,
            ],
            'groq' => [
                'name' => 'Groq API',
                'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
                'model' => 'mixtral-8x7b-32768',
                'available' => true,
            ],
            'google' => [
                'name' => 'Google Gemini',
                'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
                'model' => 'gemini-2.0-flash',
                'available' => true,
            ],
        ];

        echo "  Proveedores disponibles:\n";
        foreach ($providers as $key => $provider) {
            echo "    • " . $provider['name'] . "\n";
            echo "      Modelo: " . $provider['model'] . "\n";
        }

        echo "\n";
    }

    private function displayResults(): void {
        echo "╔════════════════════════════════════════════════════════╗\n";
        echo "║                    RESUMEN DE PRUEBAS                  ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n\n";

        echo "✅ RESULTADOS:\n";
        foreach ($this->results as $item) {
            $icon = $item['pass'] ? '✓' : '✗';
            $color = $item['pass'] ? '' : '';
            echo "  $icon " . $item['name'] . ": " . $item['value'] . "\n";
        }

        if (!empty($this->warnings)) {
            echo "\n⚠️  ADVERTENCIAS:\n";
            foreach ($this->warnings as $warning) {
                echo "  ⚠️  " . $warning . "\n";
            }
        }

        if (!empty($this->errors)) {
            echo "\n❌ ERRORES:\n";
            foreach ($this->errors as $error) {
                echo "  ✗ " . $error . "\n";
            }
        }

        echo "\n";
        echo "╔════════════════════════════════════════════════════════╗\n";

        $allPass = empty($this->errors);
        if ($allPass) {
            echo "║  ✅ TODAS LAS PRUEBAS PASARON CORRECTAMENTE            ║\n";
            echo "║                                                        ║\n";
            echo "║  Próximos pasos:                                       ║\n";
            echo "║  1. Subir cambios a producción                        ║\n";
            echo "║  2. Monitorear logs de errores                        ║\n";
            echo "║  3. Verificar que los reportes se generen             ║\n";
        } else {
            echo "║  ❌ ALGUNAS PRUEBAS FALLARON                           ║\n";
            echo "║                                                        ║\n";
            echo "║  Revisa los errores arriba y intenta de nuevo         ║\n";
        }

        echo "╚════════════════════════════════════════════════════════╝\n\n";
    }

    private function addResult(string $name, string $value, bool $pass, ?string $warning = null): void {
        $this->results[] = [
            'name' => $name,
            'value' => $value,
            'pass' => $pass,
        ];

        if ($warning) {
            $this->warnings[] = "$name: $warning";
        }

        if (!$pass && strpos($value, '✗') !== false) {
            $this->errors[] = "$name: $value";
        }
    }
}

// Ejecutar tester
$tester = new MigrationTester();
$tester->run();
?>
