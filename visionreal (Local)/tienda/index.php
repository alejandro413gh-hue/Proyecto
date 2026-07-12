<?php
/**
 * tienda/index.php
 * Página principal de la tienda online Visión Real.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/session_tienda.php';
require_once __DIR__ . '/../models/tienda/ProductoTienda.php';
require_once __DIR__ . '/../models/Descuento.php';
require_once __DIR__ . '/../models/TiendaConfig.php';

$pt        = new ProductoTienda();
$dm        = new Descuento();
$storeCfg   = (new TiendaConfig())->getAll();
$destacados = $pt->getDestacados(8);
$categorias = $pt->getCategoriasActivas();
$descuentos = $dm->getActivos();
$carritoN   = getCarritoCount();
$cliente    = getTiendaCliente();
$storeWhatsapp = trim((string) ($storeCfg['whatsapp_number'] ?? ''));
$storeAddress = trim((string) ($storeCfg['physical_address'] ?? ''));
$storeMapsUrl = trim((string) ($storeCfg['google_maps_url'] ?? ''));
$storeLocationLink = $storeMapsUrl !== ''
    ? $storeMapsUrl
    : ($storeAddress !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($storeAddress) : '');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Visión Real — Tienda Online</title>
<meta name="description" content="Compra los mejores calzados y accesorios en Visión Real. Envío y recogida en tienda.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/tienda/assets/css/tienda.css" rel="stylesheet">
</head>
<body>

<!-- ══════════════════════════════════════
     NAVBAR
══════════════════════════════════════ -->
<nav class="vr-navbar navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand vr-brand" href="<?= BASE_URL ?>/tienda/">
      <span class="brand-name">Visión Real</span>
      <span class="brand-sub">Tienda Online</span>
    </a>

    <!-- Buscador desktop -->
    <form class="vr-search d-none d-lg-flex ms-4 me-auto" id="formBusqueda">
      <input type="search" class="form-control" id="inputBusqueda" placeholder="Buscar productos…" autocomplete="off">
      <button type="submit" class="btn btn-search"><i class="bi bi-search"></i></button>
    </form>

    <div class="navbar-nav flex-row gap-2 align-items-center">
      <?php if (tiendaLoggedIn()): ?>
        <div class="dropdown">
          <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($cliente['nombre']) ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/tienda/mis-pedidos.php"><i class="bi bi-box-seam me-2"></i>Mis pedidos</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/tienda/perfil.php"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/api/tienda/auth.php?action=logout"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/tienda/login.php" class="btn btn-outline-light btn-sm">
          <i class="bi bi-person me-1"></i>Entrar
        </a>
      <?php endif; ?>

      <!-- Carrito -->
      <button class="btn btn-carrito position-relative" id="btnCarrito" onclick="abrirCarrito()">
        <i class="bi bi-bag"></i>
        <span class="carrito-badge" id="carritoCount"><?= $carritoN ?></span>
      </button>
    </div>
  </div>
</nav>

<!-- ══════════════════════════════════════
     HERO / BANNER
══════════════════════════════════════ -->
<section class="vr-hero">
  <div class="hero-content">
    <div class="container text-center text-white">
      <p class="hero-eyebrow">Nueva colección</p>
      <h1 class="hero-title">Estilo que te define</h1>
      <p class="hero-sub">Los mejores calzados con descuentos exclusivos</p>
      <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="<?= BASE_URL ?>/tienda/catalogo.php" class="btn btn-gold btn-lg">
          Ver catálogo <i class="bi bi-arrow-right ms-1"></i>
        </a>
        <?php if (tiendaLoggedIn()): ?>
          <a href="<?= BASE_URL ?>/tienda/mis-pedidos.php" class="btn btn-outline-light btn-lg">
            <i class="bi bi-box-seam me-1"></i>Mis pedidos
          </a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/tienda/login.php?modo=registro" class="btn btn-outline-light btn-lg"> Crear cuenta gratis
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════
     BANNER DESCUENTOS ACTIVOS
══════════════════════════════════════ -->
<?php if (!empty($descuentos)): ?>
<section class="vr-promo-bar py-2">
  <div class="container">
    <div class="promo-ticker">
      <?php foreach ($descuentos as $d): ?>
        <span class="promo-item">
          🏷️ <?= htmlspecialchars($d['nombre']) ?>
          — <?= $d['tipo_descuento'] === 'porcentaje' ? $d['valor'] . '% OFF' : '$' . number_format($d['valor'], 0, ',', '.') . ' dto' ?>
          <?php if ($d['aplica_genero'] !== 'todos'): ?>
            (<?= $d['aplica_genero'] === 'dama' ? '♀️ Dama' : '♂️ Caballero' ?>)
          <?php endif; ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════
     CATEGORÍAS
══════════════════════════════════════ -->
<?php if (!empty($categorias)): ?>
<section class="py-5">
  <div class="container">
    <h2 class="section-title">Categorías</h2>
    <div class="row g-3">
      <?php foreach ($categorias as $cat): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="<?= BASE_URL ?>/tienda/catalogo.php?categoria_id=<?= $cat['id'] ?>"
           class="vr-cat-card text-decoration-none">
          <div class="cat-name"><?= htmlspecialchars($cat['nombre']) ?></div>
          <div class="cat-count"><?= $cat['total'] ?> productos</div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════
     PRODUCTOS DESTACADOS
══════════════════════════════════════ -->
<section class="py-5 bg-light-subtle">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">Más vendidos</h2>
      <a href="<?= BASE_URL ?>/tienda/catalogo.php" class="btn btn-outline-dark btn-sm">
        Ver todos <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row g-4" id="productoDestacados">
      <?php foreach ($destacados as $p):
        $imgUrl = !empty($p['imagen'])
            ? BASE_URL . '/assets/img/productos/' . $p['imagen']
            : BASE_URL . '/tienda/assets/img/sin-imagen.webp';
        $tieneStock = (int)$p['stock_total'] > 0;
      ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="vr-product-card <?= !$tieneStock ? 'sin-stock' : '' ?>">
          <?php if (!$tieneStock): ?>
            <div class="badge-agotado">Agotado</div>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/tienda/producto.php?id=<?= $p['id'] ?>" class="card-img-link">
            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>"
                 loading="lazy" class="product-img">
          </a>
          <div class="card-body-vr">
            <p class="prod-cat"><?= htmlspecialchars($p['categoria_nombre'] ?? '') ?></p>
            <h5 class="prod-name">
              <a href="<?= BASE_URL ?>/tienda/producto.php?id=<?= $p['id'] ?>">
                <?= htmlspecialchars($p['nombre']) ?>
              </a>
            </h5>
            <p class="prod-precio">$<?= number_format($p['precio'], 0, ',', '.') ?></p>

            <!-- Tallas disponibles (preview) -->
            <?php if (!empty($p['tallas'])): ?>
            <div class="tallas-preview">
              <?php foreach (array_slice($p['tallas'], 0, 5) as $t): ?>
                <span class="talla-chip <?= (int)$t['stock'] === 0 ? 'agotada' : '' ?>">
                  <?= htmlspecialchars($t['talla']) ?>
                </span>
              <?php endforeach; ?>
              <?php if (count($p['tallas']) > 5): ?>
                <span class="talla-chip mas">+<?= count($p['tallas']) - 5 ?></span>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="card-actions mt-3">
              <a href="<?= BASE_URL ?>/tienda/producto.php?id=<?= $p['id'] ?>"
                 class="btn btn-ver w-100 mb-2">Ver producto</a>
              <?php if ($tieneStock): ?>
              <button class="btn btn-whatsapp w-100"
                      onclick="comprarWhatsapp(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>', <?= $p['precio'] ?>)">
                <i class="bi bi-whatsapp me-1"></i>WhatsApp
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════
     PANEL LATERAL CARRITO
══════════════════════════════════════ -->
<div class="vr-carrito-overlay" id="carritoOverlay" onclick="cerrarCarrito()"></div>
<aside class="vr-carrito-panel" id="carritoPanel">
  <div class="carrito-header">
    <h5><i class="bi bi-bag me-2"></i>Mi Carrito</h5>
    <button class="btn-close-panel" onclick="cerrarCarrito()">✕</button>
  </div>
  <div class="carrito-body" id="carritoBody">
    <div class="text-center text-muted py-5">
      <i class="bi bi-bag fs-1 d-block mb-2"></i>
      Tu carrito está vacío
    </div>
  </div>
  <div class="carrito-footer" id="carritoFooter" style="display:none">
    <div class="d-flex justify-content-between mb-2">
      <strong>Subtotal:</strong>
      <strong id="carritoSubtotal">$0</strong>
    </div>
    <a href="<?= BASE_URL ?>/tienda/checkout.php" class="btn btn-gold w-100 mb-2">
      Ir al checkout <i class="bi bi-arrow-right ms-1"></i>
    </a>
    <button class="btn btn-outline-secondary w-100 btn-sm" onclick="cerrarCarrito()">
      Seguir comprando
    </button>
  </div>
</aside>

<!-- ══════════════════════════════════════
     FOOTER
══════════════════════════════════════ -->
<footer class="vr-footer mt-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <h5 class="footer-brand">Visión Real</h5>
        <p class="text-muted">Calzado y accesorios de calidad. Visita nuestra tienda física o compra online.</p>
      </div>
      <div class="col-md-4">
        <h6>Navegación</h6>
        <ul class="list-unstyled">
          <li><a href="<?= BASE_URL ?>/tienda/" class="footer-link">Inicio</a></li>
          <li><a href="<?= BASE_URL ?>/tienda/catalogo.php" class="footer-link">Catálogo</a></li>
          <?php if (tiendaLoggedIn()): ?>
          <li><a href="<?= BASE_URL ?>/tienda/mis-pedidos.php" class="footer-link">Mis pedidos</a></li>
          <li><a href="<?= BASE_URL ?>/tienda/perfil.php" class="footer-link">Mi perfil</a></li>
          <?php else: ?>
          <li><a href="<?= BASE_URL ?>/tienda/login.php" class="footer-link">Iniciar sesión</a></li>
          <li><a href="<?= BASE_URL ?>/tienda/login.php" class="footer-link">Crear cuenta</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="col-md-4">
        <h6>Contacto</h6>
        <p class="text-muted mb-1">
          <i class="bi bi-whatsapp me-2 text-success"></i>
          <?php if ($storeWhatsapp): ?>
            <a href="https://wa.me/<?= htmlspecialchars($storeWhatsapp) ?>" class="footer-link" target="_blank" rel="noopener">WhatsApp</a>
          <?php else: ?>
            <span>WhatsApp no configurado</span>
          <?php endif; ?>
        </p>
        <p class="text-muted">
          <?php if ($storeLocationLink): ?>
            <a href="<?= htmlspecialchars($storeLocationLink) ?>" class="footer-link d-inline-flex align-items-center gap-2 px-2 py-1 rounded-2" target="_blank" rel="noopener" style="pointer-events:auto;display:inline-flex;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);">
              <i class="bi bi-geo-alt text-danger"></i>
              <span><?= htmlspecialchars($storeAddress ?: 'Ver ubicación de la tienda') ?></span>
            </a>
          <?php else: ?>
            <span class="d-inline-flex align-items-center gap-2">
              <i class="bi bi-geo-alt text-muted"></i>
              <span>Recoge tu pedido en tienda</span>
            </span>
          <?php endif; ?>
        </p>
      </div>
    </div>
    <hr>
    <p class="text-center text-muted small mb-0">
      © <?= date('Y') ?> Visión Real — Sistema ERP + Ecommerce
    </p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script>
window.VR_STORE_WHATSAPP_NUMBER = '<?= htmlspecialchars($storeWhatsapp, ENT_QUOTES) ?>';
</script>
<script src="<?= BASE_URL ?>/tienda/assets/js/tienda.js?v=3" defer></script>
<script>
// Configuración global para JS
const VR = {
  baseUrl: '<?= BASE_URL ?>',
  loggedIn: <?= tiendaLoggedIn() ? 'true' : 'false' ?>,
};

// Busqueda
document.getElementById('formBusqueda')?.addEventListener('submit', function(e) {
  e.preventDefault();
  const q = document.getElementById('inputBusqueda').value.trim();
  if (q) window.location.href = VR.baseUrl + '/tienda/catalogo.php?busqueda=' + encodeURIComponent(q);
});

// Cargar carrito al abrir
function abrirCarrito() {
  document.getElementById('carritoPanel').classList.add('open');
  document.getElementById('carritoOverlay').style.display = 'block';
  if (VR.loggedIn) cargarCarrito();
}
function cerrarCarrito() {
  document.getElementById('carritoPanel').classList.remove('open');
  document.getElementById('carritoOverlay').style.display = 'none';
}

// WhatsApp
function comprarWhatsapp(id, nombre, precio) {
  const msg = encodeURIComponent(
    `Hola Visión Real! Me interesa el producto: *${nombre}* ($${precio.toLocaleString('es-CO')}). ID: ${id}`
  );
  const target = '<?= htmlspecialchars($storeWhatsapp, ENT_QUOTES) ?>' || '573125420576';
  window.open(`https://wa.me/${target}?text=${msg}`, '_blank');
}
</script>
</body>
</html>
