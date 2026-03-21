<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Talla.php';

$pm = new Producto();
$cm = new Categoria();
$tm = new Talla();

$productos  = $pm->getAll();

// Agrupar productos por categoría
$porCategoria = [];
foreach ($productos as $p) {
    $tallas = $tm->getDisponibles($p['id']);
    $stockTallas = array_sum(array_column($tallas, 'stock'));
    if ($p['stock'] > 0 || $stockTallas > 0) {
        $cat = $p['categoria_nombre'] ?? 'Sin categoría';
        $p['tallas'] = $tallas;
        $p['tiene_tallas'] = !empty($tallas);
        $porCategoria[$cat][] = $p;
    }
}

// Índice numérico de categorías: 0=todos, 1=primera cat, 2=segunda, etc.
$catKeys = array_keys($porCategoria);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Visión Real — Tienda</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{--cream:#f5f0e8;--warm:#ede8dd;--dark:#1a1612;--brown:#2d2318;--gold:#b8942a;--gold2:#d4ad47;--text:#2d2318;--muted:#7a6e5e;--border:rgba(45,35,24,0.12);--white:#fff;--success:#2d6a4f;--success-bg:rgba(45,106,79,.1)}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'DM Sans',sans-serif;background:var(--cream);color:var(--text)}
.header{position:sticky;top:0;z-index:100;background:rgba(245,240,232,.97);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);padding:0 5%;display:flex;align-items:center;justify-content:space-between;height:68px}
.logo{font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;color:var(--dark)}.logo span{color:var(--gold)}
.cart-btn{display:flex;align-items:center;gap:8px;background:var(--dark);color:var(--cream);border:none;border-radius:50px;padding:10px 20px;font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:500;cursor:pointer;transition:.2s}
.cart-btn:hover{background:var(--brown)}
.cart-count{background:var(--gold);color:var(--dark);border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700}
.hero{background:var(--dark);padding:80px 5% 70px;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 30% 50%,rgba(184,148,42,.15) 0%,transparent 60%)}
.hero *{position:relative;z-index:1}
.hero-tag{display:inline-block;font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold2);border:1px solid rgba(184,148,42,.4);padding:5px 16px;border-radius:20px;margin-bottom:20px}
.hero h1{font-family:'Playfair Display',serif;font-size:clamp(2rem,5vw,3.5rem);font-weight:700;color:var(--cream);line-height:1.15;margin-bottom:16px}
.hero h1 em{color:var(--gold2);font-style:italic}
.hero p{color:rgba(245,240,232,.6);font-size:.95rem;max-width:480px;margin:0 auto 32px;line-height:1.7}
.hero-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.btn-oro{padding:12px 28px;border-radius:50px;background:var(--gold);color:var(--dark);border:none;font-family:'DM Sans',sans-serif;font-size:.875rem;font-weight:600;cursor:pointer;transition:.2s}
.btn-oro:hover{background:var(--gold2);transform:translateY(-2px)}
.btn-claro{padding:12px 28px;border-radius:50px;background:transparent;color:var(--cream);border:1px solid rgba(245,240,232,.3);font-family:'DM Sans',sans-serif;font-size:.875rem;font-weight:500;cursor:pointer;transition:.2s}
.btn-claro:hover{border-color:var(--gold);color:var(--gold2)}
/* FILTROS */
.filtros{padding:28px 5% 0;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.filtros span{font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;color:var(--muted)}
.f-btn{padding:7px 18px;border-radius:20px;font-size:.8rem;border:1px solid var(--border);background:var(--white);color:var(--muted);cursor:pointer;transition:.2s;font-family:'DM Sans',sans-serif}
.f-btn:hover{background:var(--dark);color:var(--cream);border-color:var(--dark)}
.f-btn.activo{background:var(--dark);color:var(--cream);border-color:var(--dark)}
/* SECCIÓN */
.seccion{padding:40px 5%}
.sec-hdr{display:flex;align-items:baseline;gap:14px;margin-bottom:24px}
.sec-titulo{font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:600}
.sec-linea{flex:1;height:1px;background:var(--border)}
.sec-cnt{font-size:.78rem;color:var(--muted)}
/* GRID */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:20px}
/* CARD */
.pcard{background:var(--white);border-radius:14px;overflow:hidden;border:1px solid var(--border);transition:transform .25s,box-shadow .25s}
.pcard:hover{transform:translateY(-5px);box-shadow:0 16px 40px rgba(26,22,18,.14)}
.pcard-img{height:220px;background:var(--warm);display:flex;align-items:center;justify-content:center;font-size:3.5rem;position:relative;overflow:hidden}
.pcard-img img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
.pcard-lbl{position:absolute;top:10px;left:10px;background:rgba(245,240,232,.9);font-size:.65rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);padding:3px 10px;border-radius:20px;z-index:1}
.pcard-body{padding:16px}
.pcard-nombre{font-family:'Playfair Display',serif;font-size:1rem;font-weight:600;margin-bottom:6px;line-height:1.3}
.pcard-precio{font-size:1.1rem;font-weight:700;color:var(--gold);font-family:'Playfair Display',serif}
.pcard-tallas-lbl{font-size:.7rem;color:var(--muted);margin-top:8px;margin-bottom:5px}
.tallas-chips{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:4px}
.talla-chip{padding:3px 9px;border-radius:6px;font-size:.72rem;font-weight:700;border:1.5px solid var(--border);background:var(--warm);color:var(--text)}
.pcard-stock{font-size:.72rem;color:var(--muted);margin-top:6px}
.pcard-pie{padding:0 16px 16px}
.btn-agregar{width:100%;padding:10px;border-radius:8px;background:var(--dark);color:var(--cream);border:none;font-family:'DM Sans',sans-serif;font-size:.82rem;font-weight:500;cursor:pointer;transition:.2s}
.btn-agregar:hover{background:var(--brown)}
/* MODAL TALLA */
.modal-fondo{display:none;position:fixed;inset:0;background:rgba(26,22,18,.65);z-index:500;align-items:center;justify-content:center;padding:20px}
.modal-fondo.abierto{display:flex}
.modal-talla{background:var(--white);border-radius:16px;width:100%;max-width:420px;overflow:hidden;box-shadow:0 24px 64px rgba(26,22,18,.3)}
.modal-talla-hdr{padding:20px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between}
.modal-talla-titulo{font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:600;margin-bottom:3px}
.modal-talla-precio{font-size:.95rem;color:var(--gold);font-weight:700;font-family:'Playfair Display',serif}
.btn-cerrar{background:none;border:none;cursor:pointer;color:var(--muted);font-size:1.3rem;line-height:1}
.modal-talla-cuerpo{padding:20px 24px}
.talla-grid{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}
.talla-opcion{min-width:64px;padding:10px 12px;border-radius:10px;border:2px solid var(--border);background:var(--cream);cursor:pointer;text-align:center;transition:.15s;font-family:'DM Sans',sans-serif}
.talla-opcion:hover{border-color:var(--gold)}
.talla-opcion.elegida{border-color:var(--dark);background:var(--dark);color:var(--cream)}
.talla-opcion.sin-stock{opacity:.35;cursor:not-allowed;text-decoration:line-through}
.talla-opcion-nombre{font-size:.95rem;font-weight:700}
.talla-opcion-stock{font-size:.65rem;margin-top:2px;opacity:.7}
.cant-row{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.cant-lbl{font-size:.78rem;color:var(--muted)}
.cant-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:var(--warm);cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;transition:.15s}
.cant-btn:hover{background:var(--dark);color:var(--cream)}
.cant-num{font-size:.95rem;font-weight:600;min-width:20px;text-align:center}
.talla-error{display:none;padding:8px 12px;background:rgba(231,76,60,.1);color:#c0392b;border-radius:7px;font-size:.82rem;margin-bottom:12px}
.btn-confirmar{width:100%;padding:12px;border-radius:10px;background:var(--dark);color:var(--cream);border:none;font-family:'DM Sans',sans-serif;font-size:.88rem;font-weight:500;cursor:pointer;transition:.2s}
.btn-confirmar:hover{background:var(--brown)}
/* DRAWER */
.drawer-fondo{position:fixed;inset:0;background:rgba(26,22,18,.4);z-index:199;opacity:0;pointer-events:none;transition:opacity .3s}
.drawer-fondo.abierto{opacity:1;pointer-events:all}
.drawer{position:fixed;top:0;right:0;bottom:0;width:400px;max-width:95vw;background:var(--white);box-shadow:-8px 0 40px rgba(26,22,18,.18);z-index:200;display:flex;flex-direction:column;transform:translateX(100%);transition:transform .35s cubic-bezier(.4,0,.2,1)}
.drawer.abierto{transform:translateX(0)}
.drawer-hdr{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.drawer-titulo{font-family:'Playfair Display',serif;font-size:1.15rem;font-weight:600}
.drawer-items{flex:1;overflow-y:auto;padding:16px 24px}
.item-carrito{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid var(--border)}
.item-ico{width:48px;height:48px;border-radius:10px;background:var(--warm);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.item-info{flex:1}
.item-nombre{font-size:.85rem;font-weight:500}
.item-sub{font-size:.72rem;color:var(--muted);margin-top:2px}
.item-ctrl{display:flex;align-items:center;gap:6px;margin-top:6px}
.q-btn{width:26px;height:26px;border-radius:6px;border:1px solid var(--border);background:var(--warm);cursor:pointer;font-size:.95rem;display:flex;align-items:center;justify-content:center;transition:.15s}
.q-btn:hover{background:var(--dark);color:var(--cream)}
.q-n{font-size:.85rem;font-weight:600;min-width:18px;text-align:center}
.item-total{font-size:.88rem;font-weight:600;color:var(--gold);text-align:right;padding-top:4px}
.item-del{background:none;border:none;cursor:pointer;color:#ccc;font-size:.9rem;padding:4px;transition:.15s;flex-shrink:0}
.item-del:hover{color:#e74c3c}
.carrito-vacio{text-align:center;padding:50px 20px;color:var(--muted)}
.drawer-pie{padding:18px 24px;border-top:1px solid var(--border)}
.t-fila{display:flex;justify-content:space-between;font-size:.85rem;color:var(--muted);margin-bottom:5px}
.t-desc{color:var(--success);font-weight:500}
.t-total{font-size:1.1rem;color:var(--dark);font-weight:700;padding-top:10px;border-top:1px solid var(--border);margin-top:6px;display:flex;justify-content:space-between}
.t-total .val{color:var(--gold);font-family:'Playfair Display',serif}
.btn-checkout{width:100%;padding:13px;border-radius:10px;background:var(--dark);color:var(--cream);border:none;font-family:'DM Sans',sans-serif;font-size:.88rem;font-weight:500;cursor:pointer;transition:.2s;margin-top:10px}
.btn-checkout:hover{background:var(--brown)}
/* MODAL CHECKOUT */
.checkout-fondo{display:none;position:fixed;inset:0;background:rgba(26,22,18,.65);z-index:300;align-items:center;justify-content:center;padding:20px}
.checkout-fondo.abierto{display:flex}
.checkout-modal{background:var(--white);border-radius:16px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px rgba(26,22,18,.3)}
.co-hdr{padding:20px 24px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.co-titulo{font-family:'Playfair Display',serif;font-size:1.15rem;font-weight:600}
.co-body{padding:20px 24px}
.fg{margin-bottom:14px}
.fg label{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);font-weight:500;margin-bottom:6px}
.fg input{width:100%;padding:10px 13px;border-radius:8px;border:1px solid var(--border);background:var(--cream);font-family:'DM Sans',sans-serif;font-size:.88rem;color:var(--text);transition:.2s}
.fg input:focus{outline:none;border-color:var(--gold)}
.f2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.resumen{background:var(--warm);border-radius:10px;padding:14px;margin-bottom:16px}
.res-titulo{font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);font-weight:600;margin-bottom:10px}
.res-item{display:flex;justify-content:space-between;font-size:.82rem;padding:3px 0}
.res-desc{color:var(--success);font-weight:500}
.res-total{display:flex;justify-content:space-between;padding-top:9px;margin-top:7px;border-top:1px solid var(--border);font-weight:700;font-size:.95rem}
.res-total .val{color:var(--gold);font-family:'Playfair Display',serif}
.promo-aviso{display:none;background:var(--success-bg);border:1.5px solid rgba(45,106,79,.3);border-radius:10px;padding:12px 14px;margin-bottom:14px}
.promo-aviso-titulo{font-size:.85rem;font-weight:700;color:var(--success);margin-bottom:4px}
.promo-aviso-txt{font-size:.78rem;color:#3a7a5a;line-height:1.5}
.promo-aviso-badge{display:inline-block;background:var(--success);color:#fff;border-radius:20px;padding:2px 12px;font-size:.75rem;font-weight:700;margin-top:6px}
.co-error{display:none;padding:10px 13px;border-radius:8px;font-size:.82rem;background:rgba(231,76,60,.1);color:#c0392b;border:1px solid rgba(231,76,60,.25);margin-bottom:14px}
.btn-confirmar-co{width:100%;padding:13px;border-radius:10px;background:var(--gold);color:var(--dark);border:none;font-family:'DM Sans',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;transition:.2s}
.btn-confirmar-co:hover{background:var(--gold2)}
.btn-confirmar-co:disabled{opacity:.5;cursor:not-allowed}
.exito{display:none;text-align:center;padding:36px 24px}
.exito-ico{font-size:3.5rem;margin-bottom:14px}
.exito-titulo{font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;margin-bottom:10px}
.exito-txt{font-size:.85rem;color:var(--muted);line-height:1.7;margin-bottom:20px}
.exito-det{background:var(--warm);border-radius:10px;padding:14px;text-align:left;margin-bottom:20px}
.exito-fila{display:flex;justify-content:space-between;font-size:.82rem;padding:3px 0}
.exito-ahorro{background:var(--success-bg);border:1px solid rgba(45,106,79,.3);border-radius:8px;padding:10px;text-align:center;font-size:.85rem;color:var(--success);font-weight:600;margin-top:10px}
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--dark);color:var(--cream);padding:11px 22px;border-radius:50px;font-size:.83rem;font-weight:500;z-index:999;transition:transform .3s;white-space:nowrap;max-width:90vw;text-align:center}
.toast.visible{transform:translateX(-50%) translateY(0)}
@media(max-width:600px){.header{padding:0 4%}.hero{padding:50px 4% 44px}.seccion{padding:28px 4%}.filtros{padding:20px 4% 0}.drawer{width:100%}.f2{grid-template-columns:1fr}.grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}.pcard-img{height:170px}}
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
  <div class="logo">Visión <span>Real</span></div>
  <div style="display:flex;align-items:center;gap:16px">
    <a href="<?=BASE_URL?>/index.php" style="font-size:.8rem;color:var(--muted);text-decoration:none">¿Eres vendedor? →</a>
    <button class="cart-btn" onclick="abrirDrawer()">🛍 Carrito <span class="cart-count" id="cart-count">0</span></button>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="hero-tag">Nueva Colección 2026</div>
  <h1>Moda que te <em>define</em></h1>
  <p>Ropa de calidad para dama y caballero. Encuentra tu estilo en nuestra colección exclusiva.</p>
  <div class="hero-btns">
    <button class="btn-oro" onclick="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})">Ver Catálogo</button>
    <button class="btn-claro" onclick="document.getElementById('catalogo').scrollIntoView({behavior:'smooth'})">Explorar todo</button>
  </div>
</section>

<!-- FILTROS: usa índice numérico -->
<div class="filtros" id="catalogo">
  <span>Filtrar:</span>
  <button class="f-btn activo" onclick="filtrar(0, this)">Todos</button>
  <?php foreach ($catKeys as $i => $catNombre): ?>
  <button class="f-btn" onclick="filtrar(<?= $i + 1 ?>, this)"><?= htmlspecialchars($catNombre) ?></button>
  <?php endforeach; ?>
</div>

<!-- CATÁLOGO -->
<?php
$emojis = ['Dama - Casual'=>'👗','Dama - Formal'=>'👔','Caballero - Casual'=>'👕','Caballero - Formal'=>'🤵','Accesorios'=>'👜'];
foreach ($porCategoria as $idx => $prods):
  $numCat = array_search($idx, $catKeys) + 1;
?>
<section class="seccion" data-cat="<?= $numCat ?>">
  <div class="sec-hdr">
    <h2 class="sec-titulo"><?= htmlspecialchars($idx) ?></h2>
    <div class="sec-linea"></div>
    <span class="sec-cnt"><?= count($prods) ?> prendas</span>
  </div>
  <div class="grid">
    <?php foreach ($prods as $p):
      $img = !empty($p['imagen']) ? BASE_URL.'/assets/img/productos/'.rawurlencode($p['imagen']) : '';
      $emoji = $emojis[$idx] ?? '🧥';
    ?>
    <div class="pcard">
      <div class="pcard-img">
        <span class="pcard-lbl"><?= htmlspecialchars($idx) ?></span>
        <?php if ($img): ?>
          <img src="<?= $img ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" onerror="this.style.display='none'">
          <span style="position:absolute;font-size:3.5rem;z-index:0"><?= $emoji ?></span>
        <?php else: ?>
          <span style="font-size:3.5rem"><?= $emoji ?></span>
        <?php endif; ?>
      </div>
      <div class="pcard-body">
        <div class="pcard-nombre"><?= htmlspecialchars($p['nombre']) ?></div>
        <div class="pcard-precio">$<?= number_format($p['precio'], 0, ',', '.') ?></div>
        <?php if (!empty($p['tallas'])): ?>
          <div class="pcard-tallas-lbl">Tallas disponibles:</div>
          <div class="tallas-chips">
            <?php foreach ($p['tallas'] as $t): ?>
            <span class="talla-chip" title="<?= $t['stock'] ?> uds"><?= htmlspecialchars($t['talla']) ?></span>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="pcard-stock"><?= $p['stock'] ?> disponibles</div>
        <?php endif; ?>
      </div>
      <div class="pcard-pie">
        <?php if (!empty($p['tallas'])): ?>
        <button class="btn-agregar" onclick='abrirTalla(<?= json_encode([
          'id'    => (int)$p['id'],
          'nombre'=> $p['nombre'],
          'precio'=> (float)$p['precio'],
          'stock' => (int)$p['stock'],
          'tallas'=> $p['tallas']
        ]) ?>)'>Elegir Talla y Agregar</button>
        <?php else: ?>
        <button class="btn-agregar" onclick='agregarDirecto(<?= json_encode([
          'id'    => (int)$p['id'],
          'nombre'=> $p['nombre'],
          'precio'=> (float)$p['precio'],
          'stock' => (int)$p['stock']
        ]) ?>)'>+ Agregar al carrito</button>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<!-- MODAL TALLA -->
