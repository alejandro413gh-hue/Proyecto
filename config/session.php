<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn()  { return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']); }

// ---- ROLES ----
function getRol()      { return $_SESSION['user_rol'] ?? ''; }
function isAdmin()     { return getRol() === 'administrador'; }
function isGestor()    { return getRol() === 'gestor_inventario'; }
function isVendedor()  { return getRol() === 'vendedor'; }

// Puede gestionar inventario (admin o gestor)
function puedeGestionarInventario() {
    return isAdmin() || isGestor();
}

// ---- PROTECCIÓN DE RUTAS ----
function requireLogin() {
    if (!isLoggedIn()) { header('Location:'.BASE_URL.'/index.php'); exit(); }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) { header('Location:'.BASE_URL.'/views/dashboard.php'); exit(); }
}

function requireGestorOAdmin() {
    requireLogin();
    if (!puedeGestionarInventario()) { header('Location:'.BASE_URL.'/views/dashboard.php'); exit(); }
}

function getCurrentUser() {
    return [
        'id'     => $_SESSION['user_id']    ?? null,
        'nombre' => $_SESSION['user_nombre'] ?? '',
        'email'  => $_SESSION['user_email']  ?? '',
        'rol'    => $_SESSION['user_rol']    ?? '',
    ];
}
?>
