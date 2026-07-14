<?php
/**
 * api/tienda/auth.php
 * API JSON para autenticación de clientes online.
 * POST action = registrar | login | logout | perfil | cambiar_password
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../tienda/includes/session_tienda.php';
require_once __DIR__ . '/../../models/tienda/ClienteOnline.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$cm     = new ClienteOnline();

switch ($action) {

    case 'registrar':
        $r = $cm->registrar([
            'nombre'   => $_POST['nombre']   ?? '',
            'email'    => $_POST['email']    ?? '',
            'password' => $_POST['password'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'sexo'     => $_POST['sexo']     ?? 'O',
        ]);
        if (isset($r['error'])) {
            http_response_code(422);
        } else {
            // Auto-login tras registro
            $loginResult = $cm->login($_POST['email'], $_POST['password']);
            if ($loginResult['success'] ?? false) {
                tiendaLogin($loginResult['cliente'], $loginResult['session_token']);
                $r['redirect'] = BASE_URL . '/tienda/index.php';
            }
        }
        echo json_encode($r);
        break;

    case 'login':
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        if (empty($email) || empty($password)) {
            http_response_code(422);
            echo json_encode(['error' => 'Correo y contraseña requeridos']);
            break;
        }
        $r = $cm->login($email, $password);
        if ($r['success'] ?? false) {
            tiendaLogin($r['cliente'], $r['session_token']);
            $redirect = $_POST['redirect'] ?? (BASE_URL . '/tienda/index.php');
            $r['redirect'] = $redirect;
            unset($r['session_token']); // No exponer token en respuesta
        } else {
            http_response_code(401);
        }
        echo json_encode($r);
        break;

    case 'logout':
        if (tiendaLoggedIn() && isset($_COOKIE['vr_tienda_token'])) {
            $cm->cerrarSesion($_COOKIE['vr_tienda_token']);
        }
        tiendaLogout(); // redirect incluido
        break;

    case 'perfil':
        if (!tiendaLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Sesión requerida']);
            break;
        }
        $cliente = getTiendaCliente();
        $datos   = $cm->getById($cliente['id']);
        unset($datos['password'], $datos['token_verificar']);
        echo json_encode(['success' => true, 'cliente' => $datos]);
        break;

    case 'actualizar_perfil':
        if (!tiendaLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Sesión requerida']);
            break;
        }
        $cliente = getTiendaCliente();
        $ok = $cm->actualizarPerfil($cliente['id'], $_POST);
        echo json_encode($ok ? ['success' => true] : ['error' => 'Error al actualizar']);
        break;

    case 'cambiar_password':
        if (!tiendaLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Sesión requerida']);
            break;
        }
        $cliente  = getTiendaCliente();
        $r = $cm->cambiarPassword(
            $cliente['id'],
            $_POST['password_actual'] ?? '',
            $_POST['password_nueva']  ?? ''
        );
        if (isset($r['error'])) http_response_code(422);
        echo json_encode($r);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
}