<div class="modal-fondo" id="modal-talla" onclick="if(event.target===this)cerrarTalla()">
  <div class="modal-talla">
    <div class="modal-talla-hdr">
      <div>
        <div class="modal-talla-titulo" id="mt-nombre"></div>
        <div class="modal-talla-precio" id="mt-precio"></div>
      </div>
      <button class="btn-cerrar" onclick="cerrarTalla()">✕</button>
    </div>
    <div class="modal-talla-cuerpo">
      <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);font-weight:600;margin-bottom:12px">Elige tu talla:</div>
      <div class="talla-grid" id="mt-tallas"></div>
      <div class="cant-row">
        <span class="cant-lbl">Cantidad:</span>
        <button class="cant-btn" onclick="cambiarCant(-1)">−</button>
        <span class="cant-num" id="mt-cant">1</span>
        <button class="cant-btn" onclick="cambiarCant(1)">+</button>
      </div>
      <div class="talla-error" id="mt-error">Por favor selecciona una talla</div>
      <button class="btn-confirmar" onclick="confirmarTalla()">+ Agregar al carrito</button>
    </div>
  </div>
</div>

<!-- DRAWER OVERLAY -->
<div class="drawer-fondo" id="drawer-fondo" onclick="cerrarDrawer()"></div>

<!-- DRAWER CARRITO -->
<div class="drawer" id="drawer">
  <div class="drawer-hdr">
    <span class="drawer-titulo">🛍 Tu Carrito</span>
    <button class="btn-cerrar" onclick="cerrarDrawer()">✕</button>
  </div>
  <div class="drawer-items" id="drawer-items">
    <div class="carrito-vacio"><div style="font-size:3rem;margin-bottom:12px">🛍</div><p>Tu carrito está vacío</p></div>
  </div>
  <div class="drawer-pie" id="drawer-pie" style="display:none">
    <div class="t-fila"><span>Subtotal</span><span id="txt-sub">$0</span></div>
    <div class="t-fila t-desc" id="fila-desc" style="display:none"><span>🎁 <span id="txt-pnombre"></span></span><span id="txt-dval"></span></div>
    <div class="t-total"><span>Total</span><span class="val" id="txt-total">$0</span></div>
    <button class="btn-checkout" onclick="abrirCheckout()">Finalizar Compra →</button>
  </div>
