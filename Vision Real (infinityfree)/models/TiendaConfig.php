<?php
require_once __DIR__ . '/../config/database.php';

class TiendaConfig {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->ensureTable();
    }

    public function defaults(): array {
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

    public function ensureTable(): void {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS store_settings (
                setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value TEXT DEFAULT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function getAll(): array {
        $settings = $this->defaults();
        $result = $this->db->query("SELECT setting_key, setting_value FROM store_settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $key = (string) ($row['setting_key'] ?? '');
                if ($key !== '') {
                    $settings[$key] = (string) ($row['setting_value'] ?? '');
                }
            }
        }

        return $settings;
    }

    public function save(array $settings): array {
        $allowed = array_keys($this->defaults());
        $stmt = $this->db->prepare(
            "INSERT INTO store_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );

        if (!$stmt) {
            return ['error' => 'No se pudo preparar el guardado de configuración.'];
        }

        foreach ($allowed as $key) {
            $value = (string) ($settings[$key] ?? '');
            $stmt->bind_param('ss', $key, $value);
            if (!$stmt->execute()) {
                return ['error' => 'No se pudo guardar la configuración.'];
            }
        }

        return ['success' => true];
    }

    public function normalizeWhatsapp(string $value): string {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    public function validateLatitude(string $value): bool {
        if (!is_numeric($value)) {
            return false;
        }
        $latitude = (float) $value;
        return $latitude >= -90 && $latitude <= 90;
    }

    public function validateLongitude(string $value): bool {
        if (!is_numeric($value)) {
            return false;
        }
        $longitude = (float) $value;
        return $longitude >= -180 && $longitude <= 180;
    }

    public function extractCoordinatesFromMapsUrl(string $url): array {
        $url = trim($url);
        if ($url === '') {
            return ['', ''];
        }

        $patterns = [
            '/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
            '/[?&]q=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
            '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                return [$m[1], $m[2]];
            }
        }

        return ['', ''];
    }

    public function buildMapsUrl(string $latitude, string $longitude, string $fallbackUrl = ''): string {
        $latitude = trim($latitude);
        $longitude = trim($longitude);
        if ($latitude !== '' && $longitude !== '') {
            return 'https://www.google.com/maps?q=' . rawurlencode($latitude . ',' . $longitude);
        }
        $fallbackUrl = trim($fallbackUrl);
        return $fallbackUrl;
    }

    public function buildMapsEmbedUrl(string $latitude, string $longitude): string {
        $latitude = trim($latitude);
        $longitude = trim($longitude);
        if ($latitude === '' || $longitude === '') {
            return '';
        }
        return 'https://maps.google.com/maps?output=embed&q=' . rawurlencode($latitude . ',' . $longitude);
    }

    public function buildMapsEmbedFromUrl(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_contains($url, 'output=embed')) {
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
