<?php
/**
 * INSTRUCCIONES: Agregar este bloque al sidebar existente
 * Archivo: htdocs/views/partials/sidebar.php
 *
 * Busca la sección de enlaces del sidebar y agrega el siguiente bloque
 * justo después del enlace de "Ventas" o antes del cierre del nav.
 *
 * ── Copiar el bloque HTML de abajo ──────────────────────────────
 */
?>

<!-- ══ AGREGAR AL SIDEBAR EXISTENTE ══════════════════════════
     Pegar dentro del <nav> del sidebar, después de "Ventas"
════════════════════════════════════════════════════════════ -->

<!--
<div class="nav-section-title">Tienda Online</div>

<a href="<?= BASE_URL ?>/views/pedidos_online.php"
   class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'pedidos_online.php' ? 'active' : '' ?>">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
    <line x1="3" y1="6" x2="21" y2="6"/>
    <path d="M16 10a4 4 0 01-8 0"/>
  </svg>
  <span>Pedidos Online</span>
  <?php
  // Badge con pedidos pendientes
  require_once __DIR__ . '/../../models/tienda/Pedido.php';
  $pm = new Pedido();
  $stats = $pm->countPorEstado();
  $pendientes = $stats['pendiente'] ?? 0;
  if ($pendientes > 0):
  ?>
  <span style="margin-left:auto;background:var(--gold);color:var(--black);border-radius:10px;padding:2px 7px;font-size:.7rem;font-weight:700">
    <?= $pendientes ?>
  </span>
  <?php endif; ?>
</a>

<?php if (isAdmin()): ?>
<a href="<?= BASE_URL ?>/tienda/" target="_blank"
   class="nav-link">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
    <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 001.95-1.56l1.65-8.43H6"/>
  </svg>
  <span>Ver Tienda</span>
  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:auto;opacity:.5">
    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/>
  </svg>
</a>
<?php endif; ?>
-->

<?php
/*
 * TAMBIÉN: Agregar en views/productos.php un botón toggle de "visible_tienda"
 * En la tabla de productos, en la columna de acciones, agregar:
 *
 * <button onclick="toggleVisible(<?= $prod['id'] ?>, this)"
 *         class="btn btn-sm"
 *         style="background:<?= $prod['visible_tienda'] ? 'var(--gold)' : 'var(--bg-hover)' ?>;
 *                color:<?= $prod['visible_tienda'] ? 'var(--black)' : 'var(--white-muted)' ?>;
 *                font-size:.7rem;padding:3px 8px"
 *         title="<?= $prod['visible_tienda'] ? 'Visible en tienda' : 'Oculto en tienda' ?>">
 *   🌐 <?= $prod['visible_tienda'] ? 'Online ✓' : 'Online' ?>
 * </button>
 *
 * Y el JS correspondiente en assets/js/app.js:
 *
 * async function toggleVisible(id, btn) {
 *   const r    = await fetch(BASE_URL + '/api/tienda/admin.php?action=toggle_visible&id=' + id);
 *   const data = await r.json();
 *   if (data.success) {
 *     const vis = data.visible;
 *     btn.style.background = vis ? 'var(--gold)' : 'var(--bg-hover)';
 *     btn.style.color = vis ? 'var(--black)' : 'var(--white-muted)';
 *     btn.innerHTML = '🌐 Online ' + (vis ? '✓' : '');
 *     btn.title = vis ? 'Visible en tienda' : 'Oculto en tienda';
 *   }
 * }
 */
?>
