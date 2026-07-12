<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Producto.php';
require_once __DIR__ . '/Venta.php';
require_once __DIR__ . '/Cliente.php';
require_once __DIR__ . '/tienda/Pedido.php';
require_once __DIR__ . '/tienda/ProductoTienda.php';

class ReporteIAData {
    private Database $db;
    private Producto $productoModel;
    private Venta $ventaModel;
    private Cliente $clienteModel;
    private Pedido $pedidoModel;
    private ProductoTienda $productoTiendaModel;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->productoModel = new Producto();
        $this->ventaModel = new Venta();
        $this->clienteModel = new Cliente();
        $this->pedidoModel = new Pedido();
        $this->productoTiendaModel = new ProductoTienda();
    }

    public function collect(): array {
        $stockLimit = defined('TELEGRAM_LOW_STOCK_THRESHOLD') ? (int) TELEGRAM_LOW_STOCK_THRESHOLD : 5;
        $lowStock = $this->productoModel->getLowStock($stockLimit);
        $outOfStock = $this->productoModel->getLowStock(0);
        $topProducts = $this->ventaModel->getTopProductos(10);
        $ventasPorDia = $this->ventaModel->getVentasPorDia(7);
        $pedidosPorEstado = $this->pedidoModel->countPorEstado();
        $pedidoStats = $this->getPedidoStats();
        $salesSummary = $this->getSalesSummary();
        $ventasPorTipo = $this->productoTiendaModel->statsVentasPorTipo();

        return [
            'meta' => [
                'fecha' => date('Y-m-d H:i:s'),
                'zona_horaria' => date_default_timezone_get(),
                'moneda' => 'COP',
            ],
            'empresa' => [
                'productos_totales' => (int) $this->productoModel->countAll(),
                'productos_stock_bajo' => count($lowStock),
                'productos_agotados' => count($outOfStock),
                'clientes_totales' => (int) $this->clienteModel->countAll(),
                'ventas_totales' => (int) $salesSummary['ventas_totales'],
                'ventas_hoy' => (int) $salesSummary['ventas_hoy'],
                'ventas_mes' => (int) $salesSummary['ventas_mes'],
                'ingresos_totales' => (float) $salesSummary['ingresos_totales'],
                'ingresos_hoy' => (float) $salesSummary['ingresos_hoy'],
                'ingresos_mes' => (float) $salesSummary['ingresos_mes'],
                'ticket_promedio' => (float) $salesSummary['ticket_promedio'],
            ],
            'ventas' => [
                'hoy' => (float) $this->ventaModel->getTotalHoy(),
                'mes' => (float) $this->ventaModel->getTotalMes(),
                'por_dia' => $ventasPorDia,
                'top_productos' => $topProducts,
                'por_tipo' => $ventasPorTipo,
            ],
            'inventario' => [
                'limite_stock_bajo' => $stockLimit,
                'stock_bajo' => array_slice($lowStock, 0, 10),
                'agotados' => array_slice($outOfStock, 0, 10),
                'rotacion' => array_slice($topProducts, 0, 10),
            ],
            'pedidos' => [
                'por_estado' => $pedidosPorEstado,
                'pendientes_total' => (int) ($pedidosPorEstado['pendiente'] ?? 0),
                'entregados_total' => (int) ($pedidosPorEstado['entregado'] ?? 0),
                'pendientes' => $pedidoStats['pendientes'],
                'entregados_recientes' => $pedidoStats['entregados_recientes'],
            ],
        ];
    }

    private function getSalesSummary(): array {
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN estado='completada' THEN total ELSE 0 END), 0) AS ingresos_totales,
                    COALESCE(SUM(CASE WHEN estado='completada' AND DATE(fecha)=CURDATE() THEN total ELSE 0 END), 0) AS ingresos_hoy,
                    COALESCE(SUM(CASE WHEN estado='completada' AND MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW()) THEN total ELSE 0 END), 0) AS ingresos_mes,
                    COALESCE(COUNT(CASE WHEN estado='completada' THEN 1 END), 0) AS ventas_totales,
                    COALESCE(COUNT(CASE WHEN estado='completada' AND DATE(fecha)=CURDATE() THEN 1 END), 0) AS ventas_hoy,
                    COALESCE(COUNT(CASE WHEN estado='completada' AND MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW()) THEN 1 END), 0) AS ventas_mes,
                    COALESCE(AVG(CASE WHEN estado='completada' THEN total END), 0) AS ticket_promedio
                FROM ventas";
        $result = $this->db->query($sql);
        $row = $result ? $result->fetch_assoc() : [];
        return [
            'ingresos_totales' => (float) ($row['ingresos_totales'] ?? 0),
            'ingresos_hoy' => (float) ($row['ingresos_hoy'] ?? 0),
            'ingresos_mes' => (float) ($row['ingresos_mes'] ?? 0),
            'ventas_totales' => (int) ($row['ventas_totales'] ?? 0),
            'ventas_hoy' => (int) ($row['ventas_hoy'] ?? 0),
            'ventas_mes' => (int) ($row['ventas_mes'] ?? 0),
            'ticket_promedio' => (float) ($row['ticket_promedio'] ?? 0),
        ];
    }

    private function getPedidoStats(): array {
        $pendientes = $this->fetchAll(
            "SELECT p.*, COALESCE(co.nombre, p.envio_nombre) AS cliente_nombre
             FROM pedidos p
             LEFT JOIN clientes_online co ON p.cliente_online_id = co.id
             WHERE p.estado = 'pendiente'
             ORDER BY p.creado_at DESC
             LIMIT 10"
        );

        $entregados = $this->fetchAll(
            "SELECT p.*, COALESCE(co.nombre, p.envio_nombre) AS cliente_nombre
             FROM pedidos p
             LEFT JOIN clientes_online co ON p.cliente_online_id = co.id
             WHERE p.estado = 'entregado'
             ORDER BY p.actualizado_at DESC
             LIMIT 10"
        );

        return [
            'pendientes' => $pendientes,
            'entregados_recientes' => $entregados,
        ];
    }

    private function fetchAll(string $sql): array {
        $result = $this->db->query($sql);
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }
}
?>
