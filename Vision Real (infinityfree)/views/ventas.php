<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = 'Ventas';
require_once __DIR__ . '/../config/config.php';
requireLogin();

require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Descuento.php';
require_once __DIR__ . '/../models/Talla.php';
require_once __DIR__ . '/../models/Factura.php';

new Producto();
new Categoria();
new Cliente();
new Venta();
new Descuento();
new Talla();
new Factura();

$user = getCurrentUser();
$posConfig = [
    'baseUrl' => BASE_URL,
    'auth' => [
        'rol' => $user['rol'] ?? '',
        'is_admin' => isAdmin(),
        'is_gestor' => isGestor(),
        'is_vendedor' => isVendedor(),
        'can_edit_price' => isAdmin() || isGestor(),
        'can_see_margin' => isAdmin(),
    ],
    'defaultCustomer' => [
        'nombre' => 'Consumidor Final',
        'nit' => '',
        'telefono' => '',
        'sexo' => '',
    ],
    'endpoints' => [
        'bootstrap' => BASE_URL . '/controllers/VentaPosController.php?action=bootstrap',
        'catalog' => BASE_URL . '/controllers/VentaPosController.php?action=catalog',
        'barcode' => BASE_URL . '/controllers/VentaPosController.php?action=barcode',
        'tallas' => BASE_URL . '/controllers/VentaPosController.php?action=tallas',
        'toggleFavorite' => BASE_URL . '/controllers/VentaPosController.php?action=toggle_favorito',
        'checkout' => BASE_URL . '/controllers/VentaPosController.php?action=checkout',
        'discount' => BASE_URL . '/controllers/DescuentoController.php',
    ],
];

include __DIR__ . '/partials/head.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/ventas-pos.css">

<div class="layout">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="main">
    <header class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" id="menu-toggle" type="button" aria-label="Abrir menú">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div>
          <h1 class="page-title">Ventas - Sistema POS</h1>
          <div class="vr-pos__subtitle">Flujo optimizado para caja rápida, escáner y alto volumen de clientes.</div>
        </div>
      </div>
      <div class="topbar-right vr-pos__meta">
        <a href="<?= BASE_URL ?>/views/ventas_export.php" class="btn btn-outline btn-sm">Exportar Excel</a>
      </div>
    </header>

    <div class="content">
      <div class="vr-pos">
        <section class="vr-pos__main vr-pos__panel">
          <div class="vr-pos__toolbar">
            <div>
              <h2 class="vr-pos__title">Catálogo de venta</h2>
              <div class="vr-pos__subtitle" id="vr-grid-meta">Cargando catálogo...</div>
            </div>
          </div>

          <div class="vr-pos__search">
            <input id="vr-search" type="search" placeholder="Código, código de barras, referencia o nombre... (Enter para escanear)" autocomplete="off" />
            <button id="vr-search-btn" type="button">Buscar</button>
          </div>

          <div class="vr-pos__strip" id="vr-categories"></div>

          <div class="vr-pos__filters">
            <div class="vr-pos__hint" id="vr-hint">Atajos: <span class="vr-kbd">F2</span> buscar <span class="vr-kbd">F3</span> cliente <span class="vr-kbd">F4</span> registrar <span class="vr-kbd">Esc</span> cancelar</div>
            <div class="vr-pos__orders" id="vr-orders"></div>
          </div>

          <div class="vr-pos__body">
            <div class="vr-pos__grid" id="vr-grid"></div>
          </div>
        </section>

        <aside class="vr-pos__side vr-side">
          <div class="vr-side__panel">
            <div class="vr-side__section">
              <div class="vr-side__header">
                <h3>Cliente rápido</h3>
                <small>F3</small>
              </div>
              <div class="vr-input-row">
                <input id="vr-customer-name" class="vr-input" type="text" placeholder="Nombre del cliente" />
                <input id="vr-customer-nit" class="vr-input" type="text" placeholder="Cédula / NIT" />
                <input id="vr-customer-phone" class="vr-input" type="text" placeholder="Teléfono del cliente" />
              </div>
              <div class="vr-sexo" style="margin-top:10px">
                <button type="button" data-sex="M">Hombre</button>
                <button type="button" data-sex="F">Mujer</button>
                <button type="button" data-sex="O">Otro</button>
              </div>
              <div style="display:flex;gap:8px;margin-top:10px">
                <button type="button" class="vr-mini-btn" id="vr-final-customer">Consumidor Final</button>
                <button type="button" class="vr-mini-btn" id="vr-clear-quick">Cancelar venta</button>
              </div>
            </div>

            <div class="vr-side__section">
              <div class="vr-side__header">
                <h3>Carrito inteligente</h3>
                <small id="vr-items-count">0</small>
              </div>
              <div class="vr-cart">
                <div class="vr-cart__list" id="vr-cart-list"></div>
              </div>
              <div class="vr-cart__customer" id="vr-cart-customer">Consumidor Final</div>
            </div>

            <div class="vr-side__section">
              <div class="vr-side__header">
                <h3>Descuento manual</h3>
                <small id="vr-units-count">0 unidades</small>
              </div>
              <input id="vr-discount-manual" class="vr-input" type="number" min="0" step="1" value="0" placeholder="0" />
            </div>

            <div class="vr-side__section">
              <div class="vr-side__header">
                <h3>Notas</h3>
                <small>Total rápido</small>
              </div>
              <textarea id="vr-notes" class="vr-textarea" placeholder="Notas de la venta..."></textarea>
            </div>

            <div class="vr-summary">
              <div class="vr-summary__row"><span>Subtotal</span><strong id="vr-subtotal">$0</strong></div>
              <div class="vr-summary__row"><span>Descuento</span><strong id="vr-discount">$0</strong></div>
              <div class="vr-summary__total"><span>Total a pagar</span><span id="vr-total">$0</span></div>
            </div>

            <div class="vr-actions">
              <button type="button" class="vr-action-btn vr-action-btn--danger" id="vr-clear">Cancelar</button>
              <button type="button" class="vr-action-btn vr-action-btn--primary" id="vr-checkout">Registrar venta</button>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</div>

<div class="vr-modal" id="vr-size-modal" aria-hidden="true">
  <div class="vr-modal__panel" role="dialog" aria-modal="true" aria-labelledby="vr-size-title">
    <div class="vr-modal__header">
      <div>
        <div class="vr-modal__eyebrow">Selecciona una talla</div>
        <h3 id="vr-size-title">Producto</h3>
      </div>
      <button type="button" class="vr-modal__close" id="vr-size-close" aria-label="Cerrar">X</button>
    </div>
    <div class="vr-modal__meta" id="vr-size-stock">Stock disponible</div>
    <div class="vr-modal__sizes" id="vr-size-list"></div>
  </div>
</div>

<input type="hidden" id="vr-discount-url" value="<?= BASE_URL ?>/controllers/DescuentoController.php">
<script>
  window.POS_CONFIG = <?= json_encode($posConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script defer src="<?= BASE_URL ?>/assets/js/ventas-pos.js"></script>
</body>
</html>