</div>

<!-- MODAL CHECKOUT -->
<div class="checkout-fondo" id="checkout-fondo" onclick="if(event.target===this)cerrarCheckout()">
  <div class="checkout-modal">
    <div class="co-hdr">
      <span class="co-titulo">Finalizar pedido</span>
      <button class="btn-cerrar" onclick="cerrarCheckout()">✕</button>
    </div>
    <div id="pantalla-form" class="co-body">
      <div class="resumen" id="resumen-pedido"></div>
      <div class="promo-aviso" id="promo-aviso">
        <div class="promo-aviso-titulo">🎁 ¡Tienes un descuento especial!</div>
        <div class="promo-aviso-txt" id="promo-aviso-txt"></div>
        <span class="promo-aviso-badge" id="promo-aviso-badge"></span>
      </div>
      <div class="co-error" id="co-error"></div>
      <p style="font-size:.8rem;color:var(--muted);margin-bottom:14px;line-height:1.6">Ingresa tu correo — si eres cliente registrado aplicamos tu descuento automáticamente.</p>
      <div class="fg"><label>Correo Electrónico</label><input type="email" id="c-email" placeholder="tu@email.com" onblur="verificarCliente()"></div>
      <div class="f2">
        <div class="fg"><label>Nombre *</label><input type="text" id="c-nombre" placeholder="Tu nombre"></div>
        <div class="fg"><label>Teléfono</label><input type="tel" id="c-tel" placeholder="300-000-0000"></div>
      </div>
      <div class="fg"><label>Dirección (opcional)</label><input type="text" id="c-dir" placeholder="Tu dirección"></div>
      <div class="fg"><label>Notas</label><input type="text" id="c-notas" placeholder="Talla, color, observaciones..."></div>
      <button class="btn-confirmar-co" id="btn-co" onclick="confirmarCompra()">✓ Confirmar Pedido</button>
    </div>
    <div class="exito" id="pantalla-exito">
      <div class="exito-ico">🎉</div>
      <div class="exito-titulo">¡Pedido registrado!</div>
      <div class="exito-txt">Tu pedido fue recibido. Pronto nos comunicaremos contigo.</div>
      <div class="exito-det" id="exito-det"></div>
      <button class="btn-confirmar-co" onclick="cerrarExito()" style="background:var(--dark);color:var(--cream)">Seguir comprando</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
