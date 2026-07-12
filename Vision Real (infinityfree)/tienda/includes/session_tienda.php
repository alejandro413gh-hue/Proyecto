<?php
/**
 * tienda/includes/session_tienda.php
 * Sistema de sesión para clientes de la tienda online.
 * Independiente del sistema de sesión de administración.
 * Incluir al inicio de cada página de la tienda.
 */
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Verificar sesión por token en cookie (recordar sesión)
if (!isset($_SESSION['cliente_online_id']) && isset($_COOKIE['vr_tienda_token'])) {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../models/tienda/ClienteOnline.php';
    $cm  = new ClienteOnline();
    $cli = $cm->verificarSesion($_COOKIE['vr_tienda_token']);
    if ($cli) {
        $_SESSION['cliente_online_id']     = $cli['id'];
        $_SESSION['cliente_online_nombre'] = $cli['nombre'];
        $_SESSION['cliente_online_email']  = $cli['email'];
        $_SESSION['cliente_online_sexo']   = $cli['sexo'];
    } else {
        // Token expirado → limpiar cookie
        setcookie('vr_tienda_token', '', time() - 3600, '/', '', false, true);
    }
}

/* ─── Helpers globales ─────────────────────────────────────── */

function tiendaLoggedIn(): bool {
    return isset($_SESSION['cliente_online_id']) && !empty($_SESSION['cliente_online_id']);
}

function getTiendaCliente(): array {
    return [
        'id'     => $_SESSION['cliente_online_id']     ?? null,
        'nombre' => $_SESSION['cliente_online_nombre'] ?? '',
        'email'  => $_SESSION['cliente_online_email']  ?? '',
        'sexo'   => $_SESSION['cliente_online_sexo']   ?? 'O',
    ];
}

function requireTiendaLogin(string $redirect = '/tienda/login.php'): void {
    if (!tiendaLoggedIn()) {
        $current = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ' . BASE_URL . $redirect . '?redirect=' . $current);
        exit();
    }
}

function tiendaLogin(array $cliente, string $token): void {
    session_regenerate_id(true);
    $_SESSION['cliente_online_id']     = $cliente['id'];
    $_SESSION['cliente_online_nombre'] = $cliente['nombre'];
    $_SESSION['cliente_online_email']  = $cliente['email'];
    $_SESSION['cliente_online_sexo']   = $cliente['sexo'];
    // Cookie de sesión persistente 30 días
    setcookie('vr_tienda_token', $token, time() + 60 * 60 * 24 * 30, '/', '', false, true);
}

function tiendaLogout(): void {
    unset(
        $_SESSION['cliente_online_id'],
        $_SESSION['cliente_online_nombre'],
        $_SESSION['cliente_online_email'],
        $_SESSION['cliente_online_sexo']
    );
    setcookie('vr_tienda_token', '', time() - 3600, '/', '', false, true);
    header('Location: ' . BASE_URL . '/tienda/index.php');
    exit();
}

/**
 * Devuelve la cantidad de items en el carrito del cliente actual.
 * Usa sesión cacheada para no hacer query en cada petición.
 */
function getCarritoCount(): int {
    if (!tiendaLoggedIn()) return 0;
    if (isset($_SESSION['carrito_count'])) return (int)$_SESSION['carrito_count'];

    require_once __DIR__ . '/../../models/tienda/Carrito.php';
    $cm = new Carrito();
    $count = $cm->contarItems((int)$_SESSION['cliente_online_id']);
    $_SESSION['carrito_count'] = $count;
    return $count;
}

function invalidarCacheCarrito(): void {
    unset($_SESSION['carrito_count']);
}
