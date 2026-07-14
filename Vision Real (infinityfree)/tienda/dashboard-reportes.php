<?php
/**
 * tienda/dashboard-reportes.php
 * 
 * Panel de control de reportes automáticos
 * Accesible desde cualquier dispositivo (web, móvil, tablet)
 * Muestra en tiempo real:
 * - Estado del inventario
 * - Pedidos pendientes
 * - Historial de reportes
 * 
 * Acceso: Requiere estar logueado como administrador
 * URL: https://visionreal.gt.tc/tienda/dashboard-reportes.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/tienda/Pedido.php';

// Validar acceso (requiere admin)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/tienda/login.php');
    exit;
}

// Obtener datos en tiempo real
$productoModel = new Producto();
$pedidoModel = new Pedido();

// 1. Productos con stock bajo
$productosStockBajo = $productoModel->getLowStock(5);
$cantidadStockBajo = count($productosStockBajo);

// 2. Pedidos por estado
$pedidosPorEstado = $pedidoModel->countPorEstado();
$pedidosPendientes = $pedidoModel->getAllAdmin('pendiente', 10);
$totalPedidos = $pedidoModel->countAllAdmin();

// 3. Obtener auditoría de reportes si existe la tabla
$auditoriaReportes = [];
$resultado = $GLOBALS['db']->query(
    "SELECT * FROM cron_reporte_auditorias ORDER BY creado_at DESC LIMIT 10"
);
if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $auditoriaReportes[] = $row;
    }
}

// Determinar estado general del sistema
$estadoSistema = 'normal';
if ($cantidadStockBajo > 5) $estadoSistema = 'advertencia';
if ($cantidadStockBajo > 10) $estadoSistema = 'critico';
if (($pedidosPorEstado['pendiente'] ?? 0) > 5) $estadoSistema = 'advertencia';

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Reportes | Visión Real</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h1 {
            font-size: 28px;
            color: #667eea;
        }
        
        .header .timestamp {
            font-size: 12px;
            color: #999;
            text-align: right;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 13px;
        }
        
        .status-badge.normal {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.advertencia {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.critico {
            background: #f8d7da;
            color: #721c24;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        
        .card-title {
            font-size: 16px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-title::before {
            font-size: 20px;
        }
        
        .card-stat {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .card-subtitle {
            font-size: 12px;
            color: #999;
        }
        
        .alert-list {
            list-style: none;
        }
        
        .alert-item {
            padding: 12px;
            margin-bottom: 8px;
            background: #f9f9f9;
            border-left: 4px solid #667eea;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .alert-item.critico {
            background: #ffebee;
            border-left-color: #d32f2f;
        }
        
        .alert-item.advertencia {
            background: #fffde7;
            border-left-color: #fbc02d;
        }
        
        .alert-item strong {
            display: block;
            margin-bottom: 4px;
        }
        
        .btn-refresh {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        
        .btn-refresh:hover {
            background: #764ba2;
        }
        
        .btn-refresh:active {
            transform: scale(0.98);
        }
        
        .pedidos-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .pedido-item {
            padding: 12px;
            margin-bottom: 8px;
            background: #f9f9f9;
            border-radius: 6px;
            border-left: 4px solid #667eea;
            font-size: 13px;
        }
        
        .pedido-numero {
            font-weight: bold;
            color: #667eea;
        }
        
        .pedido-detalles {
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
            font-size: 12px;
            color: #666;
        }
        
        .tabla-auditoria {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .tabla-auditoria th {
            background: #f5f5f5;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }
        
        .tabla-auditoria td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .tabla-auditoria tr:hover {
            background: #f9f9f9;
        }
        
        .badge-tipo {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-exitoso {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .badge-error {
            background: #ffcdd2;
            color: #c62828;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header .timestamp {
                text-align: left;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 22px;
            }
            
            .tabla-auditoria {
                font-size: 11px;
            }
            
            .tabla-auditoria th,
            .tabla-auditoria td {
                padding: 8px 5px;
            }
        }
        
        .spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div>
                <h1>📊 Dashboard de Reportes</h1>
                <div class="timestamp">
                    Última actualización: <span id="timestamp"><?php echo date('d/m/Y H:i:s'); ?></span>
                </div>
            </div>
            <div>
                <span class="status-badge <?php echo $estadoSistema; ?>">
                    Estado: <?php echo ucfirst($estadoSistema); ?>
                </span>
                <button class="btn-refresh" onclick="location.reload()">🔄 Actualizar</button>
            </div>
        </div>
        
        <!-- GRID DE ESTADÍSTICAS -->
        <div class="grid">
            <!-- Card: Productos con Stock Bajo -->
            <div class="card">
                <div class="card-title">⚠️ Stock Bajo</div>
                <div class="card-stat"><?php echo $cantidadStockBajo; ?></div>
                <div class="card-subtitle">Productos con ≤5 unidades</div>
                <hr style="margin: 15px 0; border: none; border-top: 1px solid #eee;">
                
                <?php if (!empty($productosStockBajo)): ?>
                    <ul class="alert-list">
                        <?php foreach (array_slice($productosStockBajo, 0, 5) as $prod): ?>
                            <li class="alert-item <?php echo ($prod['stock'] <= 2) ? 'critico' : 'advertencia'; ?>">
                                <strong><?php echo htmlspecialchars($prod['nombre']); ?></strong>
                                Stock: <strong><?php echo (int)$prod['stock']; ?></strong> | 
                                <?php echo htmlspecialchars($prod['categoria_nombre'] ?? 'N/A'); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($productosStockBajo) > 5): ?>
                        <div style="text-align: center; margin-top: 10px; font-size: 12px; color: #999;">
                            + <?php echo count($productosStockBajo) - 5; ?> más
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="color: #4caf50; font-weight: bold;">✓ Todo en nivel saludable</div>
                <?php endif; ?>
            </div>
            
            <!-- Card: Pedidos por Estado -->
            <div class="card">
                <div class="card-title">📦 Pedidos por Estado</div>
                <div style="font-size: 14px; line-height: 1.8;">
                    <div><strong>Pendientes:</strong> <span style="color: #d32f2f; font-weight: bold;"><?php echo $pedidosPorEstado['pendiente'] ?? 0; ?></span></div>
                    <div><strong>Pagados:</strong> <span style="color: #fbc02d; font-weight: bold;"><?php echo $pedidosPorEstado['pagado'] ?? 0; ?></span></div>
                    <div><strong>Preparando:</strong> <span style="color: #1976d2; font-weight: bold;"><?php echo $pedidosPorEstado['preparando'] ?? 0; ?></span></div>
                    <div><strong>Enviados:</strong> <span style="color: #388e3c; font-weight: bold;"><?php echo $pedidosPorEstado['enviado'] ?? 0; ?></span></div>
                    <div><strong>Entregados:</strong> <span style="color: #4caf50; font-weight: bold;"><?php echo $pedidosPorEstado['entregado'] ?? 0; ?></span></div>
                    <div><strong>Cancelados:</strong> <span style="color: #999; font-weight: bold;"><?php echo $pedidosPorEstado['cancelado'] ?? 0; ?></span></div>
                </div>
            </div>
            
            <!-- Card: Ingresos Hoy -->
            <div class="card">
                <div class="card-title">💰 Ingresos Hoy</div>
                <div class="card-stat">$<?php echo number_format($pedidoModel->getTotalOnlineHoy(), 0, ',', '.'); ?></div>
                <div class="card-subtitle">Pedidos confirmados y entregados</div>
                <hr style="margin: 15px 0; border: none; border-top: 1px solid #eee;">
                <div style="font-size: 12px; color: #666;">
                    <div>Total de transacciones: <?php echo number_format($totalPedidos, 0, ',', '.'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- PEDIDOS PENDIENTES -->
        <div class="card full-width">
            <div class="card-title">⏳ Pedidos Pendientes (Últimos 10)</div>
            <?php if (!empty($pedidosPendientes)): ?>
                <div class="pedidos-list">
                    <?php foreach ($pedidosPendientes as $pedido): ?>
                        <div class="pedido-item">
                            <div>
                                <span class="pedido-numero"><?php echo htmlspecialchars($pedido['numero_pedido'] ?? 'N/A'); ?></span>
                                — <?php echo htmlspecialchars($pedido['cliente_nombre'] ?? 'Cliente'); ?>
                            </div>
                            <div class="pedido-detalles">
                                <span>Total: $<?php echo number_format($pedido['total'] ?? 0, 0, ',', '.'); ?></span>
                                <span>Hace: <?php 
                                    $creado = new DateTime($pedido['creado_at']);
                                    $ahora = new DateTime();
                                    $diff = $ahora->diff($creado);
                                    if ($diff->d > 0) echo $diff->d . ' días';
                                    elseif ($diff->h > 0) echo $diff->h . ' horas';
                                    else echo $diff->i . ' minutos';
                                ?></span>
                                <span>Método: <?php echo htmlspecialchars($pedido['metodo_pago'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="color: #4caf50; font-weight: bold; text-align: center; padding: 20px;">
                    ✓ No hay pedidos pendientes
                </div>
            <?php endif; ?>
        </div>
        
        <!-- AUDITORÍA DE REPORTES -->
        <div class="card full-width">
            <div class="card-title">📋 Historial de Reportes Automáticos (Últimos 10)</div>
            <?php if (!empty($auditoriaReportes)): ?>
                <div style="overflow-x: auto;">
                    <table class="tabla-auditoria">
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Tipo de Verificación</th>
                                <th>Resultado</th>
                                <th>Duración</th>
                                <th>Notificación</th>
                                <th>IP Origen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditoriaReportes as $registro): ?>
                                <tr>
                                    <td><?php echo date('d/m H:i', strtotime($registro['creado_at'])); ?></td>
                                    <td>
                                        <span class="badge-tipo">
                                            <?php 
                                            $tipos = [
                                                'stock_bajo' => '📦 Stock',
                                                'pedidos_pendientes' => '⏳ Pedidos',
                                                'pedidos_revision' => '🔍 Revisión'
                                            ];
                                            echo $tipos[$registro['tipo_verificacion']] ?? $registro['tipo_verificacion'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-tipo <?php echo strpos($registro['resultado'], '"estado":"exitoso"') !== false ? 'badge-exitoso' : 'badge-error'; ?>">
                                            <?php echo strpos($registro['resultado'], '"estado":"exitoso"') !== false ? '✓ Exitoso' : '✗ Error'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $registro['duracion_ms']; ?>ms</td>
                                    <td><?php echo $registro['notificacion_enviada'] ? '✓' : '—'; ?></td>
                                    <td style="font-size: 11px;"><?php echo htmlspecialchars($registro['ip_origen']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="color: #999; text-align: center; padding: 20px;">
                    No hay registros de auditoría aún. El cron job se ejecutará cada 10 minutos.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- INSTRUCCIONES DE CONFIGURACIÓN -->
        <div class="card full-width" style="background: #f0f7ff; border-left: 4px solid #1976d2;">
            <div class="card-title">⚙️ Configuración del Cron Job</div>
            <div style="font-size: 13px; line-height: 1.8; color: #333;">
                <p style="margin-bottom: 10px;">
                    <strong>Para activar reportes automáticos cada 10 minutos:</strong>
                </p>
                <ol style="margin-left: 20px; margin-bottom: 15px;">
                    <li>Ve a <a href="https://www.easycron.com" target="_blank">EasyCron.com</a> o <a href="https://cron-job.org" target="_blank">Cron-job.org</a></li>
                    <li>Crea una nueva tarea programada</li>
                    <li>URL: <code style="background: white; padding: 5px 10px; border-radius: 4px;"><?php echo BASE_URL; ?>/api/cron-reporter.php?token=<?php echo defined('CRON_SECRET_TOKEN') ? CRON_SECRET_TOKEN : 'change-me'; ?></code></li>
                    <li>Intervalo: <strong>Cada 10 minutos</strong></li>
                    <li>Guarda y activa</li>
                </ol>
                <p style="font-size: 12px; color: #666;">
                    ⚠️ <strong>IMPORTANTE:</strong> Cambia el token <code>change-me</code> en <code>config/config.php</code> para mayor seguridad.
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-actualizar cada 30 segundos
        setInterval(() => {
            const now = new Date();
            document.getElementById('timestamp').textContent = 
                now.toLocaleDateString('es-ES') + ' ' + 
                now.toLocaleTimeString('es-ES');
        }, 1000);
        
        // Recargar datos cada 5 minutos automáticamente
        setTimeout(() => {
            location.reload();
        }, 5 * 60 * 1000);
    </script>
</body>
</html>