var BASE_URL = '<?= BASE_URL ?>';
var carrito  = [];
var promo    = null;
var clienteId = null;

// Estado modal talla
var prodActual   = null;
var tallaElegida = null;
var cantElegida  = 1;

// =============================================
// FILTRO — usa número de categoría
// =============================================
function filtrar(numCat, btn) {
  var btns = document.querySelectorAll('.f-btn');
  for (var i = 0; i < btns.length; i++) {
    btns[i].classList.remove('activo');
  }
  btn.classList.add('activo');

  var secciones = document.querySelectorAll('.seccion[data-cat]');
  for (var j = 0; j < secciones.length; j++) {
    if (numCat === 0) {
      secciones[j].style.display = '';
    } else {
      if (parseInt(secciones[j].getAttribute('data-cat')) === numCat) {
        secciones[j].style.display = '';
      } else {
        secciones[j].style.display = 'none';
      }
    }
  }
}

// =============================================
// MODAL TALLA
// =============================================
function abrirTalla(prod) {
  prodActual   = prod;
  tallaElegida = null;
  cantElegida  = 1;
  document.getElementById('mt-nombre').textContent = prod.nombre;
  document.getElementById('mt-precio').textContent = '$' + fmt(prod.precio);
  document.getElementById('mt-cant').textContent   = '1';
  document.getElementById('mt-error').style.display = 'none';

  var html = '';
  for (var i = 0; i < prod.tallas.length; i++) {
    var t = prod.tallas[i];
    var cls = t.stock == 0 ? 'talla-opcion sin-stock' : 'talla-opcion';
    html += '<div class="' + cls + '" data-talla="' + t.talla + '" data-stock="' + t.stock + '" onclick="elegirTalla(this)">';
    html += '<div class="talla-opcion-nombre">' + t.talla + '</div>';
    html += '<div class="talla-opcion-stock">' + (t.stock > 0 ? t.stock + ' uds' : 'Agotada') + '</div>';
    html += '</div>';
  }
  document.getElementById('mt-tallas').innerHTML = html;
  document.getElementById('modal-talla').classList.add('abierto');
}

