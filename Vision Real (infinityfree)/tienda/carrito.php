<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/session_tienda.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/tienda/Carrito.php';

requireTiendaLogin();
$cliente = getTiendaCliente();
$carrito = new Carrito();
$contenido = $carrito->getContenido((int) $cliente['id']);
$items = $contenido['items'] ?? [];
$total = (float) ($contenido['subtotal'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Carrito — Tienda Vision Real</title>
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/miestilo.css">
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/tienda.css">
</head>
<body class="store-body">
<header class="store-header">
  <div class="store-brand"><a href="<?=BASE_URL?>/tienda/index.php">Visión Real</a></div>
  <div class="store-nav">
    <a href="<?=BASE_URL?>/tienda/index.php">Inicio</a>
    <a href="<?=BASE_URL?>/tienda/carrito.php">Carrito</a>
  </div>
</header>
<main class="checkout-main">
  <section class="checkout-section">
    <h1>Carrito de Compras</h1>
    <?php if(empty($items)): ?>
      <div class="alert alert-info">Tu carrito está vacío.</div>
    <?php else: ?>
      <div class="cart-table">
        <?php foreach($items as $item): ?>
          <div class="cart-item">
            <div class="cart-item-thumb" style="background-image:url('<?=Producto::getImageUrl($item, BASE_URL)?>')"></div>
            <div class="cart-item-body">
              <div class="cart-item-title"><?=htmlspecialchars($item['nombre'])?></div>
              <div class="cart-item-meta">Talla: <?=htmlspecialchars($item['talla'] ?: 'Única')?></div>
              <div class="cart-item-meta">Precio: $<?=number_format($item['precio_unitario'],0,',','.')?></div>
              <div class="cart-item-meta">Sub-total: $<?=number_format($item['subtotal_item'],0,',','.')?></div>
              <div class="cart-item-actions">
                <input type="number" min="1" max="<?=intval($item['stock_disponible'])?>" value="<?=intval($item['cantidad'])?>" data-item-id="<?=$item['id']?>" class="qty-input">
                <button class="btn btn-secondary btn-small remove-item" data-item-id="<?=$item['id']?>">Eliminar</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="checkout-summary">
        <div>
          <div class="summary-row"><span>Total</span><strong>$<?=number_format($total,0,',','.')?></strong></div>
        </div>
        <div class="checkout-form">
          <h2>Confirmar compra</h2>
          <label>Nombre</label>
          <input type="text" id="nombre" placeholder="Nombre completo">
          <label>Teléfono</label>
          <input type="text" id="telefono" placeholder="Teléfono o documento">
          <label>Email</label>
          <input type="email" id="email" placeholder="correo@ejemplo.com">
          <label>Dirección</label>
          <textarea id="direccion" placeholder="Dirección de entrega"></textarea>
          <label>Tipo de entrega</label>
          <select id="tipo_entrega">
            <option value="domicilio">Domicilio</option>
            <option value="recoge_tienda">Recoger en tienda</option>
          </select>
          <label>Notas</label>
          <textarea id="notas" placeholder="Instrucciones adicionales"></textarea>
          <button class="btn btn-primary" id="btnCheckout">Pagar y crear pedido</button>
          <div id="checkout-msg" class="product-alert" style="display:none"></div>
        </div>
      </div>
    <?php endif; ?>
  </section>
</main>
<script src="<?=BASE_URL?>/assets/js/tienda.js"></script>
<script>
document.querySelectorAll('.qty-input').forEach(input => {
  input.addEventListener('change', function(){
    const itemId = this.dataset.itemId;
    const cantidad = this.value;
    fetch('<?=BASE_URL?>/api/tienda/carrito.php', {
      method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({action:'update', item_id:itemId, cantidad:cantidad})
    }).then(r=>r.json()).then(()=> location.reload());
  });
});
document.querySelectorAll('.remove-item').forEach(btn => btn.addEventListener('click', function(){
  const itemId = this.dataset.itemId;
  fetch('<?=BASE_URL?>/api/tienda/carrito.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'delete', item_id:itemId})
  }).then(r=>r.json()).then(()=> location.reload());
}));

const btnCheckout = document.getElementById('btnCheckout');
if (btnCheckout) {
  btnCheckout.addEventListener('click', function(){
    const data = new URLSearchParams({
      nombre: document.getElementById('nombre').value,
      telefono: document.getElementById('telefono').value,
      email: document.getElementById('email').value,
      direccion: document.getElementById('direccion').value,
      tipo_entrega: document.getElementById('tipo_entrega').value,
      notas: document.getElementById('notas').value
    });
    fetch('<?=BASE_URL?>/api/tienda/checkout.php', {
      method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: data
    }).then(r=>r.json()).then(json => {
      const msg = document.getElementById('checkout-msg');
      msg.style.display = 'block';
      if (json.success) {
        msg.textContent = 'Pedido creado con éxito. ID: ' + json.pedido_id;
        msg.style.background = 'rgba(39,174,96,0.12)';
        setTimeout(() => window.location.href = '<?=BASE_URL?>/tienda/index.php', 1800);
      } else {
        msg.textContent = json.error || 'Error al procesar el pedido';
        msg.style.background = 'rgba(192,57,43,0.15)';
      }
    });
  });
}
</script>
</body>
</html>
