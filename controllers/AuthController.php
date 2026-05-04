<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private $m;
    public function __construct() { 
        $this->m = new Usuario(); 
    }

    public function login() {
        if($_SERVER['REQUEST_METHOD']!=='POST') {
            return ['error' => 'Método no permitido'];
        }
        
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        
        // Validación
        if (empty($email) || empty($pass)) {
            return ['error' => 'Completa todos los campos'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Correo inválido'];
        }
        
        // Buscar usuario en BD
        $u = $this->m->findByEmail($email);
        if (!$u) {
            return ['error' => 'Credenciales incorrectas'];
        }
        
        // Verificar contraseña
        if (!password_verify($pass, $u['password'])) {
            return ['error' => 'Credenciales incorrectas'];
        }
        
        // ✅ CORREGIDO: Regenerar ID de sesión
        session_regenerate_id(true);
        
        // Guardar datos en sesión
        $_SESSION['user_id']     = (int)$u['id'];
        $_SESSION['user_nombre'] = $u['nombre'];
        $_SESSION['user_email']  = $u['email'];
        $_SESSION['user_rol']    = $u['rol'];
        $_SESSION['login_time']  = time();
        
        // Log del login (opcional)
        error_log('Login exitoso: ' . $email . ' - IP: ' . $_SERVER['REMOTE_ADDR']);
        
        return ['success' => true];
    }

    public function logout() {
        // Limpiar datos de sesión
        $_SESSION = [];
        
        // Eliminar cookie de sesión
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly']
            );
        }
        
        // Destruir sesión
        session_destroy();
        
        // Redirigir al login
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

// Manejo de logout por GET
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $c = new AuthController();
    $c->logout();
}
?>