function elegirTalla(el) {
  if (el.classList.contains('sin-stock')) return;
  var opciones = document.querySelectorAll('.talla-opcion');
  for (var i = 0; i < opciones.length; i++) opciones[i].classList.remove('elegida');
  el.classList.add('elegida');
  tallaElegida = el.getAttribute('data-talla');
  cantElegida  = 1;
  document.getElementById('mt-cant').textContent = '1';
  document.getElementById('mt-error').style.display = 'none';
}

function cambiarCant(delta) {
  if (!tallaElegida) return;
  var maxStock = 1;
  var opciones = document.querySelectorAll('.talla-opcion');
  for (var i = 0; i < opciones.length; i++) {
    if (opciones[i].getAttribute('data-talla') === tallaElegida) {
      maxStock = parseInt(opciones[i].getAttribute('data-stock'));
      break;
    }
  }
  var nuevo = cantElegida + delta;
  if (nuevo < 1 || nuevo > maxStock) return;
  cantElegida = nuevo;
  document.getElementById('mt-cant').textContent = cantElegida;
}

function confirmarTalla() {
  if (!tallaElegida) {
    document.getElementById('mt-error').style.display = 'block';
    return;
  }
  agregarConTalla(prodActual, tallaElegida, cantElegida);
  cerrarTalla();
}

