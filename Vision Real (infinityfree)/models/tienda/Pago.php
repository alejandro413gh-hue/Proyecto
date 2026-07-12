<?php
require_once __DIR__ . '/../../config/database.php';

class Pago {
    private $db;
    private $conn;

    public function __construct() {
        $this->db   = Database::getInstance();
        $this->conn = $this->db->getConnection();
        $this->initTable();
    }

    private function initTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS pagos_tienda (
            id INT AUTO_INCREMENT PRIMARY KEY,
            metodo VARCHAR(50) NOT NULL,
            clave VARCHAR(50) NOT NULL,
            valor TEXT DEFAULT NULL,
            imagen VARCHAR(255) DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_pago (metodo, clave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);
    }

    public function getConfig(): array {
        $config = [
            'nequi' => [
                'numero'   => '',
                'qr_img'   => '',
                'qr_url'   => '',
                'instrucciones' => 'Abre Nequi, escanea el QR o paga al número indicado.',
            ],
            'transferencia' => [
                'banco'    => '',
                'titular'  => '',
                'cuenta'   => '',
                'tipo_cuenta' => '',
                'qr_img'   => '',
                'qr_url'   => '',
                'instrucciones' => 'Realiza la transferencia y guarda el comprobante.',
            ],
        ];

        $r = $this->db->query("SELECT metodo, clave, valor, imagen FROM pagos_tienda");
        while ($row = $r->fetch_assoc()) {
            $metodo = $row['metodo'];
            $clave  = $row['clave'];
            if (!isset($config[$metodo])) continue;

            if ($clave === 'qr') {
                $config[$metodo]['qr_img'] = $row['imagen'] ?: '';
                $config[$metodo]['qr_url'] = $row['imagen'] ? $this->getImageUrl($row['imagen']) : '';
            } else {
                $config[$metodo][$clave] = $row['valor'] ?? '';
            }
        }

        return $config;
    }

    public function guardarTexto(string $metodo, string $clave, string $valor): bool {
        $metodo = strtolower(trim($metodo));
        $clave  = strtolower(trim($clave));
        $valor  = trim($valor);
        if (!in_array($metodo, ['nequi', 'transferencia'])) return false;
        if (!in_array($clave, ['numero', 'banco', 'titular', 'cuenta', 'tipo_cuenta'])) return false;

        $s = $this->conn->prepare(
            "INSERT INTO pagos_tienda (metodo, clave, valor)
             VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = CURRENT_TIMESTAMP"
        );
        $s->bind_param('sss', $metodo, $clave, $valor);
        return $s->execute();
    }

    public function subirQr(string $metodo, array $archivo): array {
        $metodo = strtolower(trim($metodo));
        if (!in_array($metodo, ['nequi', 'transferencia'])) {
            return ['error' => 'Método de pago inválido'];
        }

        if (empty($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            return ['error' => 'No se recibió el archivo'];
        }

        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return ['error' => 'Formato no permitido'];
        }

        $dir = __DIR__ . '/../../assets/img/pagos';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $nombre = sprintf('%s_qr_%s.%s', $metodo, time(), $ext);
        $destino = $dir . '/' . $nombre;
        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            return ['error' => 'No se pudo guardar la imagen'];
        }

        $s = $this->conn->prepare(
            "INSERT INTO pagos_tienda (metodo, clave, imagen)
             VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE imagen = VALUES(imagen), updated_at = CURRENT_TIMESTAMP"
        );
        $clave = 'qr';
        $s->bind_param('sss', $metodo, $clave, $nombre);
        if (!$s->execute()) {
            return ['error' => 'Error al guardar la referencia'];
        }

        return ['success' => true, 'imagen' => $nombre, 'url' => $this->getImageUrl($nombre)];
    }

    private function getImageUrl(string $imagen): string {
        if (defined('BASE_URL') && !empty(BASE_URL)) {
            return rtrim(BASE_URL, '/') . '/assets/img/pagos/' . $imagen;
        }
        return '/assets/img/pagos/' . $imagen;
    }
}
