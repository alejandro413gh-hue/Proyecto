<?php
/**
 * api/tienda/descuentos.php
 * Calcula descuentos aplicables al carrito del cliente online.
 * Reutiliza 100% el modelo Descuento.php existente.
 * No requiere login (para mostrar descuentos en el checkout).
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../tienda/includes/session_tienda.php';
require_once __DIR__ . '/../../models/Descuento.php';
require_once __DIR__ . '/../../models/tienda/Carrito.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'calcular';

switch ($action) {

    case 'calcular':
        // Calcular el mejor descuento para el carrito actual
        $sexo      = $_POST['sexo'] ?? 'todos';
        $clienteId = null;

        if (tiendaLoggedIn()) {
            $cli       = getTiendaCliente();
            $sexoMap   = ['M' => 'caballero', 'F' => 'dama', 'O' => 'todos'];
            $sexo      = $sexoMap[$cli['sexo']] ?? 'todos';

            // Cliente interno vinculado (para contar compras previas)
            require_once __DIR__ . '/../../models/tienda/ClienteOnline.php';
            $com    = new ClienteOnline();
            $cliDatos = $com->getById($cli['id']);
            $clienteId = $cliDatos['cliente_id'] ?? null;

            // Obtener items del carrito
            $carritoM  = new Carrito();
            $contenido = $carritoM->getContenido($cli['id']);
            $items     = array_map(fn($i) => [
                'producto_id'  => $i['producto_id'],
                'categoria_id' => null, // Se podría obtener del JOIN pero no es crítico
                'precio'       => (float)$i['precio_unitario'],
                'cantidad'     => (int)$i['cantidad'],
            ], $contenido['items']);
        } else {
            // Sin login, recibir items desde POST
            $items = json_decode($_POST['items'] ?? '[]', true);
        }

        if (empty($items)) {
            echo json_encode(['success' => true, 'descuento' => null]);
            break;
        }

        $dm      = new Descuento();
        $mejor   = $dm->calcularMejor($items, $clienteId, $sexo);
        $activos = $dm->getActivos();

        echo json_encode([
            'success'         => true,
            'mejor_descuento' => $mejor,
            'descuentos'      => array_map(fn($d) => [
                'id'          => $d['id'],
                'nombre'      => $d['nombre'],
                'descripcion' => $d['descripcion'],
                'tipo'        => $d['tipo_descuento'],
                'valor'       => (float)$d['valor'],
                'genero'      => $d['aplica_genero'],
            ], $activos),
        ]);
        break;

    case 'listar':
        // Listar descuentos activos para mostrar en banner/promociones
        $dm = new Descuento();
        echo json_encode(['success' => true, 'descuentos' => $dm->getActivos()]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
}