function cerrarTalla() {
  document.getElementById('modal-talla').classList.remove('abierto');
}

// =============================================
// CARRITO
// =============================================
function agregarDirecto(prod) {
  agregarConTalla(prod, null, 1);
}

function agregarConTalla(prod, talla, qty) {
  var key = prod.id + (talla ? '_' + talla : '');
  var encontrado = null;
  for (var i = 0; i < carrito.length; i++) {
    if (carrito[i].key === key) { encontrado = carrito[i]; break; }
  }

  var maxStock = prod.stock;
  if (talla && prod.tallas) {
    for (var j = 0; j < prod.tallas.length; j++) {
      if (prod.tallas[j].talla === talla) { maxStock = prod.tallas[j].stock; break; }
    }
  }

  if (encontrado) {
    if (encontrado.qty + qty > maxStock) { mostrarToast('No hay más stock disponible'); return; }
    encontrado.qty += qty;
  } else {
    carrito.push({ key: key, id: prod.id, nombre: prod.nombre, precio: prod.precio, talla: talla, qty: qty, stock: maxStock });
  }
  renderCarrito();
  var msg = talla ? '✓ ' + prod.nombre + ' (Talla ' + talla + ') agregado' : '✓ ' + prod.nombre + ' agregado';
  mostrarToast(msg);
}

function cambiarQty(idx, delta) {
  var item = carrito[idx];
  var nuevo = item.qty + delta;
  if (nuevo <= 0) { carrito.splice(idx, 1); }
  else if (nuevo > item.stock) { mostrarToast('No hay más stock'); return; }
  else { item.qty = nuevo; }
  renderCarrito();
}

function quitarItem(idx) {
  carrito.splice(idx, 1);
  renderCarrito();
}

function abrirDrawer() {
  document.getElementById('drawer').classList.add('abierto');
  document.getElementById('drawer-fondo').classList.add('abierto');
}
function cerrarDrawer() {
  document.getElementById('drawer').classList.remove('abierto');
  document.getElementById('drawer-fondo').classList.remove('abierto');
}

