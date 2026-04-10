<?php
// tienda/api.php — API pública de la tienda
// No requiere login (es para clientes externos)
require_once __DIR__ . '/../config/database.php';

// Iniciar sesión para usar el usuario "vendedor_tienda" virtualmente
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$db = Database::getInstance();

// =============================================
// GET: buscar cliente por email
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'tallas_producto') {
        // Devuelve tallas disponibles de un producto (sin login)
        $prod_id = intval($_GET['producto_id'] ?? 0);
        if (!$prod_id) { echo json_encode([]); exit(); }
        $stmt = $db->prepare(
            "SELECT talla, stock FROM producto_tallas WHERE producto_id = ? AND stock > 0 
             ORDER BY FIELD(talla,'XS','S','M','L','XL','XXL','XXXL','Único') DESC, CAST(talla AS UNSIGNED) ASC, talla ASC"
        );
        $stmt->bind_param("i", $prod_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $tallas = [];
        while($row = $r->fetch_assoc()) $tallas[] = $row;
        echo json_encode($tallas);
        exit();
    }
    if ($_GET['action'] === 'calcular_descuento') {
        // Calcula el mejor descuento para items + cliente + genero
        require_once __DIR__ . '/../models/Descuento.php';
        $dm = new Descuento();
        $items      = json_decode($_GET['items'] ?? '[]', true);
        $cliente_id = intval($_GET['cliente_id'] ?? 0) ?: null;
        $genero     = trim($_GET['genero'] ?? 'todos');
        $mejor = $dm->calcularMejor($items, $cliente_id, $genero);
        echo json_encode(['descuento' => $mejor]);
        exit();
    }
    if ($_GET['action'] === 'buscar_cliente') {
        $email = trim($_GET['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['id' => null]);
            exit();
        }
        $stmt = $db->prepare("SELECT id, nombre, telefono, email FROM clientes WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();
        echo json_encode($cliente ?: ['id' => null]);
        exit();
    }
}

// =============================================
// POST: procesar compra pública
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'compra_publica') {

    $nombre      = trim($_POST['nombre'] ?? '');
    $telefono    = trim($_POST['telefono'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $direccion   = trim($_POST['direccion'] ?? '');
    $notas       = trim($_POST['notas'] ?? '');
    $promocion_id= intval($_POST['promocion_id'] ?? 0) ?: null;
    // NO confiar en el descuento enviado por el cliente — recalcular en el servidor
    $cliente_id  = intval($_POST['cliente_id'] ?? 0) ?: null;
    $productos   = json_decode($_POST['productos'] ?? '[]', true);

    // Validar
    if (empty($nombre)) { echo json_encode(['error' => 'El nombre es obligatorio']); exit(); }
    if (empty($productos)) { echo json_encode(['error' => 'No hay productos en el carrito']); exit(); }

    // Validar y re-verificar precios desde la base de datos (evitar manipulación del cliente)
    foreach ($productos as &$p) {
        if (!isset($p['producto_id'], $p['cantidad'])) {
            echo json_encode(['error' => 'Datos de productos inválidos']); exit();
        }
        if ($p['cantidad'] <= 0) {
            echo json_encode(['error' => 'Cantidad debe ser mayor a 0']); exit();
        }
        // Obtener precio real de la BD (no confiar en el precio enviado por el cliente)
        $sp = $db->prepare("SELECT precio, stock, activo FROM productos WHERE id = ? AND activo = 1");
        $sp->bind_param("i", $p['producto_id']);
        $sp->execute();
        $prod_real = $sp->get_result()->fetch_assoc();
        if (!$prod_real) {
            echo json_encode(['error' => 'Producto no encontrado o no disponible']); exit();
        }
        $p['precio'] = (float)$prod_real['precio']; // Usar precio real de BD
    }
    unset($p);

    $conn = $db->getConnection();
    $conn->begin_transaction();

    try {
        // 1. Crear o encontrar cliente
        if (!$cliente_id) {
            // Buscar por email si lo proporcionó
            if (!empty($email)) {
                $s = $conn->prepare("SELECT id FROM clientes WHERE email = ? LIMIT 1");
                $s->bind_param("s", $email);
                $s->execute();
                $found = $s->get_result()->fetch_assoc();
                if ($found) $cliente_id = $found['id'];
            }
            // Crear cliente nuevo si no existe
            if (!$cliente_id) {
                $s = $conn->prepare("INSERT INTO clientes (nombre, telefono, email, direccion) VALUES (?,?,?,?)");
                $s->bind_param("ssss", $nombre, $telefono, $email, $direccion);
                $s->execute();
                $cliente_id = $conn->insert_id;
            }
        }

        // 2. Calcular total (precios ya fueron verificados desde BD)
        $subtotal = array_sum(array_map(fn($p) => $p['precio'] * $p['cantidad'], $productos));
        // Recalcular el descuento en el servidor (no usar el valor del cliente)
        $descuento = 0;
        if ($promocion_id) {
            require_once __DIR__ . '/../models/Descuento.php';
            $dm = new Descuento();
            $items_calc = array_map(fn($p) => [
                'producto_id'  => $p['producto_id'],
                'categoria_id' => 0, // Se puede enriquecer si se necesita
                'precio'       => $p['precio'],
                'cantidad'     => $p['cantidad'],
            ], $productos);
            $mejor = $dm->calcularMejor($items_calc, $cliente_id);
            if ($mejor) $descuento = $mejor['monto'];
        }
        $total    = max(0, $subtotal - $descuento);

        // 3. Obtener usuario vendedor del sistema (usar el primer vendedor activo o admin)
        $uRes = $conn->query("SELECT id FROM usuarios WHERE activo=1 ORDER BY id ASC LIMIT 1");
        $uRow = $uRes->fetch_assoc();
        $usuario_id = $uRow ? $uRow['id'] : 1;

        // Notas con indicador de compra online
        $notasFinal = '[Compra Online]' . ($notas ? ' ' . $notas : '');

        // 4. Crear venta
        $s = $conn->prepare(
            "INSERT INTO ventas (cliente_id, usuario_id, total, notas, promocion_id, descuento, total_sin_descuento)
             VALUES (?,?,?,?,?,?,?)"
        );
        $s->bind_param("iidsidd", $cliente_id, $usuario_id, $total, $notasFinal, $promocion_id, $descuento, $subtotal);
        $s->execute();
        $venta_id = $conn->insert_id;

        // Verificar columna talla UNA SOLA VEZ antes del loop
        $ck = $conn->query("SHOW COLUMNS FROM detalle_venta LIKE 'talla'");
        if ($ck->num_rows === 0) $conn->query("ALTER TABLE detalle_venta ADD COLUMN talla VARCHAR(20) NULL AFTER cantidad");

        // 5. Insertar detalle y descontar stock
        foreach ($productos as $p) {
            $sub          = $p['precio'] * $p['cantidad'];
            $talla_venta  = isset($p['talla']) && $p['talla'] !== '' ? $p['talla'] : null;

            $s2 = $conn->prepare(
                "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, talla, precio_unitario, subtotal) VALUES (?,?,?,?,?,?)"
            );
            $s2->bind_param("iiisdd", $venta_id, $p['producto_id'], $p['cantidad'], $talla_venta, $p['precio'], $sub);
            $s2->execute();

            // Descontar stock de talla si aplica
            if ($talla_venta) {
                $st = $conn->prepare("UPDATE producto_tallas SET stock = stock - ? WHERE producto_id = ? AND talla = ? AND stock >= ?");
                $st->bind_param("iisi", $p['cantidad'], $p['producto_id'], $talla_venta, $p['cantidad']);
                $st->execute();
            }

            // Descontar stock general
            $s3 = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $s3->bind_param("iii", $p['cantidad'], $p['producto_id'], $p['cantidad']);
            $s3->execute();

            if ($conn->affected_rows === 0) {
                throw new Exception("Stock insuficiente para un producto. Refresca la página e intenta de nuevo.");
            }
        }

        $conn->commit();
        echo json_encode([
            'success'  => true,
            'venta_id' => $venta_id,
            'total'    => $total,
            'descuento'=> $descuento,
            'cliente_id'=> $cliente_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['error' => 'Acción no válida']);
?>
