<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn()  { return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']); }
function isAdmin()     { return ($_SESSION['user_rol'] ?? '') === 'administrador'; }

function requireLogin() {
    if (!isLoggedIn()) { header('Location: '.BASE_URL.'/index.php'); exit(); }
}
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) { header('Location: '.BASE_URL.'/views/dashboard.php'); exit(); }
}
function getCurrentUser() {
    return ['id'=>$_SESSION['user_id']??null,'nombre'=>$_SESSION['user_nombre']??'','email'=>$_SESSION['user_email']??'','rol'=>$_SESSION['user_rol']??''];
}
?>