function renderCarrito() {
  var count = 0;
  for (var i = 0; i < carrito.length; i++) count += carrito[i].qty;
  document.getElementById('cart-count').textContent = count;

  var items = document.getElementById('drawer-items');
  var pie   = document.getElementById('drawer-pie');

  if (!carrito.length) {
    items.innerHTML = '<div class="carrito-vacio"><div style="font-size:3rem;margin-bottom:12px">🛍</div><p>Tu carrito está vacío</p></div>';
    pie.style.display = 'none';
    return;
  }
  pie.style.display = 'block';

  var html = '';
  for (var i = 0; i < carrito.length; i++) {
    var item = carrito[i];
    html += '<div class="item-carrito">';
    html += '<div class="item-ico">' + getEmoji(item.nombre) + '</div>';
    html += '<div class="item-info">';
    html += '<div class="item-nombre">' + item.nombre + '</div>';
    html += '<div class="item-sub">' + (item.talla ? 'Talla: <strong>' + item.talla + '</strong> · ' : '') + '$' + fmt(item.precio) + ' c/u</div>';
    html += '<div class="item-ctrl">';
    html += '<button class="q-btn" onclick="cambiarQty(' + i + ',-1)">−</button>';
    html += '<span class="q-n">' + item.qty + '</span>';
    html += '<button class="q-btn" onclick="cambiarQty(' + i + ',1)">+</button>';
    html += '</div></div>';
    html += '<div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">';
    html += '<div class="item-total">$' + fmt(item.precio * item.qty) + '</div>';
    html += '<button class="item-del" onclick="quitarItem(' + i + ')">✕</button>';
    html += '</div></div>';
  }
  items.innerHTML = html;
  recalcular();
}

function calcDesc(sub) {
  if (!promo) return 0;
  if (promo.tipo === 'porcentaje') return Math.round(sub * promo.valor / 100);
  return Math.min(promo.valor, sub);
}

function recalcular() {
  var sub  = 0;
  for (var i = 0; i < carrito.length; i++) sub += carrito[i].precio * carrito[i].qty;
  var desc = calcDesc(sub);
  var tot  = Math.max(0, sub - desc);
  document.getElementById('txt-sub').textContent   = '$' + fmt(sub);
  document.getElementById('txt-total').textContent = '$' + fmt(tot);
  if (desc > 0 && promo) {
    document.getElementById('txt-pnombre').textContent = promo.nombre;
    document.getElementById('txt-dval').textContent    = '-$' + fmt(desc);
    document.getElementById('fila-desc').style.display = 'flex';
  } else {
    document.getElementById('fila-desc').style.display = 'none';
  }
}

// =============================================
// CHECKOUT
// =============================================
function renderResumen() {
  var sub  = 0;
  for (var i = 0; i < carrito.length; i++) sub += carrito[i].precio * carrito[i].qty;
  var desc = calcDesc(sub);
  var tot  = Math.max(0, sub - desc);

  var html = '<div class="res-titulo">Resumen del pedido</div>';
  for (var i = 0; i < carrito.length; i++) {
    var it = carrito[i];
    html += '<div class="res-item"><span>' + it.nombre + (it.talla ? ' (T.' + it.talla + ')' : '') + ' ×' + it.qty + '</span><span>$' + fmt(it.precio * it.qty) + '</span></div>';
  }
  if (desc > 0 && promo) {
    html += '<div class="res-item res-desc"><span>🎁 ' + promo.nombre + '</span><span>-$' + fmt(desc) + '</span></div>';
  }
  html += '<div class="res-total"><span>Total a pagar</span><span class="val">$' + fmt(tot) + '</span></div>';
  document.getElementById('resumen-pedido').innerHTML = html;
}

function abrirCheckout() {
  if (!carrito.length) return;
  promo = null; clienteId = null;
  document.getElementById('promo-aviso').style.display = 'none';
  renderResumen();
  document.getElementById('pantalla-form').style.display  = 'block';
  document.getElementById('pantalla-exito').style.display = 'none';
  document.getElementById('co-error').style.display       = 'none';
  document.getElementById('c-email').value  = '';
  document.getElementById('c-nombre').value = '';
  document.getElementById('c-tel').value    = '';
  document.getElementById('c-dir').value    = '';
  document.getElementById('c-notas').value  = '';
  document.getElementById('checkout-fondo').classList.add('abierto');
  cerrarDrawer();
}

function cerrarCheckout() {
  document.getElementById('checkout-fondo').classList.remove('abierto');
}

