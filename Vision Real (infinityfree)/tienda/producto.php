<?php
/**
 * tienda/producto.php
 * Página de detalle de producto.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/session_tienda.php';
require_once __DIR__ . '/../models/tienda/ProductoTienda.php';
require_once __DIR__ . '/../models/Descuento.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/tienda/catalogo.php'); exit(); }

$pt      = new ProductoTienda();
$p       = $pt->getDetalle($id);
if (!$p) { header('Location: ' . BASE_URL . '/tienda/catalogo.php'); exit(); }

$dm         = new Descuento();
$descuentos = $dm->getActivos();
$carritoN   = getCarritoCount();
$cliente    = getTiendaCliente();

$imgUrl = !empty($p['imagen'])
    ? BASE_URL . '/assets/img/productos/' . $p['imagen']
    : BASE_URL . '/tienda/assets/img/sin-imagen.webp';

$tieneStock = (int)$p['stock_total'] > 0;
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($p['nombre']) ?> — Visión Real</title>
<meta name="description" content="<?= htmlspecialchars(substr($p['descripcion'] ?? '', 0, 160)) ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="<?= BASE_URL ?>/tienda/assets/css/tienda.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="vr-navbar navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand vr-brand" href="<?= BASE_URL ?>/tienda/">
      <span class="brand-name">Visión Real</span>
      <span class="brand-sub">Tienda Online</span>
    </a>
    <div class="navbar-nav flex-row gap-2 align-items-center ms-auto">
      <?php if (tiendaLoggedIn()): ?>
        <div class="dropdown">
          <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($cliente['nombre']) ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/tienda/mis-pedidos.php"><i class="bi bi-box-seam me-2"></i>Mis pedidos</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/tienda/perfil.php"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/api/tienda/auth.php?action=logout">Cerrar sesión</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/tienda/login.php" class="btn btn-outline-light btn-sm"><i class="bi bi-person me-1"></i>Entrar</a>
      <?php endif; ?>
      <button class="btn btn-carrito position-relative" onclick="abrirCarrito()">
        <i class="bi bi-bag"></i>
        <span class="carrito-badge" id="carritoCount"><?= $carritoN ?></span>
      </button>
    </div>
  </div>
</nav>

<!-- Breadcrumb -->
<div class="container py-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb" style="font-size:.82rem">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/tienda/" class="text-muted">Inicio</a></li>
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/tienda/catalogo.php" class="text-muted">Catálogo</a></li>
      <?php if ($p['categoria_nombre']): ?>
      <li class="breadcrumb-item">
        <a href="<?= BASE_URL ?>/tienda/catalogo.php?categoria_id=<?= $p['categoria_id'] ?>" class="text-muted">
          <?= htmlspecialchars($p['categoria_nombre']) ?>
        </a>
      </li>
      <?php endif; ?>
      <li class="breadcrumb-item active"><?= htmlspecialchars($p['nombre']) ?></li>
    </ol>
  </nav>
</div>

<!-- Detalle producto -->
<div class="container pb-5">
  <div class="row g-5">

    <!-- Imagen -->
    <div class="col-md-5">
      <div class="product-gallery">
        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" id="imgPrincipal">
      </div>
    </div>

    <!-- Info -->
    <div class="col-md-7">
      <p class="text-muted small mb-1 text-uppercase" style="letter-spacing:.08em">
        <?= htmlspecialchars($p['categoria_nombre'] ?? '') ?>
        <?php if ($p['codigo']): ?>
          &nbsp;·&nbsp; Código: <strong><?= htmlspecialchars($p['codigo']) ?></strong>
        <?php endif; ?>
      </p>
      <h1 class="fw-bold mb-2" style="font-size:1.8rem"><?= htmlspecialchars($p['nombre']) ?></h1>

      <!-- Precio y descuentos -->
      <div class="mb-3">
        <span class="fs-3 fw-bold" style="color:var(--gold-dark)">
          $<?= number_format($p['precio'], 0, ',', '.') ?>
        </span>
        <?php
        // Mostrar descuentos aplicables al producto
        foreach ($descuentos as $d):
          $aplica = (!$d['aplica_producto_id'] && !$d['aplica_categoria_id'])
                 || $d['aplica_producto_id'] == $p['id']
                 || $d['aplica_categoria_id'] == $p['categoria_id'];
          if (!$aplica) continue;
          $txt = $d['tipo_descuento'] === 'porcentaje'
               ? $d['valor'] . '% OFF'
               : '$' . number_format($d['valor'], 0, ',', '.') . ' de descuento';
        ?>
        <span class="badge ms-2" style="background:var(--gold);color:#000;font-size:.8rem">
          🏷️ <?= htmlspecialchars($d['nombre']) ?>: <?= $txt ?>
        </span>
        <?php endforeach; ?>
      </div>

      <!-- Descripción -->
      <?php if (!empty($p['descripcion'])): ?>
      <p class="text-muted mb-4" style="line-height:1.7"><?= nl2br(htmlspecialchars($p['descripcion'])) ?></p>
      <?php endif; ?>

      <!-- Stock general -->
      <div class="mb-3">
        <?php if ($tieneStock): ?>
          <?php if ($p['stock_total'] > 10): ?>
            <span class="stock-chip disponible"><i class="bi bi-check-circle me-1"></i>En stock</span>
          <?php else: ?>
            <span class="stock-chip bajo"><i class="bi bi-exclamation-triangle me-1"></i>¡Últimas <?= $p['stock_total'] ?> unidades!</span>
          <?php endif; ?>
        <?php else: ?>
          <span class="stock-chip agotado"><i class="bi bi-x-circle me-1"></i>Agotado</span>
        <?php endif; ?>
      </div>

      <!-- Selector de talla -->
      <?php if (!empty($p['tallas'])): ?>
      <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <label class="fw-semibold" style="font-size:.9rem">Selecciona tu talla</label>
          <span id="infoStock" class="stock-chip" style="display:none"></span>
        </div>
        <div class="talla-selector">
          <?php foreach ($p['tallas'] as $t):
            $agotada = (int)$t['stock'] === 0;
          ?>
          <button class="talla-btn <?= $agotada ? 'agotada' : '' ?>"
                  data-talla="<?= htmlspecialchars($t['talla']) ?>"
                  data-stock="<?= (int)$t['stock'] ?>"
                  onclick="seleccionarTalla('<?= htmlspecialchars($t['talla']) ?>', <?= (int)$t['stock'] ?>, this)"
                  <?= $agotada ? 'disabled title="Agotado"' : '' ?>>
            <?= htmlspecialchars($t['talla']) ?>
            <?php if (!$agotada): ?>
              <small style="display:block;font-size:.65rem;opacity:.6"><?= $t['stock'] ?></small>
            <?php endif; ?>
          </button>
          <?php endforeach; ?>
        </div>
        <p class="text-muted" style="font-size:.75rem;margin-top:6px">
          <i class="bi bi-info-circle me-1"></i>El número bajo la talla indica las unidades disponibles.
          Las tallas tachadas están agotadas.
        </p>
      </div>
      <?php endif; ?>

      <!-- Cantidad -->
      <div class="mb-4">
        <label class="fw-semibold mb-2 d-block" style="font-size:.9rem">Cantidad</label>
        <div class="d-flex align-items-center gap-3">
          <button class="qty-btn" style="width:36px;height:36px;font-size:1.1rem" onclick="cambiarQty(-1)">−</button>
          <span id="qtyDisplay" class="fw-bold" style="font-size:1.1rem;min-width:30px;text-align:center">1</span>
          <button class="qty-btn" style="width:36px;height:36px;font-size:1.1rem" onclick="cambiarQty(1)">+</button>
        </div>
      </div>

      <!-- Botones de acción -->
      <?php if ($tieneStock): ?>
      <div class="d-flex gap-3 flex-wrap mb-4">
        <button class="btn btn-gold btn-lg flex-grow-1"
                onclick="agregarAlCarrito(<?= $p['id'] ?>, tallaSeleccionada, parseInt(document.getElementById('qtyDisplay').textContent))">
          <i class="bi bi-bag-plus me-2"></i>Agregar al carrito
        </button>
        <button class="btn btn-whatsapp btn-lg"
                onclick="comprarWhatsapp(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>', <?= $p['precio'] ?>)">
          <i class="bi bi-whatsapp me-1"></i>WhatsApp
        </button>
      </div>

      <!-- Opción recogida en tienda -->
      <div class="alert alert-light border d-flex align-items-center gap-2" style="border-radius:10px">
        <i class="bi bi-shop fs-5 text-muted"></i>
        <div>
          <strong style="font-size:.88rem">¿Prefieres recoger en tienda?</strong>
          <p class="mb-0 text-muted" style="font-size:.8rem">Selecciona "Recoger en tienda" al momento del checkout. Sin costo de envío.</p>
        </div>
      </div>
      <?php else: ?>
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Producto agotado.</strong> Escríbenos por WhatsApp para preguntar disponibilidad.
        <br>
        <button class="btn btn-whatsapp btn-sm mt-2"
                onclick="comprarWhatsapp(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>', <?= $p['precio'] ?>)">
          <i class="bi bi-whatsapp me-1"></i>Consultar disponibilidad
        </button>
      </div>
      <?php endif; ?>

    </div><!-- /col info -->
  </div><!-- /row -->
</div>

<!-- Panel carrito -->
<div class="vr-carrito-overlay" id="carritoOverlay" onclick="cerrarCarrito()"></div>
<aside class="vr-carrito-panel" id="carritoPanel">
  <div class="carrito-header">
    <h5><i class="bi bi-bag me-2"></i>Mi Carrito</h5>
    <button class="btn-close-panel" onclick="cerrarCarrito()">✕</button>
  </div>
  <div class="carrito-body" id="carritoBody"></div>
  <div class="carrito-footer" id="carritoFooter" style="display:none">
    <div class="d-flex justify-content-between mb-2">
      <strong>Subtotal:</strong><strong id="carritoSubtotal">$0</strong>
    </div>
    <a href="<?= BASE_URL ?>/tienda/checkout.php" class="btn btn-gold w-100 mb-2">Ir al checkout <i class="bi bi-arrow-right ms-1"></i></a>
    <button class="btn btn-outline-secondary w-100 btn-sm" onclick="cerrarCarrito()">Seguir comprando</button>
  </div>
</aside>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
const VR = { baseUrl: '<?= BASE_URL ?>', loggedIn: <?= tiendaLoggedIn() ? 'true' : 'false' ?> };
</script>
<script>
window.VR_STORE_WHATSAPP_NUMBER = '<?= htmlspecialchars(defined("STORE_WHATSAPP_NUMBER") ? STORE_WHATSAPP_NUMBER : "", ENT_QUOTES) ?>';
</script>
<script src="<?= BASE_URL ?>/tienda/assets/js/tienda.js?v=3" defer></script>
<script>
let qty = 1;
function cambiarQty(delta) {
  qty = Math.max(1, qty + delta);
  document.getElementById('qtyDisplay').textContent = qty;
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarCarrito(); });
</script>
    
