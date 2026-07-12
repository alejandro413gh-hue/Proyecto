<?php
/**
 * api/cron-reporter.php
 * 
 * Script automático que se ejecuta cada 5-15 minutos para:
 * 1. Verificar productos con stock bajo
 * 2. Verificar pedidos pendientes nuevos
 * 3. Verificar pedidos que requieren revisión
 * 4. Enviar notificaciones vía Telegram/WhatsApp
 * 5. Registrar auditoría de reportes
 * 
 * Uso:
 * - Llamar desde servicio externo (EasyCron, Cron-job.org, etc)
 * - URL: https://visionreal.gt.tc/api/cron-reporter.php
 * - Intervalo recomendado: cada 10 minutos
 * 
 * Seguridad:
 * - Requiere secret token en query parameter
 * - Registra IP de origen
 * - Log de todas las ejecuciones
 */

// ============================================
// 1. INICIALIZACIÓN Y VALIDACIÓN DE SEGURIDAD
// ============================================

define('SCRIPT_START_TIME', microtime(true));
define('SCRIPT_TIMEOUT', 30); // segundos máximo

// Validar que NO se ejecuta desde CLI (evitar conflictos)
if (php_sapi_name() === 'cli') {
    die("Este script debe ejecutarse vía HTTP, no desde CLI.\n");
}

// Headers para evitar cachés
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Requerir archivos de configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/tienda/Pedido.php';
require_once __DIR__ . '/../models/TelegramBot.php';

// ============================================
// 2. VALIDACIÓN DE SEGURIDAD
// ============================================

$secretToken = $_GET['token'] ?? $_POST['token'] ?? '';
$expectedToken = defined('CRON_SECRET_TOKEN') ? CRON_SECRET_TOKEN : 'change-me-in-config';

// ⚠️ IMPORTANTE: Cambiar en config/config.php antes de usar en producción
if (empty($secretToken) || $secretToken !== $expectedToken) {
    http_response_code(403);
    die(json_encode([
        'success' => false,
        'error' => 'Token inválido o ausente',
        'timestamp' => date('Y-m-d H:i:s')
    ]));
}

// ============================================
// 3. INICIALIZAR TABLA DE AUDITORÍA
// ============================================

