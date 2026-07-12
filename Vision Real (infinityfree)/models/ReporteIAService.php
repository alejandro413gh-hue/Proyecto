<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/ReporteIAData.php';
require_once __DIR__ . '/ReporteIAPrompt.php';
require_once __DIR__ . '/OllamaClient.php';
require_once __DIR__ . '/TelegramBot.php';

class ReporteIAService {
    private ReporteIAData $dataProvider;
    private ReporteIAPrompt $promptBuilder;
    private OllamaClient $ollamaClient;
    private TelegramBot $telegramBot;

    public function __construct() {
        $this->dataProvider = new ReporteIAData();
        $this->promptBuilder = new ReporteIAPrompt();
        $this->ollamaClient = new OllamaClient();
        $this->telegramBot = new TelegramBot();
    }


    public function enviarReporteNuevoCliente(array $cliente, bool $enviarATelegram = true): array {
        if (!(defined('AI_REPORT_ENABLED') ? (bool) AI_REPORT_ENABLED : true)) {
            $markdown = $this->buildNuevoClienteFallback($cliente);
            $telegramResult = [
                'success' => false,
                'skipped' => true,
                'message' => 'La IA está deshabilitada en la configuración.',
            ];

            if ($enviarATelegram && $this->telegramBot->configured()) {
                $telegramResult = $this->telegramBot->sendMessage($markdown);
            }

            return [
                'success' => true,
                'message' => 'Reporte de nuevo cliente generado con texto de respaldo.',
                'markdown' => $markdown,
                'provider' => 'fallback',
                'model' => defined('AI_OLLAMA_MODEL') ? AI_OLLAMA_MODEL : 'gemma4:latest',
                'telegram' => $telegramResult,
            ];
        }

        $nombre = trim((string) ($cliente['nombre'] ?? ''));
        $email = trim((string) ($cliente['email'] ?? ''));
        $id = (int) ($cliente['id'] ?? 0);

        if ($nombre === '' || $email === '') {
            return [
                'success' => false,
                'error' => 'Nombre y correo son obligatorios para el reporte del cliente.',
            ];
        }

        $prompt = "Redacta una nota interna breve en Markdown para el equipo de una tienda. Debe sonar profesional y natural, no robótica.

Estructura sugerida:
- Título corto de alerta o novedad.
- Una frase de contexto.
- Bloque breve con los datos del cliente.
- Cierre con una sugerencia de seguimiento.

Datos del cliente:
- ID: {$id}
- Nombre completo: {$nombre}
- Correo: {$email}

Requisitos:
- Máximo 6 líneas útiles.
- No inventes datos.
- No agregues análisis comercial.
- Mantén un tono cordial y ejecutivo.";
        $system = 'Eres un asistente interno de operaciones. Responde solo en Markdown. Escribe con tono profesional, breve y humano. Evita frases telegráficas y repeticiones.';

        $aiResult = $this->ollamaClient->generate($prompt, [
            'system' => $system,
        ]);

        $markdown = '';
        if (($aiResult['success'] ?? false) === true) {
            $markdown = trim((string) ($aiResult['text'] ?? ''));
        }

        if ($markdown === '') {
            $markdown = $this->buildNuevoClienteFallback([
                'id' => $id,
                'nombre' => $nombre,
                'email' => $email,
            ]);
        }

        $markdown = $this->normalizeMarkdown($markdown);

        $telegramResult = [
            'success' => false,
            'skipped' => true,
            'message' => 'Telegram no fue usado.',
        ];

        if ($enviarATelegram && $this->telegramBot->configured()) {
            $telegramResult = $this->telegramBot->sendMessage($markdown);
        }

        return [
            'success' => true,
            'message' => 'Reporte de nuevo cliente generado correctamente.',
            'markdown' => $markdown,
            'markdown_length' => function_exists('mb_strlen') ? mb_strlen($markdown, 'UTF-8') : strlen($markdown),
            'provider' => $aiResult['source'] ?? 'ollama_local',
            'model' => $aiResult['model'] ?? (defined('AI_OLLAMA_MODEL') ? AI_OLLAMA_MODEL : 'gemma4:latest'),
            'telegram' => $telegramResult,
        ];
    }

    public function generarAnalisis(bool $enviarATelegram = true): array {
        if (!(defined('AI_REPORT_ENABLED') ? (bool) AI_REPORT_ENABLED : true)) {
            return ['success' => false, 'error' => 'La IA está deshabilitada en la configuración.'];
        }

        $data = $this->dataProvider->collect();
        $prompt = $this->promptBuilder->build($data);

        $system = 'Eres un analista empresarial profesional. Responde solo en Markdown. Sé breve, directo y accionable. No uses tablas largas. No inventes datos. Máximo 2 bullets por sección.';
        $aiResult = $this->ollamaClient->generate($prompt, [
            'system' => $system,
        ]);

        if (($aiResult['success'] ?? false) !== true) {
            return [
                'success' => false,
                'error' => $aiResult['error'] ?? 'No se pudo generar el análisis con IA.',
                'data' => $data,
                'prompt' => $prompt,
                'source' => $aiResult['source'] ?? null,
            ];
        }

        $markdown = trim((string) ($aiResult['text'] ?? ''));
        if ($markdown === '') {
            return [
                'success' => false,
                'error' => 'La IA no devolvió contenido utilizable.',
                'data' => $data,
                'prompt' => $prompt,
                'source' => $aiResult['source'] ?? null,
            ];
        }

        $markdown = $this->normalizeMarkdown($markdown);

        $telegramResult = [
            'success' => false,
            'skipped' => true,
            'message' => 'Telegram no fue usado.',
        ];

        if ($enviarATelegram && $this->telegramBot->configured()) {
            $telegramResult = $this->telegramBot->sendMessage($markdown);
        }

        return [
            'success' => true,
            'message' => 'Análisis generado correctamente.',
            'markdown' => $markdown,
            'markdown_length' => function_exists('mb_strlen') ? mb_strlen($markdown, 'UTF-8') : strlen($markdown),
            'provider' => $aiResult['source'] ?? 'ollama_local',
            'model' => $aiResult['model'] ?? (defined('AI_OLLAMA_MODEL') ? AI_OLLAMA_MODEL : 'gemma4:latest'),
            'telegram' => $telegramResult,
            'data' => $data,
        ];
    }
    private function buildNuevoClienteFallback(array $cliente): string {
        $nombre = trim((string) ($cliente['nombre'] ?? 'Cliente'));
        $email = trim((string) ($cliente['email'] ?? ''));
        $id = (int) ($cliente['id'] ?? 0);

        $lines = [];
        $lines[] = 'Nuevo cliente registrado';
        $lines[] = '';
        $lines[] = 'Se activó un nuevo registro en la tienda online.';
        $lines[] = '';
        $lines[] = 'ID: ' . $id;
        $lines[] = 'Nombre: ' . ($nombre !== '' ? $nombre : 'Sin nombre');
        $lines[] = 'Correo: ' . ($email !== '' ? $email : 'No disponible');
        $lines[] = '';
        $lines[] = 'Sugerencia: dar seguimiento de bienvenida y revisar si requiere asesoría inicial.';

        return implode("\n", $lines);
    }

    private function normalizeMarkdown(string $markdown): string {
        $markdown = preg_replace('/[ \t]+$/m', '', $markdown) ?? $markdown;
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown) ?? $markdown;
        return trim($markdown);
    }

}