function verificarCliente() {
  var email = document.getElementById('c-email').value.trim();
  promo = null; clienteId = null;
  document.getElementById('promo-aviso').style.display = 'none';
  if (!email) return;

  var xhr = new XMLHttpRequest();
  xhr.open('GET', BASE_URL + '/tienda/api.php?action=buscar_cliente&email=' + encodeURIComponent(email), true);
  xhr.onload = function() {
    if (xhr.status === 200) {
      var cl = JSON.parse(xhr.responseText);
      if (cl && cl.id) {
        clienteId = cl.id;
        if (cl.nombre && !document.getElementById('c-nombre').value) document.getElementById('c-nombre').value = cl.nombre;
        if (cl.telefono && !document.getElementById('c-tel').value)  document.getElementById('c-tel').value   = cl.telefono;

        var xhr2 = new XMLHttpRequest();
        xhr2.open('GET', BASE_URL + '/controllers/PromocionController.php?action=para_cliente&cliente_id=' + cl.id, true);
        xhr2.onload = function() {
          if (xhr2.status === 200) {
            var d = JSON.parse(xhr2.responseText);
            if (d.promociones && d.promociones.length > 0) {
              var mejor = d.promociones[0];
              promo = { id: parseInt(mejor.id), nombre: mejor.nombre, tipo: mejor.tipo, valor: parseFloat(mejor.valor) };
              var etq = mejor.tipo === 'porcentaje' ? mejor.valor + '% de descuento' : '$' + parseInt(mejor.valor).toLocaleString('es-CO') + ' de descuento';
              document.getElementById('promo-aviso-txt').textContent = 'Por tus ' + d.compras + ' compras anteriores, se aplicó "' + mejor.nombre + '" — ' + etq + '.';
              document.getElementById('promo-aviso-badge').textContent = '🎉 ' + etq + ' aplicado';
              document.getElementById('promo-aviso').style.display = 'block';
              mostrarToast('🎁 Descuento aplicado: ' + mejor.nombre);
            } else {
              mostrarToast('👋 Bienvenido de nuevo, ' + cl.nombre);
            }
            recalcular();
            renderResumen();
          }
        };
        xhr2.send();
      }
    }
  };
  xhr.send();
}

function confirmarCompra() {
  var nombre = document.getElementById('c-nombre').value.trim();
  var tel    = document.getElementById('c-tel').value.trim();
  var email  = document.getElementById('c-email').value.trim();
  var dir    = document.getElementById('c-dir').value.trim();
  var notas  = document.getElementById('c-notas').value.trim();
  var errEl  = document.getElementById('co-error');

  if (!nombre) { errEl.textContent = '⚠ El nombre es obligatorio'; errEl.style.display = 'block'; return; }
  errEl.style.display = 'none';

  var sub  = 0;
  for (var i = 0; i < carrito.length; i++) sub += carrito[i].precio * carrito[i].qty;
  var desc = calcDesc(sub);
  var tot  = Math.max(0, sub - desc);

  var btn = document.getElementById('btn-co');
  btn.disabled = true; btn.textContent = 'Procesando...';

  var productos = [];
  for (var i = 0; i < carrito.length; i++) {
    productos.push({ producto_id: carrito[i].id, cantidad: carrito[i].qty, precio: carrito[i].precio, talla: carrito[i].talla || '' });
  }

  var fd = new FormData();
  fd.append('action',       'compra_publica');
  fd.append('nombre',       nombre);
  fd.append('telefono',     tel);
  fd.append('email',        email);
  fd.append('direccion',    dir);
  fd.append('notas',        notas);
  fd.append('promocion_id', promo ? promo.id : 0);
  fd.append('descuento',    desc);
  fd.append('cliente_id',   clienteId || 0);
  fd.append('productos',    JSON.stringify(productos));

  var xhr = new XMLHttpRequest();
  xhr.open('POST', BASE_URL + '/tienda/api.php', true);
  xhr.onload = function() {
    if (xhr.status === 200) {
      var d = JSON.parse(xhr.responseText);
      if (d.success) {
        var det = '<div class="exito-fila"><span>Pedido #</span><strong>' + d.venta_id + '</strong></div>';
        det += '<div class="exito-fila"><span>Cliente</span><strong>' + nombre + '</strong></div>';
        det += '<div class="exito-fila"><span>Total pagado</span><strong style="color:var(--gold)">$' + fmt(tot) + '</strong></div>';
        if (desc > 0) det += '<div class="exito-ahorro">🎉 ¡Ahorraste $' + fmt(desc) + ' en esta compra!</div>';
        document.getElementById('exito-det').innerHTML = det;
        document.getElementById('pantalla-form').style.display  = 'none';
        document.getElementById('pantalla-exito').style.display = 'block';
        carrito = []; promo = null; clienteId = null;
        renderCarrito();
      } else {
        errEl.textContent = '⚠ ' + (d.error || 'Error. Intenta de nuevo.');
        errEl.style.display = 'block';
      }
    }
    btn.disabled = false; btn.textContent = '✓ Confirmar Pedido';
  };
  xhr.send(fd);
}

function cerrarExito() { cerrarCheckout(); }

// =============================================
// UTILS
// =============================================
function fmt(v) {
  return Math.round(v).toLocaleString('es-CO');
}

function getEmoji(n) {
  n = n.toLowerCase();
  if (n.indexOf('blusa') >= 0 || n.indexOf('vestido') >= 0) return '👗';
  if (n.indexOf('pantalón') >= 0 || n.indexOf('jean') >= 0) return '👖';
  if (n.indexOf('camisa') >= 0) return '👔';
  if (n.indexOf('cinturón') >= 0) return '🪢';
  if (n.indexOf('bolso') >= 0) return '👜';
  return '🧥';
}

var toastTimer;
function mostrarToast(msg) {
  var t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('visible');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(function() { t.classList.remove('visible'); }, 2500);
}
</script>
</body>
</html>
