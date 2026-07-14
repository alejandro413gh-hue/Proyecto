<?php
/**
 * models/tienda/Pedido.php
 * Gestiona pedidos online.
 * Al confirmar el pago crea una Venta (tipo_venta='online') en la tabla ventas existente,
 * descuenta stock de producto_tallas igual que una venta física.
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Descuento.php';
require_once __DIR__ . '/../Cliente.php';
require_once __DIR__ . '/../Talla.php';
require_once __DIR__ . '/../Factura.php';
require_once __DIR__ . '/../Producto.php';
require_once __DIR__ . '/ClienteOnline.php';

if (!defined('WHATSAPP_SEND_NOTIFICATIONS')) {
    require_once __DIR__ . '/../../config/config.php';
}

class Pedido {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->crearTablasSiNoExisten();
        new ClienteOnline();
    }

    private function crearTablasSiNoExisten(): void {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS pedidos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_online_id INT DEFAULT NULL,
                cliente_id INT DEFAULT NULL,
                venta_id INT DEFAULT NULL,
                estado ENUM('pendiente','pagado','preparando','enviado','entregado','cancelado') DEFAULT 'pendiente',
                tipo_entrega ENUM('domicilio','recoge_tienda') DEFAULT 'domicilio',
                subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
                descuento DECIMAL(10,2) NOT NULL DEFAULT 0,
                descuento_id INT DEFAULT NULL,
                total DECIMAL(10,2) NOT NULL DEFAULT 0,
                notas TEXT DEFAULT NULL,
                envio_nombre VARCHAR(150) DEFAULT NULL,
                envio_telefono VARCHAR(50) DEFAULT NULL,
                envio_direccion TEXT DEFAULT NULL,
                envio_ciudad VARCHAR(120) DEFAULT NULL,
                metodo_pago VARCHAR(80) DEFAULT NULL,
                comprobante_img VARCHAR(255) DEFAULT NULL,
                printable_token VARCHAR(64) DEFAULT NULL,
                creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                actualizado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS pedido_detalle (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pedido_id INT NOT NULL,
                producto_id INT NOT NULL,
                talla VARCHAR(50) DEFAULT NULL,
                cantidad INT NOT NULL,
                precio_unitario DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS pedido_historial (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pedido_id INT NOT NULL,
                estado_ant VARCHAR(50) DEFAULT NULL,
                estado_new VARCHAR(50) NOT NULL,
                usuario_id INT DEFAULT NULL,
                nota TEXT DEFAULT NULL,
                creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->ensureTableCollation('pedidos');
        $this->db->ensureTableCollation('pedido_detalle');
        $this->db->ensureTableCollation('pedido_historial');

        $this->ensureTiendaPedidosSchema();
    }

    private function ensureTiendaPedidosSchema(): void {
        $columns = [
            'cliente_online_id' => 'INT DEFAULT NULL',
            'numero_pedido'     => 'VARCHAR(100) DEFAULT NULL',
            'subtotal'          => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
            'descuento'         => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
            'descuento_id'      => 'INT DEFAULT NULL',
            'envio_nombre'      => 'VARCHAR(150) DEFAULT NULL',
            'envio_telefono'    => 'VARCHAR(50) DEFAULT NULL',
            'envio_direccion'   => 'TEXT DEFAULT NULL',
            'envio_ciudad'      => 'VARCHAR(120) DEFAULT NULL',
            'metodo_pago'       => 'VARCHAR(80) DEFAULT NULL',
            'comprobante_img'   => 'VARCHAR(255) DEFAULT NULL',
            'printable_token'   => 'VARCHAR(64) DEFAULT NULL',
            'creado_at'         => 'TIMESTAMP NULL DEFAULT NULL',
            'actualizado_at'    => 'TIMESTAMP NULL DEFAULT NULL',
        ];

        foreach ($columns as $column => $definition) {
            if (!$this->columnExists('pedidos', $column)) {
                $this->db->query("ALTER TABLE pedidos ADD COLUMN {$column} {$definition}");
            }
        }

        $this->db->query(
            "ALTER TABLE pedidos
                MODIFY COLUMN estado ENUM('pendiente','pagado','preparando','enviado','entregado','cancelado') DEFAULT 'pendiente',
                MODIFY COLUMN tipo_entrega ENUM('domicilio','recoge_tienda','recoger_tienda') DEFAULT 'domicilio'"
        );

        if ($this->columnExists('pedidos', 'creado')) {
            $this->db->query("UPDATE pedidos SET creado_at = creado WHERE creado_at IS NULL AND creado IS NOT NULL");
        }
        if ($this->columnExists('pedidos', 'actualizado')) {
            $this->db->query("UPDATE pedidos SET actualizado_at = actualizado WHERE actualizado_at IS NULL AND actualizado IS NOT NULL");
        }
    }

    private function columnExists(string $table, string $column): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        if (!$stmt) {
            return false;
        }

        $dbName = DB_NAME;
        $stmt->bind_param('sss', $dbName, $table, $column);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        return !empty($row['c']);
    }

    /* ─── Crear pedido desde carrito ────────────────────────── */

    public function crearDesdeCarrito(int $clienteOnlineId, array $datosEnvio, array $carritoContenido, ?int $descuentoId = null): array {
        $conn = $this->db->getConnection();

        if (empty($carritoContenido['items']))
            return ['error' => 'El carrito está vacío.'];

        $inventario = new Talla();
        $stockCheck = $inventario->validarItems($carritoContenido['items']);
        if (!($stockCheck['success'] ?? false)) {
            return ['error' => $stockCheck['error'] ?? 'No hay suficiente inventario para completar el pedido.'];
        }

        $conn->begin_transaction();
        try {
            // Calcular totales
            $subtotal  = (float)$carritoContenido['subtotal'];
            $descuento = 0;
            if ($descuentoId) {
                $dm   = new Descuento();
                $desc = $dm->getById($descuentoId);
                if ($desc) {
                    $descuento = $desc['tipo_descuento'] === 'porcentaje'
                        ? round($subtotal * $desc['valor'] / 100, 0)
                        : min((float)$desc['valor'], $subtotal);
                }
            }
            $total = max(0, $subtotal - $descuento);

            // Número de pedido único
            $numeroPedido = $this->generarNumero();

            // Resolver tipo_entrega
            $tipoEntrega = in_array($datosEnvio['tipo_entrega'] ?? '', ['domicilio','recoge_tienda'])
                ? $datosEnvio['tipo_entrega'] : 'domicilio';
            $estado = $tipoEntrega === 'recoge_tienda' ? 'preparando' : 'pendiente';

            // Buscar cliente_id interno si existe y sincronizar si falta
            $clienteId = $this->resolverClienteInterno($clienteOnlineId);
            if ($clienteId === null) {
                $clienteId = $this->sincronizarClienteInterno($clienteOnlineId, $datosEnvio);
            }

            // Asegurar columna para token imprimible
            $this->ensurePrintableTokenColumnExists();

            // Generar token público para factura imprimible
            $printableToken = bin2hex(random_bytes(16));

            // Insertar pedido
            $s = $conn->prepare(
                "INSERT INTO pedidos
                 (numero_pedido,cliente_online_id,cliente_id,estado,tipo_entrega,
                  subtotal,descuento,descuento_id,total,notas,
                  envio_nombre,envio_telefono,envio_direccion,envio_ciudad,metodo_pago,printable_token)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $estado  = $tipoEntrega === 'recoge_tienda' ? 'preparando' : 'pendiente';
            $notas   = trim($datosEnvio['notas'] ?? '');
            $nombre  = trim($datosEnvio['nombre']    ?? '');
            $tel     = trim($datosEnvio['telefono']   ?? '');
            $dir     = trim($datosEnvio['direccion']  ?? '');
            $ciudad  = trim($datosEnvio['ciudad']     ?? '');
            $metodo  = trim($datosEnvio['metodo_pago'] ?? 'transferencia');

            $s->bind_param(
                "siissddidsssssss",
                $numeroPedido, $clienteOnlineId, $clienteId, $estado, $tipoEntrega,
                $subtotal, $descuento, $descuentoId, $total, $notas,
                $nombre, $tel, $dir, $ciudad, $metodo, $printableToken
            );
            $s->execute();
            $pedidoId = (int)$conn->insert_id;

            // Insertar detalle del pedido
            foreach ($carritoContenido['items'] as $item) {
                $s2 = $conn->prepare(
                    "INSERT INTO pedido_detalle
                     (pedido_id,producto_id,talla,cantidad,precio_unitario,subtotal)
                     VALUES (?,?,?,?,?,?)"
                );
                $subItem = (float)$item['precio_unitario'] * (int)$item['cantidad'];
                $s2->bind_param(
                    "iisidd",
                    $pedidoId, $item['producto_id'], $item['talla'],
                    $item['cantidad'], $item['precio_unitario'], $subItem
                );
                $s2->execute();
            }

            // Historial
            $this->registrarHistorial($pedidoId, null, $estado, null, 'Pedido creado online', $conn);

            $conn->commit();

            $facturaUrl = null;
            if ($datosEnvio['metodo_pago'] === 'contraentrega') {
                $facturaUrl = rtrim(BASE_URL, '/') . '/tienda/factura_publica.php?id=' . $pedidoId . '&t=' . $printableToken;
            }

            $this->notificarNuevoPedidoOnlineHtml($pedidoId, $numeroPedido, $clienteOnlineId, $datosEnvio, $carritoContenido, $total);
            $this->enviarNotificacionEmailAdmin($pedidoId);

            return ['success' => true, 'pedido_id' => $pedidoId, 'numero' => $numeroPedido, 'total' => $total, 'factura_url' => $facturaUrl];

        } catch (Exception $e) {
            $conn->rollback();
            return ['error' => $e->getMessage()];
        }
    }

    public function crearDesdéCarrito(int $clienteOnlineId, array $datosEnvio, array $carritoContenido, ?int $descuentoId = null): array {
        return $this->crearDesdeCarrito($clienteOnlineId, $datosEnvio, $carritoContenido, $descuentoId);
    }

    private function ensurePrintableTokenColumnExists(): void {
        $dbName = DB_NAME;
        $conn = $this->db->getConnection();
        $q = $conn->prepare(
            "SELECT COUNT(*) as c FROM information_schema.columns WHERE table_schema=? AND table_name='pedidos' AND column_name='printable_token'"
        );
        $q->bind_param('s', $dbName);
        $q->execute();
        $res = $q->get_result()->fetch_assoc();
        if (empty($res) || (int)$res['c'] === 0) {
            // Agregar columna
            $conn->query("ALTER TABLE pedidos ADD COLUMN printable_token VARCHAR(64) DEFAULT NULL");
        }
    }

    /* ─── Confirmar pago y crear venta en sistema interno ───── */

    public function confirmarPago(int $pedidoId, int $usuarioId, ?string $referenciaPago = null): array {
        $conn = $this->db->getConnection();

        $pedido = $this->getById($pedidoId);
        if (!$pedido)           return ['error' => 'Pedido no encontrado.'];
        if (in_array($pedido['tipo_entrega'], ['recoge_tienda','recoger_tienda'], true)) {
            return ['error' => 'Este pedido no requiere confirmación de pago porque es para recoger en tienda.'];
        }
        if ($pedido['estado'] !== 'pendiente')
                                return ['error' => 'El pedido ya fue procesado.'];

        $detalle = $this->getDetalle($pedidoId);
        if (empty($detalle))    return ['error' => 'Pedido sin productos.'];

        $inventario = new Talla();
        $stockCheck = $inventario->validarItems(array_map(static function ($d) {
            return [
                'producto_id' => (int) ($d['producto_id'] ?? 0),
                'talla' => (string) ($d['talla'] ?? ''),
                'cantidad' => (int) ($d['cantidad'] ?? 0),
            ];
        }, $detalle));
        if (!($stockCheck['success'] ?? false)) {
            return ['error' => $stockCheck['error'] ?? 'No hay suficiente inventario para completar la venta.'];
        }

        $conn->begin_transaction();
        try {
            // Preparar productos para Venta::create()
            $productosVenta = [];
            foreach ($detalle as $d) {
                $productosVenta[] = [
                    'producto_id' => (int)$d['producto_id'],
                    'precio'      => (float)$d['precio_unitario'],
                    'cantidad'    => (int)$d['cantidad'],
                    'talla'       => $d['talla'] ?? null,
                ];
            }

            // Usar el modelo Venta existente para descontar stock y crear registro
            require_once __DIR__ . '/../Venta.php';
            $vm = new Venta();
            $resultado = $vm->create(
                $pedido['cliente_id'] ?? null,   // cliente_id (puede ser null)
                $usuarioId,
                $productosVenta,
                "Pedido online #{$pedido['numero_pedido']}",
                null,                             // promocion_id
                (float)$pedido['descuento'],
                (float)$pedido['subtotal'],
                $pedido['descuento_id'],
                null,
                'online',
                'completada',
                false
            );

            if (!($resultado['success'] ?? false))
                throw new Exception($resultado['error'] ?? 'Error al procesar venta.');

            $ventaId = $resultado['venta_id'];
            $totalVenta = (float) ($resultado['total'] ?? $pedido['total']);

            $facturaM = new Factura();
            $clienteNombreFactura = trim((string) ($pedido['envio_nombre'] ?? ''));
            if ($clienteNombreFactura === '') {
                $clienteNombreFactura = trim((string) ($pedido['cliente_nombre'] ?? 'Cliente'));
            }
            $clienteTelefonoFactura = trim((string) ($pedido['envio_telefono'] ?? ''));
            if ($clienteTelefonoFactura === '') {
                $clienteTelefonoFactura = trim((string) ($pedido['cliente_telefono'] ?? ''));
            }
            $clienteDocumentoFactura = $clienteTelefonoFactura !== '' ? $clienteTelefonoFactura : 'CF';
            $factura = $facturaM->crear(
                $ventaId,
                $clienteNombreFactura,
                $clienteDocumentoFactura,
                (float) $pedido['subtotal'],
                (float) $pedido['descuento'],
                $totalVenta,
                $clienteTelefonoFactura
            );
            if (!($factura['success'] ?? false)) {
                error_log('Pedido::confirmarPago factura: ' . ($factura['error'] ?? 'Error desconocido'));
            }

            // Marcar venta como online
            $conn->query("UPDATE ventas SET tipo_venta='online' WHERE id={$ventaId}");

            // Actualizar pedido
            $s = $conn->prepare(
                "UPDATE pedidos SET estado='pagado', venta_id=?, referencia_pago=? WHERE id=?"
            );
            $s->bind_param("isi", $ventaId, $referenciaPago, $pedidoId);
            $s->execute();

            // Historial
            $this->registrarHistorial($pedidoId, 'pendiente', 'pagado', $usuarioId, "Pago confirmado. Venta #{$ventaId}", $conn);

            $conn->commit();

            $detalleVenta = [];
            $productoModel = new Producto();
            foreach ($detalle as $item) {
                $productoId = (int) ($item['producto_id'] ?? 0);
                $producto = $productoId > 0 ? $productoModel->getById($productoId) : null;
                $detalleVenta[] = [
                    'producto_id' => $productoId,
                    'nombre' => (string) ($producto['nombre'] ?? ''),
                    'referencia' => (string) ($producto['codigo'] ?? $producto['referencia'] ?? ''),
                    'talla' => trim((string) ($item['talla'] ?? '')),
                    'cantidad' => (int) ($item['cantidad'] ?? 0),
                    'precio_unitario' => (float) ($item['precio_unitario'] ?? 0),
                    'subtotal' => (float) ($item['subtotal'] ?? 0),
                ];
            }

            $this->enviarReporteVentaTelegram([
                'origen' => 'Tienda Online',
                'cliente_nombre' => trim((string) ($pedido['envio_nombre'] ?? ($pedido['cliente_nombre'] ?? 'Cliente'))),
                'cliente_nit' => 'No registrado',
                'cliente_telefono' => trim((string) ($pedido['envio_telefono'] ?? ($pedido['cliente_telefono'] ?? ''))),
                'cliente_correo' => 'No registrado',
                'items' => $detalleVenta,
                'subtotal' => (float) $pedido['subtotal'],
                'descuento' => (float) $pedido['descuento'],
                'envio' => 0.0,
                'total' => $totalVenta,
                'tipo_entrega' => $pedido['tipo_entrega'] ?? 'domicilio',
                'direccion' => trim((string) ($pedido['envio_direccion'] ?? '')),
                'ciudad' => trim((string) ($pedido['envio_ciudad'] ?? '')),
                'metodo_pago' => trim((string) ($pedido['metodo_pago'] ?? '')),
                'estado_pago' => 'Completado',
                'numero_factura' => $factura['numero_factura'] ?? '',
                'fecha_factura' => date('Y-m-d'),
                'hora_factura' => date('H:i'),
            ]);

            return ['success' => true, 'venta_id' => $ventaId];

        } catch (Exception $e) {
            $conn->rollback();
            return ['error' => $e->getMessage()];
        }
    }

    private function enviarReporteVentaTelegram(array $ventaData): void {
        try {
            require_once __DIR__ . '/../TelegramBot.php';
            $bot = new TelegramBot();
            if (!$bot->configured()) {
                return;
            }

            $bot->enviarReporteVenta($ventaData);
        } catch (Throwable $e) {
            error_log('Pedido::enviarReporteVentaTelegram: ' . $e->getMessage());
        }
    }

    /* ─── Cambiar estado ────────────────────────────────────── */

    public function cambiarEstado(int $pedidoId, string $nuevoEstado, int $usuarioId, string $nota = ''): array {
        $estados = ['pendiente','pagado','preparando','enviado','entregado','cancelado'];
        if (!in_array($nuevoEstado, $estados))
            return ['error' => 'Estado inválido.'];

        $pedido = $this->getById($pedidoId);
        if (!$pedido) return ['error' => 'Pedido no encontrado.'];

        if (in_array($pedido['tipo_entrega'], ['recoge_tienda','recoger_tienda'], true)) {
            if ($pedido['estado'] !== 'preparando' || $nuevoEstado !== 'entregado') {
                return ['error' => 'Los pedidos para recoger en tienda solo pueden pasar de Preparando a Entregado.'];
            }
        }

        $s = $this->db->prepare("UPDATE pedidos SET estado=? WHERE id=?");
        $s->bind_param("si", $nuevoEstado, $pedidoId);
        $s->execute();

        $this->registrarHistorial($pedidoId, $pedido['estado'], $nuevoEstado, $usuarioId, $nota);
        return ['success' => true];
    }

    /* ─── Subir comprobante de pago ─────────────────────────── */

    public function subirComprobante(int $pedidoId, int $clienteOnlineId, array $archivo): array {
        $pedido = $this->getById($pedidoId);
        if (!$pedido || $pedido['cliente_online_id'] !== $clienteOnlineId)
            return ['error' => 'Pedido no encontrado.'];

        $ext      = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg','jpeg','png','webp','pdf'];
        if (!in_array($ext, $permitidos))
            return ['error' => 'Tipo de archivo no permitido.'];

        $nombre   = 'comprobante_' . $pedidoId . '_' . time() . '.' . $ext;
        $destino  = __DIR__ . '/../../assets/img/comprobantes/' . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $destino))
            return ['error' => 'Error al subir el archivo.'];

        $s = $this->db->prepare("UPDATE pedidos SET comprobante_img=? WHERE id=?");
        $s->bind_param("si", $nombre, $pedidoId);
        $s->execute();

        return ['success' => true, 'archivo' => $nombre];
    }

    /* ─── Consultas ─────────────────────────────────────────── */

    public function getById(int $id): ?array {
        $s = $this->db->prepare(
            "SELECT p.*, COALESCE(co.nombre, p.envio_nombre) as cliente_nombre, COALESCE(co.email, '') as cliente_email,
                    COALESCE(co.telefono, p.envio_telefono) as cliente_telefono
             FROM pedidos p
             LEFT JOIN clientes_online co ON p.cliente_online_id = co.id
             WHERE p.id = ?"
        );
        $s->bind_param("i", $id);
        $s->execute();
        return $s->get_result()->fetch_assoc() ?: null;
    }

    public function getDetalle(int $pedidoId): array {
        $s = $this->db->prepare(
            "SELECT pd.*, p.nombre as producto_nombre, p.imagen
             FROM pedido_detalle pd
             JOIN productos p ON pd.producto_id = p.id
             WHERE pd.pedido_id = ?"
        );
        if (!$s) {
            error_log('Pedido::getDetalle prepare failed: ' . $this->db->getConnection()->error);
            return [];
        }
        $s->bind_param("i", $pedidoId);
        $s->execute();
        $r = $s->get_result();
        $a = [];
        if ($r) {
            while ($row = $r->fetch_assoc()) $a[] = $row;
        }
        return $a;
    }

    public function getPorCliente(int $clienteOnlineId, int $limit = 20): array {
        $s = $this->db->prepare(
            "SELECT * FROM pedidos WHERE cliente_online_id=?
             ORDER BY creado_at DESC LIMIT ?"
        );
        $s->bind_param("ii", $clienteOnlineId, $limit);
        $s->execute();
        $r = $s->get_result();
        $a = [];
        while ($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    public function getAllAdmin(string $estado = '', int $limit = 50): array {
        $where = $estado ? "WHERE p.estado='" . $this->db->escape($estado) . "'" : '';
        $limitSql = $limit > 0 ? " LIMIT {$limit}" : '';
        $r = $this->db->query(
            "SELECT p.*, COALESCE(co.nombre, p.envio_nombre) as cliente_nombre, COALESCE(co.email, '') as cliente_email
             FROM pedidos p
             LEFT JOIN clientes_online co ON p.cliente_online_id = co.id
             {$where}
             ORDER BY p.creado_at DESC{$limitSql}"
        );
        if (!$r) {
            error_log('Pedido::getAllAdmin query failed: ' . $this->db->getConnection()->error);
            return [];
        }
        $a = [];
        while ($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    public function countAllAdmin(string $estado = ''): int {
        $where = $estado ? "WHERE estado='" . $this->db->escape($estado) . "'" : '';
        $r = $this->db->query("SELECT COUNT(*) as total FROM pedidos {$where}");
        if (!$r) {
            error_log('Pedido::countAllAdmin query failed: ' . $this->db->getConnection()->error);
            return 0;
        }
        $row = $r->fetch_assoc();
        return (int) ($row['total'] ?? 0);
    }

    public function getHistorial(int $pedidoId): array {
        $s = $this->db->prepare(
            "SELECT ph.*, u.nombre as usuario_nombre
             FROM pedido_historial ph
             LEFT JOIN usuarios u ON ph.usuario_id = u.id
             WHERE ph.pedido_id = ? ORDER BY ph.creado_at ASC"
        );
        $s->bind_param("i", $pedidoId);
        $s->execute();
        $r = $s->get_result();
        $a = [];
        while ($row = $r->fetch_assoc()) $a[] = $row;
        return $a;
    }

    /* ─── Estadísticas para dashboard ──────────────────────── */

    public function countPorEstado(): array {
        $r = $this->db->query(
            "SELECT estado, COUNT(*) as total FROM pedidos GROUP BY estado"
        );
        if (!$r) {
            error_log('Pedido::countPorEstado query failed: ' . $this->db->getConnection()->error);
            return [];
        }
        $a = [];
        while ($row = $r->fetch_assoc()) $a[$row['estado']] = (int)$row['total'];
        return $a;
    }

    public function getTotalOnlineHoy(): float {
        $r = $this->db->query(
            "SELECT COALESCE(SUM(total),0) as t FROM pedidos
             WHERE DATE(creado_at)=CURDATE() AND estado IN('pagado','preparando','enviado','entregado')"
        );
        if (!$r) {
            error_log('Pedido::getTotalOnlineHoy query failed: ' . $this->db->getConnection()->error);
            return 0.0;
        }
        $row = $r->fetch_assoc();
        return (float)($row['t'] ?? 0);
    }

    /* ─── Helpers privados ──────────────────────────────────── */

    private function generarNumero(): string {
        $fecha = date('Ymd');
        $r     = $this->db->query(
            "SELECT COUNT(*) as t FROM pedidos WHERE DATE(creado_at)=CURDATE()"
        );
        $n = ((int)$r->fetch_assoc()['t']) + 1;
        return 'ON-' . $fecha . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    private function resolverClienteInterno(int $clienteOnlineId): ?int {
        $s = $this->db->prepare("SELECT cliente_id FROM clientes_online WHERE id=?");
        $s->bind_param("i", $clienteOnlineId);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        return $r && $r['cliente_id'] ? (int)$r['cliente_id'] : null;
    }

    private function sincronizarClienteInterno(int $clienteOnlineId, array $datosEnvio): ?int {
        $clienteOnline = $this->obtenerClienteOnline($clienteOnlineId);
        if (!$clienteOnline) {
            return null;
        }

        if (!empty($clienteOnline['cliente_id'])) {
            return (int) $clienteOnline['cliente_id'];
        }

        $nombre   = trim((string) ($clienteOnline['nombre'] ?? $datosEnvio['nombre'] ?? 'Consumidor Final'));
        $email    = trim(strtolower((string) ($clienteOnline['email'] ?? $datosEnvio['email'] ?? '')));
        $telefono = trim((string) ($clienteOnline['telefono'] ?? $datosEnvio['telefono'] ?? ''));
        $direccion= trim((string) ($clienteOnline['direccion'] ?? $datosEnvio['direccion'] ?? ''));
        $sexo     = in_array($clienteOnline['sexo'] ?? 'O', ['M','F','O'], true) ? (string) $clienteOnline['sexo'] : 'O';

        $existing = null;
        if ($email !== '') {
            $s = $this->db->prepare("SELECT id FROM clientes WHERE email = ? LIMIT 1");
            $s->bind_param('s', $email);
            $s->execute();
            $existing = $s->get_result()->fetch_assoc() ?: null;
        }

        if (!$existing && $telefono !== '') {
            $s = $this->db->prepare("SELECT id FROM clientes WHERE telefono = ? LIMIT 1");
            $s->bind_param('s', $telefono);
            $s->execute();
            $existing = $s->get_result()->fetch_assoc() ?: null;
        }

        if (!$existing && $nombre !== '') {
            $s = $this->db->prepare("SELECT id FROM clientes WHERE nombre = ? LIMIT 1");
            $s->bind_param('s', $nombre);
            $s->execute();
            $existing = $s->get_result()->fetch_assoc() ?: null;
        }

        if ($existing && !empty($existing['id'])) {
            $clienteId = (int) $existing['id'];
        } else {
            $cm = new Cliente();
            $documento = $telefono !== '' ? $telefono : 'CF';
            if (!$cm->create($nombre, $documento, $telefono, $email, $direccion, $sexo)) {
                return null;
            }
            $clienteId = (int) $this->db->lastInsertId();
        }

        $co = new ClienteOnline();
        $co->vincularClienteInterno($clienteOnlineId, $clienteId);
        return $clienteId;
    }

    private function obtenerClienteOnline(int $clienteOnlineId): ?array {
        $s = $this->db->prepare("SELECT * FROM clientes_online WHERE id = ?");
        $s->bind_param('i', $clienteOnlineId);
        $s->execute();
        return $s->get_result()->fetch_assoc() ?: null;
    }

    private function registrarHistorial(int $pedidoId, ?string $ant, string $nuevo, ?int $uid, string $nota, $conn = null): void {
        $db = $conn ?? $this->db->getConnection();
        if ($uid === null) {
            $s = $db->prepare(
                "INSERT INTO pedido_historial (pedido_id,estado_ant,estado_new,usuario_id,nota)
                 VALUES (?,?,?,NULL,?)"
            );
            $s->bind_param("isss", $pedidoId, $ant, $nuevo, $nota);
        } else {
            $s = $db->prepare(
                "INSERT INTO pedido_historial (pedido_id,estado_ant,estado_new,usuario_id,nota)
                 VALUES (?,?,?,?,?)"
            );
            $s->bind_param("issis", $pedidoId, $ant, $nuevo, $uid, $nota);
        }
        $s->execute();
    }

    private function notificarNuevoPedidoOnline(int $pedidoId, string $numeroPedido, int $clienteOnlineId, array $datosEnvio, array $carritoContenido, float $total): void {
        try {
            if (!(defined('TELEGRAM_REPORT_ENABLED') ? (bool) TELEGRAM_REPORT_ENABLED : true)) {
                return;
            }

            require_once __DIR__ . '/../TelegramBot.php';
            $bot = new TelegramBot();
            if (!$bot->configured()) {
                return;
            }

            $cliente = $this->obtenerClienteOnline($clienteOnlineId) ?: [];
            $nombre = trim((string) ($datosEnvio['nombre'] ?? ($cliente['nombre'] ?? 'Cliente')));
            $telefono = trim((string) ($datosEnvio['telefono'] ?? ($cliente['telefono'] ?? '')));
            $ciudad = trim((string) ($datosEnvio['ciudad'] ?? ($cliente['ciudad'] ?? '')));
            $direccion = trim((string) ($datosEnvio['direccion'] ?? ($cliente['direccion'] ?? '')));
            $metodo = trim((string) ($datosEnvio['metodo_pago'] ?? 'transferencia'));
            $tipoEntrega = trim((string) ($datosEnvio['tipo_entrega'] ?? 'domicilio'));
            $items = $carritoContenido['items'] ?? [];

            $lineas = [];
            foreach (array_slice($items, 0, 5) as $item) {
                $producto = trim((string) ($item['nombre'] ?? $item['producto_nombre'] ?? 'Producto'));
                $cantidad = (int) ($item['cantidad'] ?? 0);
                $talla = trim((string) ($item['talla'] ?? ''));
                $texto = '- ' . $producto . ' x' . $cantidad;
                if ($talla !== '') {
                    $texto .= ' (Talla ' . $talla . ')';
                }
                $lineas[] = $texto;
            }

            $mensaje = [];
            $mensaje[] = 'Nuevo pedido online recibido';
            $mensaje[] = 'Pedido: ' . $numeroPedido;
            $mensaje[] = 'Cliente: ' . ($nombre !== '' ? $nombre : 'Sin nombre');
            if ($telefono !== '') {
                $mensaje[] = 'Teléfono: ' . $telefono;
            }
            if ($ciudad !== '') {
                $mensaje[] = 'Ciudad: ' . $ciudad;
            }
            $mensaje[] = 'Entrega: ' . ($tipoEntrega === 'recoge_tienda' ? 'Recoge en tienda' : 'Domicilio');
            $mensaje[] = 'Pago: ' . $metodo;
            $mensaje[] = 'Total: $' . number_format($total, 0, ',', '.');
            if ($direccion !== '') {
                $mensaje[] = 'Dirección: ' . $direccion;
            }
            if (!empty($lineas)) {
                $mensaje[] = '';
                $mensaje[] = 'Productos:';
                $mensaje = array_merge($mensaje, $lineas);
            }
            $mensaje[] = '';
            $mensaje[] = 'Revisar el panel de pedidos para continuar con el proceso.';

            $bot->sendMessage(implode("\n", $mensaje));
        } catch (Throwable $e) {
            error_log('Pedido::notificarNuevoPedidoOnline: ' . $e->getMessage());
        }
    }

    private function notificarNuevoPedidoOnlineHtml(int $pedidoId, string $numeroPedido, int $clienteOnlineId, array $datosEnvio, array $carritoContenido, float $total): void {
        try {
            if (!(defined('TELEGRAM_REPORT_ENABLED') ? (bool) TELEGRAM_REPORT_ENABLED : true)) {
                return;
            }

            require_once __DIR__ . '/../TelegramBot.php';
            $bot = new TelegramBot();
            if (!$bot->configured()) {
                return;
            }

            $cliente = $this->obtenerClienteOnline($clienteOnlineId) ?: [];
            $nombre = trim((string) ($datosEnvio['nombre'] ?? ($cliente['nombre'] ?? 'Cliente')));
            $telefono = trim((string) ($datosEnvio['telefono'] ?? ($cliente['telefono'] ?? '')));
            $ciudad = trim((string) ($datosEnvio['ciudad'] ?? ($cliente['ciudad'] ?? '')));
            $direccion = trim((string) ($datosEnvio['direccion'] ?? ($cliente['direccion'] ?? '')));
            $metodo = trim((string) ($datosEnvio['metodo_pago'] ?? 'transferencia'));
            $tipoEntrega = trim((string) ($datosEnvio['tipo_entrega'] ?? 'domicilio'));
            $items = $carritoContenido['items'] ?? [];

            $resumenItems = [];
            foreach (array_slice($items, 0, 5) as $item) {
                $producto = trim((string) ($item['nombre'] ?? $item['producto_nombre'] ?? 'Producto'));
                $cantidad = (int) ($item['cantidad'] ?? 0);
                $talla = trim((string) ($item['talla'] ?? ''));
                $texto = $producto . ' x' . $cantidad;
                if ($talla !== '') {
                    $texto .= ' (Talla ' . $talla . ')';
                }
                $resumenItems[] = htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            $mensaje = [];
            $mensaje[] = '<b>Nuevo pedido online recibido</b>';
            $mensaje[] = '<b>Pedido:</b> ' . htmlspecialchars($numeroPedido, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $mensaje[] = '<b>Cliente:</b> ' . htmlspecialchars($nombre !== '' ? $nombre : 'Sin nombre', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            if ($telefono !== '') {
                $mensaje[] = '<b>Teléfono:</b> ' . htmlspecialchars($telefono, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            if ($ciudad !== '') {
                $mensaje[] = '<b>Ciudad:</b> ' . htmlspecialchars($ciudad, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            $mensaje[] = '<b>Entrega:</b> ' . ($tipoEntrega === 'recoge_tienda' ? 'Recoge en tienda' : 'Domicilio');
            $mensaje[] = '<b>Pago:</b> ' . htmlspecialchars($metodo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $mensaje[] = '<b>Total:</b> $' . number_format($total, 0, ',', '.');
            if ($direccion !== '') {
                $mensaje[] = '<b>Dirección:</b> ' . htmlspecialchars($direccion, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            if (!empty($resumenItems)) {
                $mensaje[] = '';
                $mensaje[] = '<b>Productos:</b>';
                foreach ($resumenItems as $itemTexto) {
                    $mensaje[] = '• ' . $itemTexto;
                }
            }
            $mensaje[] = '';
            $mensaje[] = 'Revisar el panel de pedidos para continuar con el proceso.';

            $bot->sendMessage(implode("\n", $mensaje), 'HTML');
        } catch (Throwable $e) {
            error_log('Pedido::notificarNuevoPedidoOnlineHtml: ' . $e->getMessage());
        }
    }

    private function enviarNotificacionEmailAdmin(int $pedidoId): void {
        if (!defined('ADMIN_EMAIL') || empty(ADMIN_EMAIL)) return;
        $pedido = $this->getById($pedidoId);
        if (!$pedido) return;
        $detalle = $this->getDetalle($pedidoId);

        $lineas = [];
        foreach ($detalle as $item) {
            $lineas[] = "- {$item['producto_nombre']} (Talla: " . ($item['talla'] ?: '—') . ") x{$item['cantidad']} = $" . number_format($item['subtotal'],0,',','.');
        }

        $subject = "Nuevo pedido online: {$pedido['numero_pedido']}";
        $body = "Se ha creado un nuevo pedido online:\n\n";
        $body .= "Pedido: {$pedido['numero_pedido']}\n";
        $body .= "Cliente: {$pedido['envio_nombre']}\n";
        $body .= "Teléfono: {$pedido['envio_telefono']}\n";
        $body .= "Dirección: {$pedido['envio_direccion']}\n";
        $body .= "Método de pago: {$pedido['metodo_pago']}\n";
        $body .= "Total: $" . number_format($pedido['total'],0,',','.') . "\n\n";
        $body .= "Productos:\n" . implode("\n", $lineas) . "\n\n";
        $body .= "Ver pedido en panel: " . rtrim(BASE_URL, '/') . "/views/pedidos_online.php\n";

        $headers = "From: " . (defined('APP_NAME') ? APP_NAME : 'Visión Real') . " <no-reply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">\r\n";
        @mail(ADMIN_EMAIL, $subject, $body, $headers);
    }
}
