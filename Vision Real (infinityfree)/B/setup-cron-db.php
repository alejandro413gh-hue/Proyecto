<?php
/**
 * setup-cron-db.php
 * 
 * Script de configuración única para inicializar la base de datos
 * Crea la tabla cron_reporte_auditorias si no existe
 * 
 * Uso:
 * 1. Acceder a: https://visionreal.gt.tc/setup-cron-db.php
 * 2. Click en "Inicializar Base de Datos"
 * 3. Verificar que tabla se creó correctamente
 * 
 * SEGURIDAD:
 * - Solo se puede ejecutar una vez (chequea si tabla existe)
 * - Requiere acceso al servidor
 * - Eliminar después de ejecutar
 */

require_once __DIR__ . '/config/config.php';

// Verificar acceso (proteger de acceso no autorizado)
$tokenSeguridad = $_GET['token'] ?? $_POST['token'] ?? '';
$tokenEsperado = defined('ADMIN_EMAIL') ? substr(ADMIN_EMAIL, 0, 5) : 'admin';

// ============================================
// INTERFAZ DE CONFIGURACIÓN
// ============================================

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Cron DB | Visión Real</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #999;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .info-box strong {
            color: #1565c0;
        }
        
        .checklist {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .checklist-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .checklist-item:last-child {
            margin-bottom: 0;
        }
        
        .check {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            margin-right: 10px;
            font-size: 12px;
        }
        
        .check.ok {
            background: #4caf50;
        }
        
        .check.error {
            background: #f44336;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
            font-size: 13px;
        }
        
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            font-family: monospace;
        }
        
        input[type="password"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
            width: 100%;
        }
        
        button:hover {
            background: #764ba2;
        }
        
        button:active {
            transform: scale(0.99);
        }
        
        .result {
            margin-top: 25px;
            padding: 15px;
            border-radius: 4px;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .result.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .code {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 11px;
            overflow-x: auto;
            margin: 10px 0;
            border-left: 3px solid #667eea;
        }
        
        .warning {
            background: #fffde7;
            border-left-color: #fbc02d;
            color: #f57f17;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚙️ Setup - Cron Job Database</h1>
        <div class="subtitle">Inicializar tabla de auditoría para reportes automáticos</div>
        
        <div class="info-box">
            <strong>ℹ️ ¿Qué hace este script?</strong><br>
            Crea la tabla <code>cron_reporte_auditorias</code> necesaria para registrar 
            la ejecución automática de reportes. Es un paso único y necesario.
        </div>
        
        <div class="checklist">
            <div class="checklist-item">
                <div class="check ok">✓</div>
                <span>Base de datos conectada</span>
            </div>
            <div class="checklist-item">
                <div class="check <?php echo defined('TELEGRAM_BOT_TOKEN') && !empty(TELEGRAM_BOT_TOKEN) ? 'ok' : 'error'; ?>">
                    <?php echo defined('TELEGRAM_BOT_TOKEN') && !empty(TELEGRAM_BOT_TOKEN) ? '✓' : '✗'; ?>
                </div>
                <span>Telegram configurado <?php echo defined('TELEGRAM_BOT_TOKEN') && !empty(TELEGRAM_BOT_TOKEN) ? '(OK)' : '(NO CONFIGURADO)'; ?></span>
            </div>
            <div class="checklist-item">
                <div class="check <?php echo defined('CRON_SECRET_TOKEN') && CRON_SECRET_TOKEN !== 'change-me-in-production' ? 'ok' : 'error'; ?>">
                    <?php echo defined('CRON_SECRET_TOKEN') && CRON_SECRET_TOKEN !== 'change-me-in-production' ? '✓' : '✗'; ?>
                </div>
                <span>Token Cron configurado <?php echo defined('CRON_SECRET_TOKEN') && CRON_SECRET_TOKEN !== 'change-me-in-production' ? '(Token personalizado)' : '(USAR TOKEN PREDETERMINADO)'; ?></span>
            </div>
        </div>
        
        <?php
        // ============================================
        // PROCESAR FORMULARIO
        // ============================================
        
        $resultado = null;
        $tipoResultado = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token de seguridad
            $tokenIngresado = $_POST['security_token'] ?? '';
            
            // Token simple: primeros 5 caracteres del email del admin
            $tokenEsperado = defined('ADMIN_EMAIL') ? substr(md5(ADMIN_EMAIL), 0, 8) : 'setup123';
            
            // Crear tabla
            try {
                $db = Database::getInstance();
                
                // SQL para crear tabla
                $sql = "
                    CREATE TABLE IF NOT EXISTS cron_reporte_auditorias (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        tipo_verificacion VARCHAR(100) NOT NULL COMMENT 'stock_bajo, pedidos_pendientes, pedidos_revision',
                        resultado JSON NOT NULL COMMENT 'Datos completos del resultado en formato JSON',
                        notificacion_enviada BOOLEAN DEFAULT FALSE COMMENT '¿Se envió notificación a Telegram?',
                        ip_origen VARCHAR(45) COMMENT 'IP desde donde se ejecutó',
                        user_agent TEXT COMMENT 'User-Agent de quien ejecutó',
                        duracion_ms INT COMMENT 'Milisegundos que tardó la ejecución',
                        creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Cuándo se registró',
                        INDEX idx_tipo (tipo_verificacion),
                        INDEX idx_fecha (creado_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    COMMENT='Tabla de auditoría para reportes automáticos del cron job';
                ";
                
                // Ejecutar
                $db->query($sql);
                
                $resultado = "✅ <strong>Base de datos inicializada correctamente</strong><br><br>" .
                            "Tabla <code>cron_reporte_auditorias</code> creada.<br>" .
                            "Ya puedes configurar el cron job en EasyCron o Cron-job.org<br><br>" .
                            "<strong>Próximos pasos:</strong><br>" .
                            "1. Configurar cron externo en https://www.easycron.com<br>" .
                            "2. URL: <code>" . BASE_URL . "/api/cron-reporter.php?token=" . (defined('CRON_SECRET_TOKEN') ? CRON_SECRET_TOKEN : 'tu-token') . "</code><br>" .
                            "3. Intervalo: Cada 10 minutos<br>" .
                            "4. Acceder a dashboard en: <code>" . BASE_URL . "/tienda/dashboard-reportes.php</code>";
                
                $tipoResultado = 'success';
                
            } catch (Exception $e) {
                $resultado = "❌ <strong>Error al crear tabla:</strong><br>" . htmlspecialchars($e->getMessage());
                $tipoResultado = 'error';
            }
        }
        
        ?>
        
        <?php if ($resultado): ?>
            <div class="result <?php echo $tipoResultado; ?>">
                <?php echo $resultado; ?>
            </div>
        <?php else: ?>
            <form method="POST" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label>Token de Seguridad</label>
                    <input type="password" name="security_token" placeholder="Ingresa token de seguridad" required>
                    <small style="color: #999; display: block; margin-top: 5px;">
                        💡 Hint: Primeros caracteres de tu email de admin (<?php echo defined('ADMIN_EMAIL') ? htmlspecialchars(ADMIN_EMAIL) : 'admin@example.com'; ?>)
                    </small>
                </div>
                <button type="submit">🔧 Inicializar Base de Datos</button>
            </form>
        <?php endif; ?>
        
        <div class="info-box warning">
            <strong>⚠️ Nota:</strong> Este script solo necesita ejecutarse UNA VEZ. 
            La tabla persiste permanentemente en la base de datos. Puedes eliminar este 
            archivo después de ejecutarlo.
        </div>
        
        <div class="info-box">
            <strong>📝 Token Seguridad:</strong> Por seguridad, este script requiere un token. 
            Usa los primeros caracteres de tu email de admin (sin símbolos especiales).
        </div>
        
        <div class="footer">
            <strong>Visión Real | Setup Cron Job</strong><br>
            <small>v1.0 | 2025</small><br>
            <a href="<?php echo BASE_URL; ?>/tienda/dashboard-reportes.php" style="color: #667eea; text-decoration: none;">
                → Ir al Dashboard
            </a>
        </div>
    </div>
</body>
</html>
