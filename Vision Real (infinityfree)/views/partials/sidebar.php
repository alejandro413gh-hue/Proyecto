<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
$user = getCurrentUser();
$ini  = strtoupper(substr($user['nombre'], 0, 1));
$cur  = basename($_SERVER['PHP_SELF']);
?>
<div id="sidebar-overlay" class="sidebar-overlay"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="brand">Visión Real</div>
    <div class="tagline">Sistema de Gestión</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Principal</div>
    <a href="<?=BASE_URL?>/views/dashboard.php" class="nav-item <?=$cur==='dashboard.php'?'active':''?>">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>

    <div class="nav-section-label">Gestión</div>

    <!-- Productos: todos lo ven -->
    <a href="<?=BASE_URL?>/views/productos.php" class="nav-item <?=$cur==='productos.php'?'active':''?>">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Productos
    </a>

    <!-- Clientes: todos lo ven -->
    <a href="<?=BASE_URL?>/views/clientes.php" class="nav-item <?=$cur==='clientes.php'?'active':''?>">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Clientes
    </a>

    <!-- Ventas: todos lo ven -->
    <a href="<?=BASE_URL?>/views/inventario.php" class="nav-item <?=$cur==='inventario.php'?'active':''?>">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      Inventario
    </a>

    <a href="<?=BASE_URL?>/views/ventas.php" class="nav-item <?=$cur==='ventas.php'?'active':''?>">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
      Ventas
    </a>

    <!-- Tienda Online -->
    <a href="<?=BASE_URL?>/views/pedidos_online.php" class="nav-item <?=$cur==='pedidos_online.php'?'active':''?>">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Pedidos Online
    </a>

    <?php if(isAdmin()): ?>
    <a href="<?=BASE_URL?>/views/pagos_tienda.php" class="nav-item <?=$cur==='pagos_tienda.php'?'active':''?>">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h18M6 11h12M9 15h6"/><path d="M4 19h16"/></svg>
      Configurar pagos
    </a>
    <a href="<?=BASE_URL?>/views/informacion_tienda.php" class="nav-item <?=$cur==='informacion_tienda.php'?'active':''?>">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
      Información de la Tienda
    </a>
    <a href="<?=BASE_URL?>/tienda/" target="_blank" class="nav-item">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 001.95-1.56l1.65-8.43H6"/></svg>
      Ver Tienda
    </a>
    <?php endif; ?>


    <?php if(isAdmin()): ?>
    <!-- Descuentos: solo admin -->
    <a href="<?=BASE_URL?>/views/descuentos.php" class="nav-item <?=$cur==='descuentos.php'?'active':''?>">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 010-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 000-5C13 2 12 7 12 7z"/></svg>
      Descuentos  Fechas
    </a>

    <div class="nav-section-label">Administración</div>
    <a href="<?=BASE_URL?>/views/usuarios.php" class="nav-item <?=$cur==='usuarios.php'?'active':''?>">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Usuarios
    </a>
    <a href="<?=BASE_URL?>/views/categorias.php" class="nav-item <?=$cur==='categorias.php'?'active':''?>">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
      Categorías
    </a>
    <?php endif; ?>

  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?=htmlspecialchars($ini)?></div>
      <div class="user-info">
        <div class="user-name"><?=htmlspecialchars($user['nombre'])?></div>
        <div class="user-role"><?=htmlspecialchars($user['rol'])?></div>
      </div>
    </div>
    <a href="<?=BASE_URL?>/views/perfil.php" style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;color:var(--white-dim);text-decoration:none;font-size:.8rem;transition:.2s;margin-bottom:6px" onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='transparent'">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Mi Perfil
    </a>
    <a href="<?=BASE_URL?>/controllers/AuthController.php?action=logout" class="btn-logout">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      Cerrar Sesión
    </a>
  </div>
</aside>
