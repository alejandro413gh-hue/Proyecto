<?php
/**
 * tienda/catalogo.php
 * Catálogo de productos con filtros, búsqueda y paginación.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/session_tienda.php';
require_once __DIR__ . '/../models/tienda/ProductoTienda.php';

$pt = new ProductoTienda();

$filtros = [
    'busqueda'    => trim($_GET['busqueda']    ?? ''),
    'categoria_id'=> (int)($_GET['categoria_id'] ?? 0),
    'orden'       => $_GET['orden']            ?? 'reciente',
    'pagina'      => max(1, (int)($_GET['pagina'] ?? 1)),
];

$resultado  = $pt->getCatalogo($filtros);
$categorias = $pt->getCategoriasActivas();
$carritoN   = getCarritoCount();
$cliente    = getTiendaCliente();

$ordenes = [
    'reciente'    => 'Más nuevos',
    'precio_asc'  => 'Menor precio',
    'precio_desc' => 'Mayor precio',
    'nombre'      => 'Nombre A–Z',
];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Catálogo — Visión Real</title>
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
    <form class="vr-search d-none d-lg-flex ms-4 me-auto" action="<?= BASE_URL ?>/tienda/catalogo.php" method="GET">
      <input type="search" name="busqueda" class="form-control" value="<?= htmlspecialchars($filtros['busqueda']) ?>" placeholder="Buscar productos…">
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
        <a href="<?= BASE_URL ?>/tienda/login.php" class="btn btn-outline-light btn-sm"><i class="bi bi-person me-1"></i>Entrar</a>
      <?php endif; ?>
      <button class="btn btn-carrito position-relative" onclick="abrirCarrito()">
        <i class="bi bi-bag"></i>
        <span class="carrito-badge" id="carritoCount"><?= $carritoN ?></span>
      </button>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="row g-4">

    <!-- ══ Sidebar filtros ══ -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="card-body p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-funnel me-2"></i>Filtros</h6>

          <!-- Categorías -->
          <div class="mb-4">
            <p class="text-muted small fw-semibold mb-2 text-uppercase" style="font-size:.7rem;letter-spacing:.1em">Categoría</p>
            <a href="<?= BASE_URL ?>/tienda/catalogo.php?orden=<?= $filtros['orden'] ?><?= $filtros['busqueda'] ? '&busqueda=' . urlencode($filtros['busqueda']) : '' ?>"
               class="d-block py-1 px-2 rounded mb-1 text-decoration-none <?= !$filtros['categoria_id'] ? 'bg-dark text-white' : 'text-muted' ?>"
               style="font-size:.85rem">
              Todas las categorías
            </a>
            <?php foreach ($categorias as $cat): ?>
            <a href="<?= BASE_URL ?>/tienda/catalogo.php?categoria_id=<?= $cat['id'] ?>&orden=<?= $filtros['orden'] ?><?= $filtros['busqueda'] ? '&busqueda=' . urlencode($filtros['busqueda']) : '' ?>"
               class="d-flex justify-content-between align-items-center py-1 px-2 rounded mb-1 text-decoration-none <?= $filtros['categoria_id'] == $cat['id'] ? 'bg-dark text-white' : 'text-muted' ?>"
               style="font-size:.85rem">
              <span><?= htmlspecialchars($cat['nombre']) ?></span>
              <span class="badge" style="background:<?= $filtros['categoria_id'] == $cat['id'] ? 'var(--gold)' : '#eee' ?>;color:<?= $filtros['categoria_id'] == $cat['id'] ? '#000' : '#666' ?>"><?= $cat['total'] ?></span>
            </a>
            <?php endforeach; ?>
          </div>

          <!-- Ordenar -->
          <div>
            <p class="text-muted small fw-semibold mb-2 text-uppercase" style="font-size:.7rem;letter-spacing:.1em">Ordenar por</p>
            <?php foreach ($ordenes as $val => $label): ?>
            <a href="<?= BASE_URL ?>/tienda/catalogo.php?orden=<?= $val ?><?= $filtros['categoria_id'] ? '&categoria_id=' . $filtros['categoria_id'] : '' ?><?= $filtros['busqueda'] ? '&busqueda=' . urlencode($filtros['busqueda']) : '' ?>"
               class="d-block py-1 px-2 rounded mb-1 text-decoration-none <?= $filtros['orden'] === $val ? 'bg-dark text-white' : 'text-muted' ?>"
               style="font-size:.85rem">
              <?= $label ?>
            </a>
            <?php endforeach; ?>
          </div>

          <?php if ($filtros['busqueda'] || $filtros['categoria_id']): ?>
          <div class="mt-3 pt-3 border-top">
            <a href="<?= BASE_URL ?>/tienda/catalogo.php" class="btn btn-outline-secondary btn-sm w-100">
              <i class="bi bi-x-circle me-1"></i>Limpiar filtros
            </a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ══ Productos ══ -->
    <div class="col-lg-9">

      <!-- Header resultados -->
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <?php if ($filtros['busqueda']): ?>
            <h5 class="mb-1">Resultados para "<strong><?= htmlspecialchars($filtros['busqueda']) ?></strong>"</h5>
          <?php elseif ($filtros['categoria_id']): ?>
            <?php $catActiva = array_filter($categorias, fn($c) => $c['id'] == $filtros['categoria_id']); ?>
            <h5 class="mb-1"><?= htmlspecialchars(array_values($catActiva)[0]['nombre'] ?? 'Categoría') ?></h5>
          <?php else: ?>
            <h5 class="mb-1">Todos los productos</h5>
          <?php endif; ?>
          <p class="text-muted small mb-0"><?= $resultado['total'] ?> productos encontrados</p>
        </div>
        <!-- Búsqueda móvil -->
        <form class="d-flex d-lg-none gap-2" action="<?= BASE_URL ?>/tienda/catalogo.php" method="GET" style="flex:1;max-width:300px">
          <input type="search" name="busqueda" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['busqueda']) ?>" placeholder="Buscar…">
          <button type="submit" class="btn btn-sm btn-dark"><i class="bi bi-search"></i></button>
        </form>
      </div>

      <!-- Grid productos -->
      <?php if (empty($resultado['productos'])): ?>
      <div class="text-center py-5">
        <i class="bi bi-search" style="font-size:3rem;color:#ccc"></i>
        <h5 class="mt-3 text-muted">No se encontraron productos</h5>
        <p class="text-muted">Intenta con otros términos o revisa los filtros.</p>
        <a href="<?= BASE_URL ?>/tienda/catalogo.php" class="btn btn-gold">Ver todo el catálogo</a>
      </div>
      <?php else: ?>
      <div class="row g-4">
        <?php foreach ($resultado['productos'] as $p):
          $imgUrl = !empty($p['imagen'])
            ? BASE_URL . '/assets/img/productos/' . $p['imagen']
            : BASE_URL . '/tienda/assets/img/sin-imagen.svg';
          $tieneStock = (int)$p['stock_total'] > 0;
        ?>
        <div class="col-6 col-md-4">
          <div class="vr-product-card <?= !$tieneStock ? 'sin-stock' : '' ?>">
            <?php if (!$tieneStock): ?><div class="badge-agotado">Agotado</div><?php endif; ?>
            <a href="<?= BASE_URL ?>/tienda/producto.php?id=<?= $p['id'] ?>" class="card-img-link">
              <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" loading="lazy" class="product-img">
            </a>
            <div class="card-body-vr">
              <p class="prod-cat"><?= htmlspecialchars($p['categoria_nombre'] ?? '') ?></p>
              <h5 class="prod-name">
                <a href="<?= BASE_URL ?>/tienda/producto.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></a>
              </h5>
              <p class="prod-precio">$<?= number_format($p['precio'], 0, ',', '.') ?></p>
              <?php if (!empty($p['tallas'])): ?>
              <div class="tallas-preview">
                <?php foreach (array_slice($p['tallas'], 0, 4) as $t): ?>
                  <span class="talla-chip <?= (int)$t['stock'] === 0 ? 'agotada' : '' ?>"><?= htmlspecialchars($t['talla']) ?></span>
                <?php endforeach; ?>
                <?php if (count($p['tallas']) > 4): ?>
                  <span class="talla-chip mas">+<?= count($p['tallas']) - 4 ?></span>
                <?php endif; ?>
              </div>
              <?php endif; ?>
              <div class="card-actions mt-3">
                <a href="<?= BASE_URL ?>/tienda/producto.php?id=<?= $p['id'] ?>" class="btn btn-ver w-100 mb-2">Ver producto</a>
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

      <!-- Paginación -->
      <?php if ($resultado['total_pages'] > 1): ?>
      <nav class="mt-5 d-flex justify-content-center">
        <ul class="pagination">
          <?php if ($resultado['pagina'] > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina' => $resultado['pagina'] - 1])) ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          <?php endif; ?>

          <?php for ($i = max(1, $resultado['pagina'] - 2); $i <= min($resultado['total_pages'], $resultado['pagina'] + 2); $i++): ?>
          <li class="page-item <?= $i === $resultado['pagina'] ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina' => $i])) ?>"><?= $i ?></a>
          </li>
          <?php endfor; ?>

          <?php if ($resultado['pagina'] < $resultado['total_pages']): ?>
          <li class="page-item">
            <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina' => $resultado['pagina'] + 1])) ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </nav>
      <?php endif; ?>
      <?php endif; ?>

    </div><!-- /col productos -->
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
window.VR_STORE_WHATSAPP_NUMBER = '<?= htmlspecialchars(defined("STORE_WHATSAPP_NUMBER") ? STORE_WHATSAPP_NUMBER : "", ENT_QUOTES) ?>';
</script>
<script src="<?= BASE_URL ?>/tienda/assets/js/tienda.js?v=3" defer></script>
<script>
const VR = { baseUrl: '<?= BASE_URL ?>', loggedIn: <?= tiendaLoggedIn() ? 'true' : 'false' ?> };
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarCarrito(); });
</script>
</body>
</html>