class CronReporter {
    private $db;
    private $botTelegram;
    private $logResults = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->botTelegram = new TelegramBot();
        $this->initAuditTable();
    }
    
    private function initAuditTable(): void {
        // Crear tabla de auditoría si no existe
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS cron_reporte_auditorias (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tipo_verificacion VARCHAR(100) NOT NULL,
                resultado JSON NOT NULL,
                notificacion_enviada BOOLEAN DEFAULT FALSE,
                ip_origen VARCHAR(45),
                user_agent TEXT,
                duracion_ms INT,
                creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
    
    // =========================================
    // MÉTODO 1: Verificar Stock Bajo
    // =========================================
    public function verificarStockBajo(int $threshold = 5): array {
        $startTime = microtime(true);
        
        try {
            $productoModel = new Producto();
            $productosConStockBajo = $productoModel->getLowStock($threshold);
            
            $resultado = [
                'estado' => 'exitoso',
                'cantidad_productos' => count($productosConStockBajo),
                'threshold' => $threshold,
                'productos' => []
            ];
            
            // Compilar información de productos críticos
            foreach (array_slice($productosConStockBajo, 0, 10) as $producto) {
                $resultado['productos'][] = [
                    'id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'codigo' => $producto['codigo'] ?? 'N/A',
                    'stock_actual' => (int)($producto['stock'] ?? 0),
                    'categoria' => $producto['categoria_nombre'] ?? 'Sin categoría'
                ];
            }
            
            // Si hay más de 10, indicar cantidad adicional
            if (count($productosConStockBajo) > 10) {
                $resultado['productos_adicionales'] = count($productosConStockBajo) - 10;
            }
            
            $resultado['timestamp'] = date('Y-m-d H:i:s');
            
        } catch (Exception $e) {
            $resultado = [
                'estado' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        $duracionMs = (int)((microtime(true) - $startTime) * 1000);
        $this->registrarAuditoria('stock_bajo', $resultado, $duracionMs);
        
        return $resultado;
    }
    
    // =========================================
    // MÉTODO 2: Verificar Pedidos Pendientes Nuevos
    // =========================================
    public function verificarPedidosPendientes(int $limit = 20): array {
        $startTime = microtime(true);
        
        try {
            $pedidoModel = new Pedido();
            $pedidosPendientes = $pedidoModel->getAllAdmin('pendiente', $limit);
            
            $resultado = [
                'estado' => 'exitoso',
                'cantidad_pendientes' => count($pedidosPendientes),
                'pedidos' => []
            ];
            
            // Compilar información de pedidos
            foreach ($pedidosPendientes as $pedido) {
                $resultado['pedidos'][] = [
                    'id' => $pedido['id'],
                    'numero_pedido' => $pedido['numero_pedido'] ?? 'N/A',
                    'cliente' => $pedido['cliente_nombre'] ?? $pedido['envio_nombre'] ?? 'Cliente anónimo',
                    'total' => (float)($pedido['total'] ?? 0),
                    'estado' => $pedido['estado'],
                    'creado_hace' => $this->tiempoTranscurrido($pedido['creado_at']),
                    'metodo_pago' => $pedido['metodo_pago'] ?? 'No especificado'
                ];
            }
            
            // Contar estados totales
            $conteos = $pedidoModel->countPorEstado();
            $resultado['resumen_estados'] = $conteos ?? [];
            $resultado['timestamp'] = date('Y-m-d H:i:s');
            
        } catch (Exception $e) {
            $resultado = [
                'estado' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        $duracionMs = (int)((microtime(true) - $startTime) * 1000);
        $this->registrarAuditoria('pedidos_pendientes', $resultado, $duracionMs);
        
        return $resultado;
    }
    
    // =========================================
    // MÉTODO 3: Verificar Pedidos que Requieren Revisión
    // =========================================
    public function verificarPedidosEnRevision(): array {
        $startTime = microtime(true);
        
        try {
            $pedidoModel = new Pedido();
            
            // Criterios de "requieren revisión":
            // 1. Pendientes hace más de 30 minutos sin ser pagados
            // 2. Pagados pero sin asignar a "preparando"
            // 3. Sin comprobante de pago pero marcados como pagados
            
            $query = "
                SELECT p.*, co.nombre as cliente_nombre, co.email as cliente_email
                FROM pedidos p
                LEFT JOIN clientes_online co ON p.cliente_online_id = co.id
                WHERE (
                    (p.estado = 'pendiente' AND TIMESTAMPDIFF(MINUTE, p.creado_at, NOW()) > 30)
                    OR (p.estado = 'pagado' AND p.comprobante_img IS NULL)
                    OR (p.estado = 'pagado' AND TIMESTAMPDIFF(HOUR, p.creado_at, NOW()) > 2)
                )
                ORDER BY p.creado_at ASC
                LIMIT 20
            ";
            
            $result = $this->db->query($query);
            $pedidosRevision = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $pedidosRevision[] = $row;
                }
            }
            
            $resultado = [
                'estado' => 'exitoso',
                'cantidad_revision' => count($pedidosRevision),
                'pedidos' => []
            ];
            
            foreach ($pedidosRevision as $pedido) {
                $razon = [];
                
                // Determinar razón de revisión
                if ($pedido['estado'] === 'pendiente') {
                    $razon[] = 'Pendiente > 30 min sin pago';
                }
                if ($pedido['estado'] === 'pagado' && empty($pedido['comprobante_img'])) {
                    $razon[] = 'Pagado sin comprobante';
                }
                if ($pedido['estado'] === 'pagado' && 
                    strtotime($pedido['creado_at']) < strtotime('-2 hours')) {
                    $razon[] = 'Pagado hace > 2 horas sin procesamiento';
                }
                
                $resultado['pedidos'][] = [
                    'id' => $pedido['id'],
                    'numero_pedido' => $pedido['numero_pedido'] ?? 'N/A',
                    'cliente' => $pedido['cliente_nombre'] ?? 'Cliente',
                    'email' => $pedido['cliente_email'] ?? 'N/A',
                    'estado' => $pedido['estado'],
                    'total' => (float)($pedido['total'] ?? 0),
                    'razones_revision' => $razon,
                    'creado_hace' => $this->tiempoTranscurrido($pedido['creado_at'])
                ];
            }
            
            $resultado['timestamp'] = date('Y-m-d H:i:s');
            
        } catch (Exception $e) {
            $resultado = [
                'estado' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        $duracionMs = (int)((microtime(true) - $startTime) * 1000);
        $this->registrarAuditoria('pedidos_revision', $resultado, $duracionMs);
        
        return $resultado;
    }
    
    // =========================================
    // MÉTODO 4: Ejecutar Todas las Verificaciones
    // =========================================
    public function ejecutarTodasVerificaciones(): array {
        $resultados = [
            'timestamp_inicio' => date('Y-m-d H:i:s'),
            'verificaciones' => []
        ];
        
        // 1. Verificar stock bajo
        $resultados['verificaciones']['stock_bajo'] = $this->verificarStockBajo(
            defined('TELEGRAM_LOW_STOCK_THRESHOLD') ? TELEGRAM_LOW_STOCK_THRESHOLD : 5
        );
        
        // 2. Verificar pedidos pendientes
        $resultados['verificaciones']['pedidos_pendientes'] = $this->verificarPedidosPendientes();
        
        // 3. Verificar pedidos en revisión
        $resultados['verificaciones']['pedidos_revision'] = $this->verificarPedidosEnRevision();
        
        $resultados['timestamp_fin'] = date('Y-m-d H:i:s');
        
        return $resultados;
    }
    
    // =========================================
    // MÉTODO 5: Enviar Reporte Consolidado por Telegram
    // =========================================
    public function enviarReportePorTelegram(array $verificaciones): array {
        if (!$this->botTelegram->configured()) {
            return ['success' => false, 'error' => 'Telegram no está configurado'];
        }
        
        // Construir mensaje consolidado
        $partes = [];
        
        // Stock bajo
        if (!empty($verificaciones['stock_bajo']) && 
            $verificaciones['stock_bajo']['cantidad_productos'] > 0) {
            $partes[] = $this->construirMensajeStockBajo($verificaciones['stock_bajo']);
        }
        
        // Pedidos pendientes
        if (!empty($verificaciones['pedidos_pendientes']) && 
            $verificaciones['pedidos_pendientes']['cantidad_pendientes'] > 0) {
            $partes[] = $this->construirMensajePedidosPendientes($verificaciones['pedidos_pendientes']);
        }
        
        // Pedidos en revisión
        if (!empty($verificaciones['pedidos_revision']) && 
            $verificaciones['pedidos_revision']['cantidad_revision'] > 0) {
            $partes[] = $this->construirMensajeRevision($verificaciones['pedidos_revision']);
        }
        
        // Si no hay nada importante, enviar reporte normal
        if (empty($partes)) {
            $partes[] = "✅ Visión Real | Reporte automático\n" . date('d/m/Y H:i') . "\n\n✓ Todo en orden";
        }
        
        $mensaje = implode("\n\n" . str_repeat("─", 30) . "\n\n", $partes);
        
        return $this->botTelegram->sendMessage($mensaje);
    }
    
    private function construirMensajeStockBajo(array $data): string {
        $lineas = [
            "⚠️ ALERTA: PRODUCTOS CON STOCK BAJO",
            "─────────────────────────",
            "Cantidad: " . $data['cantidad_productos'] . " productos (umbral: ≤ " . $data['threshold'] . " ud)",
            ""
        ];
        
        foreach (array_slice($data['productos'], 0, 5) as $prod) {
            $lineas[] = sprintf(
                "• %s | Stock: %d | %s",
                $prod['nombre'],
                $prod['stock_actual'],
                $prod['categoria']
            );
        }
        
        if (!empty($data['productos_adicionales'])) {
            $lineas[] = "• ... y " . $data['productos_adicionales'] . " más";
        }
        
        return implode("\n", $lineas);
    }
    
    private function construirMensajePedidosPendientes(array $data): string {
        $lineas = [
            "📦 PEDIDOS PENDIENTES",
            "─────────────────────────",
            "Cantidad: " . $data['cantidad_pendientes'] . " pedidos esperando pago",
            ""
        ];
        
        foreach (array_slice($data['pedidos'], 0, 5) as $ped) {
            $lineas[] = sprintf(
                "• %s | %s | $%s | %s",
                $ped['numero_pedido'],
                substr($ped['cliente'], 0, 15),
                number_format($ped['total'], 0, ',', '.'),
                $ped['creado_hace']
            );
        }
        
        return implode("\n", $lineas);
    }
    
    private function construirMensajeRevision(array $data): string {
        $lineas = [
            "🔍 PEDIDOS REQUIEREN REVISIÓN",
            "─────────────────────────",
            "Cantidad: " . $data['cantidad_revision'] . " pedidos",
            ""
        ];
        
        foreach (array_slice($data['pedidos'], 0, 5) as $ped) {
            $razon = !empty($ped['razones_revision']) ? $ped['razones_revision'][0] : 'Revisión general';
            $lineas[] = sprintf(
                "• %s | %s | %s",
                $ped['numero_pedido'],
                $ped['estado'],
                $razon
            );
        }
        
        return implode("\n", $lineas);
    }
    
    // =========================================
    // HELPER: Calcular tiempo transcurrido
    // =========================================
    private function tiempoTranscurrido(string $fecha): string {
        $ahora = new DateTime();
        $creado = new DateTime($fecha);
        $diff = $ahora->diff($creado);
        
        if ($diff->d > 0) return $diff->d . 'd atrás';
        if ($diff->h > 0) return $diff->h . 'h atrás';
        if ($diff->i > 0) return $diff->i . 'min atrás';
        return 'Hace poco';
    }
    
    // =========================================
    // MÉTODO: Registrar en auditoría
    // =========================================
    private function registrarAuditoria(
        string $tipo, 
        array $resultado, 
        int $duracionMs,
        bool $notificacionEnviada = false
    ): void {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO cron_reporte_auditorias 
                 (tipo_verificacion, resultado, notificacion_enviada, ip_origen, user_agent, duracion_ms)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            
            $ipOrigen = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
            $resultadoJson = json_encode($resultado);
            
            $stmt->bind_param(
                'ssissi',
                $tipo,
                $resultadoJson,
                $notificacionEnviada,
                $ipOrigen,
                $userAgent,
                $duracionMs
            );
            
            $stmt->execute();
        } catch (Exception $e) {
            error_log('Error registrando auditoría: ' . $e->getMessage());
        }
    }
    
    // =========================================
    // MÉTODO: Obtener auditoría reciente
    // =========================================
    public function obtenerAuditoriaReciente(int $limite = 20): array {
        $result = $this->db->query(
            "SELECT * FROM cron_reporte_auditorias 
             ORDER BY creado_at DESC LIMIT " . $limite
        );
        
        $registros = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row['resultado']) {
                    $row['resultado'] = json_decode($row['resultado'], true);
                }
                $registros[] = $row;
            }
        }
        
        return $registros;
    }
}

// ============================================
// 4. EJECUTAR REPORTER
// ============================================

try {
    $reporter = new CronReporter();
    
    // Ejecutar todas las verificaciones
    $verificaciones = $reporter->ejecutarTodasVerificaciones();
    
    // Enviar reporte por Telegram si hay novedades
    $telegramResult = $reporter->enviarReportePorTelegram($verificaciones['verificaciones']);
    
    // Respuesta final
    $response = [
        'success' => true,
        'mensaje' => 'Reporte ejecutado correctamente',
        'timestamp' => date('Y-m-d H:i:s'),
        'duracion_total_segundos' => round(microtime(true) - SCRIPT_START_TIME, 2),
        'verificaciones' => $verificaciones,
        'telegram' => $telegramResult,
        'ip_origen' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
    ];
    
    http_response_code(200);
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'trace' => DEBUG_MODE ? $e->getTraceAsString() : null
    ];
    
    http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
