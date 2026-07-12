<?php
/**
 * models/tienda/ClienteOnline.php
 * Gestiona registro y autenticación de clientes de la tienda online.
 * Reutiliza la tabla 'clientes' existente al confirmar el primer pedido.
 */
require_once __DIR__ . '/../../config/database.php';

class ClienteOnline {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->crearTablasSiNoExisten();
    }

    private function crearTablasSiNoExisten(): void {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS clientes_online (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT DEFAULT NULL,
                nombre VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL,
                password VARCHAR(255) NOT NULL,
                telefono VARCHAR(20) DEFAULT NULL,
                direccion TEXT DEFAULT NULL,
                ciudad VARCHAR(120) DEFAULT NULL,
                sexo ENUM('M','F','O') DEFAULT 'O',
                activo TINYINT(1) DEFAULT 1,
                token_verificar VARCHAR(64) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS clientes_online_sesiones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NOT NULL,
                token VARCHAR(128) NOT NULL,
                expira_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->ensureTableCollation('clientes_online');
        $this->db->ensureTableCollation('clientes_online_sesiones');
    }

    /* ─── Registro ──────────────────────────────────────────── */

    public function registrar(array $d): array {
        $nombre   = trim($d['nombre']   ?? '');
        $email    = strtolower(trim($d['email'] ?? ''));
        $password = $d['password'] ?? '';
        $telefono = trim($d['telefono'] ?? '');
        $sexo     = in_array($d['sexo'] ?? '', ['M','F','O']) ? $d['sexo'] : 'O';

        if (empty($nombre) || empty($email) || empty($password))
            return ['error' => 'Nombre, correo y contraseña son obligatorios.'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return ['error' => 'Correo electrónico inválido.'];
        if (strlen($password) < 6)
            return ['error' => 'La contraseña debe tener al menos 6 caracteres.'];
        if ($this->emailExiste($email))
            return ['error' => 'Ya existe una cuenta con ese correo.'];

        $hash  = password_hash($password, PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(32));

        $s = $this->db->prepare(
            "INSERT INTO clientes_online (nombre,email,password,telefono,sexo,token_verificar)
             VALUES (?,?,?,?,?,?)"
        );
        $s->bind_param("ssssss", $nombre, $email, $hash, $telefono, $sexo, $token);
        if (!$s->execute())
            return ['error' => 'Error al crear la cuenta. Intenta de nuevo.'];

        $id = $this->db->lastInsertId();
        $this->notificarNuevoClienteRegistrado([
            'id' => (int) $id,
            'nombre' => $nombre,
            'email' => $email,
        ]);
        return ['success' => true, 'id' => $id, 'token_verificar' => $token];
    }

    /* ─── Login ─────────────────────────────────────────────── */

    public function login(string $email, string $password): array {
        $email = strtolower(trim($email));
        $u = $this->findByEmail($email);

        if (!$u)                              return ['error' => 'Correo o contraseña incorrectos.'];
        if (!$u['activo'])                    return ['error' => 'Cuenta desactivada.'];
        if (!password_verify($password, $u['password']))
                                              return ['error' => 'Correo o contraseña incorrectos.'];

        // Generar token de sesión (30 días)
        $token   = bin2hex(random_bytes(32));
        $expira  = date('Y-m-d H:i:s', strtotime('+30 days'));
        $s = $this->db->prepare(
            "INSERT INTO clientes_online_sesiones (cliente_id, token, expira_at) VALUES (?,?,?)"
        );
        $s->bind_param("iss", $u['id'], $token, $expira);
        $s->execute();

        unset($u['password'], $u['token_verificar']);
        return ['success' => true, 'cliente' => $u, 'session_token' => $token];
    }

    /* ─── Verificar token de sesión ─────────────────────────── */

    public function verificarSesion(string $token): ?array {
        $s = $this->db->prepare(
            "SELECT co.* FROM clientes_online_sesiones cs
             JOIN clientes_online co ON cs.cliente_id = co.id
             WHERE cs.token = ? AND cs.expira_at > NOW() AND co.activo = 1"
        );
        $s->bind_param("s", $token);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        if (!$r) return null;
        unset($r['password'], $r['token_verificar']);
        return $r;
    }

    public function cerrarSesion(string $token): void {
        $s = $this->db->prepare("DELETE FROM clientes_online_sesiones WHERE token = ?");
        $s->bind_param("s", $token);
        $s->execute();
    }

    /* ─── Consultas ─────────────────────────────────────────── */

    public function findByEmail(string $email): ?array {
        $s = $this->db->prepare("SELECT * FROM clientes_online WHERE email = ?");
        $s->bind_param("s", $email);
        $s->execute();
        return $s->get_result()->fetch_assoc() ?: null;
    }

    public function getById(int $id): ?array {
        $s = $this->db->prepare("SELECT * FROM clientes_online WHERE id = ?");
        $s->bind_param("i", $id);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        if ($r) { unset($r['password'], $r['token_verificar']); }
        return $r ?: null;
    }

    public function actualizarPerfil(int $id, array $d): bool {
        $nombre    = trim($d['nombre']    ?? '');
        $telefono  = trim($d['telefono']  ?? '');
        $direccion = trim($d['direccion'] ?? '');
        $ciudad    = trim($d['ciudad']    ?? '');
        $sexo      = in_array($d['sexo'] ?? '', ['M','F','O']) ? $d['sexo'] : 'O';

        $s = $this->db->prepare(
            "UPDATE clientes_online SET nombre=?,telefono=?,direccion=?,ciudad=?,sexo=? WHERE id=?"
        );
        $s->bind_param("sssssi", $nombre, $telefono, $direccion, $ciudad, $sexo, $id);
        return $s->execute();
    }

    public function cambiarPassword(int $id, string $actual, string $nueva): array {
        $u = $this->getByIdConPassword($id);
        if (!$u || !password_verify($actual, $u['password']))
            return ['error' => 'Contraseña actual incorrecta.'];
        if (strlen($nueva) < 6)
            return ['error' => 'La nueva contraseña debe tener al menos 6 caracteres.'];
        $hash = password_hash($nueva, PASSWORD_BCRYPT);
        $s = $this->db->prepare("UPDATE clientes_online SET password=? WHERE id=?");
        $s->bind_param("si", $hash, $id);
        return $s->execute() ? ['success' => true] : ['error' => 'Error al cambiar contraseña.'];
    }

    /* ─── Vinculación con tabla clientes del sistema interno ── */

    public function vincularClienteInterno(int $clienteOnlineId, int $clienteId): void {
        $s = $this->db->prepare(
            "UPDATE clientes_online SET cliente_id=? WHERE id=? AND cliente_id IS NULL"
        );
        $s->bind_param("ii", $clienteId, $clienteOnlineId);
        $s->execute();
    }

    /* ─── Helpers privados ──────────────────────────────────── */

    private function emailExiste(string $email): bool {
        $s = $this->db->prepare("SELECT id FROM clientes_online WHERE email=?");
        $s->bind_param("s", $email);
        $s->execute();
        return $s->get_result()->num_rows > 0;
    }

    private function getByIdConPassword(int $id): ?array {
        $s = $this->db->prepare("SELECT * FROM clientes_online WHERE id=?");
        $s->bind_param("i", $id);
        $s->execute();
        return $s->get_result()->fetch_assoc() ?: null;
    }

    private function notificarNuevoClienteRegistrado(array $cliente): void {
        try {
            require_once __DIR__ . '/../ReporteIAService.php';

            $service = new ReporteIAService();
            $service->enviarReporteNuevoCliente([
                'id' => (int) ($cliente['id'] ?? 0),
                'nombre' => trim((string) ($cliente['nombre'] ?? '')),
                'email' => trim((string) ($cliente['email'] ?? '')),
            ]);
        } catch (Throwable $e) {
            error_log('ClienteOnline::notificarNuevoClienteRegistrado: ' . $e->getMessage());
        }
    }

}