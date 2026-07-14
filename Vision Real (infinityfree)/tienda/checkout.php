<?php
/**
 * tienda/checkout.php
 * Proceso de compra: resumen + datos de envío + descuento + confirmar.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/session_tienda.php';
requireTiendaLogin();

require_once __DIR__ . '/../models/tienda/Carrito.php';
require_once __DIR__ . '/../models/tienda/ClienteOnline.php';

$carritoM  = new Carrito();
$clienteM  = new ClienteOnline();
$cliente   = getTiendaCliente();
$perfil    = $clienteM->getById($cliente['id']);
$contenido = $carritoM->getContenido($cliente['id']);
$storeAddress = defined('STORE_PHYSICAL_ADDRESS') ? (string) STORE_PHYSICAL_ADDRESS : '';
$storeMapsUrl = defined('STORE_MAPS_URL') ? (string) STORE_MAPS_URL : '';
$storeMapsEmbed = defined('STORE_MAPS_EMBED_URL') ? (string) STORE_MAPS_EMBED_URL : '';
$storeWhatsapp = defined('STORE_WHATSAPP_NUMBER') ? (string) STORE_WHATSAPP_NUMBER : '';
$storeMapsUrl = trim($storeMapsUrl);
$storeMapsEmbed = trim($storeMapsEmbed);

// Redirigir si carrito vacío
if (empty($contenido['items'])) {
    header('Location: ' . BASE_URL . '/tienda/catalogo.php');
    exit();
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Checkout — Visión Real</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="<?= BASE_URL ?>/tienda/assets/css/tienda.css" rel="stylesheet">
</head>
<body>

<nav class="vr-navbar navbar sticky-top">
  <div class="container">
    <a class="navbar-brand vr-brand" href="<?= BASE_URL ?>/tienda/">
      <span class="brand-name">Visión Real</span>
      <span class="brand-sub">Checkout</span>
    </a>
    <a href="<?= BASE_URL ?>/tienda/catalogo.php" class="btn btn-outline-light btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Seguir comprando
    </a>
  </div>
</nav>

<div class="container py-5">
  <div class="row g-4">

    <!-- ══ Formulario ══ -->
    <div class="col-lg-7">

      <!-- Datos de entrega -->
      <div class="checkout-card mb-4">
        <h5 class="fw-bold mb-4"><i class="bi bi-geo-alt me-2"></i>Datos de entrega</h5>

        <!-- Tipo de entrega -->
        <div class="mb-4">
          <label class="fw-semibold mb-2 d-block">Tipo de entrega</label>
          <div class="row g-2">
            <div class="col-6">
              <input type="radio" class="btn-check" name="tipo_entrega" id="optDomicilio" value="domicilio" checked>
              <label class="btn btn-outline-secondary w-100 text-start" for="optDomicilio" style="border-radius:10px;padding:14px">
                <i class="bi bi-truck me-2"></i><strong>Domicilio</strong>
                <small class="d-block text-muted mt-1">Enviamos a tu dirección</small>
              </label>
            </div>
            <div class="col-6">
              <input type="radio" class="btn-check" name="tipo_entrega" id="optTienda" value="recoge_tienda">
              <label class="btn btn-outline-secondary w-100 text-start" for="optTienda" style="border-radius:10px;padding:14px">
                <i class="bi bi-shop me-2"></i><strong>Recoger en tienda</strong>
                <small class="d-block text-muted mt-1">Sin costo de envío</small>
              </label>
            </div>
          </div>
        </div>

        <!-- Campos domicilio -->
        <div id="camposDomicilio">
          <div class="row g-3 form-tienda">
            <div class="col-md-6">
              <label>Nombre completo *</label>
              <input type="text" class="form-control" id="envioNombre"
                     value="<?= htmlspecialchars($perfil['nombre'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label>Teléfono *</label>
              <input type="text" class="form-control" id="envioTelefono"
                     value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>" required>
            </div>
            <div class="col-12">
              <label>Dirección completa *</label>
              <input type="text" class="form-control" id="envioDireccion"
                     value="<?= htmlspecialchars($perfil['direccion'] ?? '') ?>" placeholder="Calle, número, barrio…">
            </div>
            <div class="col-md-6">
              <label>Ciudad *</label>
              <input type="text" class="form-control" id="envioCiudad"
                     value="<?= htmlspecialchars($perfil['ciudad'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label>Notas del pedido (opcional)</label>
              <textarea class="form-control" id="enviNotas" rows="2" placeholder="Instrucciones especiales, color, detalles…"></textarea>
            </div>
          </div>
        </div>

        <!-- Campos recoge en tienda -->
        <div id="camposTienda" style="display:none">
          <div class="row g-3 form-tienda">
            <div class="col-md-6">
              <label>Nombre para recoger *</label>
              <input type="text" class="form-control" id="recogeNombre"
                     value="<?= htmlspecialchars($perfil['nombre'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label>Teléfono *</label>
              <input type="text" class="form-control" id="recogeTelefono"
                     value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>">
            </div>
            <div class="alert alert-info mt-2">
              <i class="bi bi-info-circle me-2"></i>
              Te avisaremos cuando tu pedido esté listo para recoger.
            </div>
            <div class="col-12">
              <div style="padding:18px;border:1px solid rgba(0,0,0,.08);border-radius:16px;background:linear-gradient(180deg,#fff 0%,#f8f6ef 100%);">
                <div class="fw-bold mb-2" style="font-size:1rem;color:#1f2937">Retiro en tienda</div>
                <div style="font-size:.9rem;color:#4b5563;line-height:1.6;margin-bottom:12px">
                  <?= htmlspecialchars($storeAddress ?: 'La dirección del local se configurará desde el panel de administración.') ?>
                </div>
                <?php if (!empty($storeMapsUrl)): ?>
                  <a href="<?= htmlspecialchars($storeMapsUrl) ?>" class="btn btn-gold btn-sm mb-3" target="_blank" rel="noopener">
                    <i class="bi bi-geo-alt me-1"></i>Abrir en Google Maps
                  </a>
                  <?php if (!empty($storeMapsEmbed)): ?>
                    <div class="ratio ratio-16x9 rounded overflow-hidden border">
                      <iframe src="<?= htmlspecialchars($storeMapsEmbed) ?>" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    </div>
                  <?php endif; ?>
                <?php else: ?>
                  <div class="alert alert-warning mb-0">
                    El administrador debe registrar las coordenadas de la tienda para mostrar el mapa.
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Método de pago -->
      <div class="checkout-card mb-4">
        <h5 class="fw-bold mb-4"><i class="bi bi-credit-card me-2"></i>Método de pago</h5>
        <div class="row g-2 mb-3">
          <div class="col-4">
            <input type="radio" class="btn-check" name="metodo_pago" id="pagoTransferencia" value="transferencia" checked>
            <label class="btn btn-outline-secondary w-100 text-center" for="pagoTransferencia" style="border-radius:10px;padding:12px;font-size:.82rem">
              <i class="bi bi-bank d-block fs-4 mb-1"></i>Transferencia
            </label>
          </div>
          <div class="col-4">
            <input type="radio" class="btn-check" name="metodo_pago" id="pagoNequi" value="nequi">
            <label class="btn btn-outline-secondary w-100 text-center" for="pagoNequi" style="border-radius:10px;padding:12px;font-size:.82rem">
              <i class="bi bi-phone d-block fs-4 mb-1"></i>Nequi
            </label>
          </div>
          <div class="col-4">
            <input type="radio" class="btn-check" name="metodo_pago" id="pagoContraentrega" value="contraentrega">
            <label class="btn btn-outline-secondary w-100 text-center" for="pagoContraentrega" style="border-radius:10px;padding:12px;font-size:.82rem">
              <i class="bi bi-cash-stack d-block fs-4 mb-1"></i>Contraentrega
            </label>
          </div>
        </div>
        <div id="checkoutMetodoPagoInfo" class="alert alert-light border" style="font-size:.82rem">
          <i class="bi bi-whatsapp text-success me-1"></i>
          Una vez confirmado el pedido, te enviaremos los datos de pago por WhatsApp.
        </div>
        <div class="alert alert-light border" style="font-size:.82rem">
          <div class="fw-semibold mb-1">Contacto de la tienda</div>
          <div>WhatsApp: <?= htmlspecialchars($storeWhatsapp ?: 'No configurado') ?></div>
          <div>Correo: <?= htmlspecialchars(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '') ?></div>
          <?php if (!empty($storeAddress)): ?>
            <div>Dirección: <?= htmlspecialchars($storeAddress) ?></div>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- /col form -->

    <!-- ══ Resumen del pedido ══ -->
    <div class="col-lg-5">
      <div class="checkout-resumen sticky-top" style="top:80px">
        <h5 class="fw-bold mb-4">Resumen del pedido</h5>

        <!-- Items -->
        <?php foreach ($contenido['items'] as $item):
          $imgUrl = !empty($item['imagen'])
            ? BASE_URL . '/assets/img/productos/' . $item['imagen']
            : BASE_URL . '/tienda/assets/img/sin-imagen.svg';
        ?>
        <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
          <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($item['nombre']) ?>"
               style="width:56px;height:56px;object-fit:cover;border-radius:8px;background:#f5f5f5">
          <div class="flex-grow-1">
            <p class="mb-0 fw-semibold" style="font-size:.88rem"><?= htmlspecialchars($item['nombre']) ?></p>
            <?php if ($item['talla']): ?>
              <p class="mb-0 text-muted" style="font-size:.78rem">Talla: <?= htmlspecialchars($item['talla']) ?></p>
            <?php endif; ?>
            <p class="mb-0 text-muted" style="font-size:.78rem">× <?= $item['cantidad'] ?></p>
          </div>
          <div class="text-end">
            <span class="fw-bold" style="font-size:.9rem;color:var(--gold-dark)">
              $<?= number_format($item['subtotal_item'], 0, ',', '.') ?>
            </span>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Descuento -->
        <div id="descuentoInfo" class="mb-2"></div>
        <input type="hidden" id="descuentoId" value="">

        <!-- Totales -->
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Subtotal</span>
          <span id="checkoutSubtotal" data-valor="<?= $contenido['subtotal'] ?>">
            $<?= number_format($contenido['subtotal'], 0, ',', '.') ?>
          </span>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="text-muted">Descuento</span>
          <span id="checkoutDescuento" class="text-success">$0</span>
        </div>
        <div class="d-flex justify-content-between mb-3 pt-2 border-top">
          <strong class="fs-5">Total</strong>
          <strong class="fs-5" id="checkoutTotal" style="color:var(--gold-dark)">
            $<?= number_format($contenido['subtotal'], 0, ',', '.') ?>
          </strong>
        </div>

        <!-- Botón confirmar -->
        <button class="btn btn-gold w-100 btn-lg" id="btnConfirmar" onclick="confirmarPedido()">
          <i class="bi bi-check-circle me-2"></i>Confirmar pedido
        </button>
        <p class="text-center text-muted mt-2" style="font-size:.75rem">
          <i class="bi bi-shield-check me-1"></i>Tus datos están seguros.
        </p>
      </div>
    </div>

  </div><!-- /row -->
</div>

<!-- Modal de éxito -->
<div class="modal fade" id="modalExito" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center p-4">
      <div style="font-size:4rem;margin-bottom:16px">🎉</div>
      <h4 class="fw-bold">¡Pedido confirmado!</h4>
      <p class="text-muted" id="modalMensaje">Tu pedido ha sido recibido. Te contactaremos pronto.</p>
      <p><strong>Número de pedido:</strong> <span id="modalNumeroPedido" class="text-primary fw-bold"></span></p>
      <a href="<?= BASE_URL ?>/tienda/mis-pedidos.php" class="btn btn-gold w-100 mb-2">Ver mis pedidos</a>
      <a href="<?= BASE_URL ?>/tienda/" class="btn btn-outline-secondary w-100">Seguir comprando</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
const VR = { baseUrl: '<?= BASE_URL ?>', loggedIn: true };
const STORE_WHATSAPP_NUMBER = '<?= defined("STORE_WHATSAPP_NUMBER") ? STORE_WHATSAPP_NUMBER : "" ?>';
window.VR_STORE_WHATSAPP_NUMBER = STORE_WHATSAPP_NUMBER;
</script>
<script src="<?= BASE_URL ?>/tienda/assets/js/tienda.js?v=3" defer></script>
<script>

// Cambiar entre domicilio y recogida en tienda
document.querySelectorAll('input[name="tipo_entrega"]').forEach(r => {
  r.addEventListener('change', () => {
    const esDomicilio = r.value === 'domicilio';
    document.getElementById('camposDomicilio').style.display = esDomicilio ? 'block' : 'none';
    document.getElementById('camposTienda').style.display    = esDomicilio ? 'none'  : 'block';
  });
});

// Calcular descuentos y cargar datos de pago al cargar
window.addEventListener('DOMContentLoaded', () => {
  calcularDescuento();
  cargarDatosPagoCheckout();
});

document.querySelectorAll('input[name="metodo_pago"]').forEach(radio => {
  radio.addEventListener('change', () => cargarDatosPagoCheckout());
});

async function cargarDatosPagoCheckout() {
  const metodoPago = document.querySelector('input[name="metodo_pago"]:checked').value;
  const contenedor = document.getElementById('checkoutMetodoPagoInfo');
  contenedor.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cargando datos de pago...';

  try {
    const datos = await apiGet(VR.baseUrl + '/api/tienda/pagos.php?action=get');
    const config = datos.config || {};
    let html = '';

    if (metodoPago === 'nequi') {
      const nequi = config.nequi || {};
      html = `
        <strong>Nequi</strong><br>
        ${nequi.numero ? `Número: <strong>${nequi.numero}</strong><br>` : 'Número de Nequi no disponible.<br>'}
        ${nequi.instrucciones ? `${nequi.instrucciones}<br>` : ''}
        ${nequi.qr_url ? `<img src="${nequi.qr_url}" alt="QR Nequi" style="max-width:180px;display:block;margin-top:10px;border-radius:12px;">` : ''}`;
    } else if (metodoPago === 'transferencia') {
      const ban = config.transferencia || {};
      html = `
        <strong>Transferencia bancaria</strong><br>
        ${ban.banco ? `Banco: <strong>${ban.banco}</strong><br>` : ''}
        ${ban.titular ? `Titular: <strong>${ban.titular}</strong><br>` : ''}
        ${ban.cuenta ? `Cuenta: <strong>${ban.cuenta}</strong><br>` : ''}
        ${ban.tipo_cuenta ? `Tipo de cuenta: <strong>${ban.tipo_cuenta}</strong><br>` : ''}
        ${ban.instrucciones ? `${ban.instrucciones}<br>` : ''}
        ${ban.qr_url ? `<img src="${ban.qr_url}" alt="QR Transferencia" style="max-width:180px;display:block;margin-top:10px;border-radius:12px;">` : ''}`;
    } else {
      html = `
        <strong>Contraentrega</strong><br>
        El pedido se genera automáticamente y podrás imprimir la factura para enviar al domiciliario.<br>
        Recibirás un resumen del pedido y el estado por WhatsApp.`;
    }

    contenedor.innerHTML = html;
  } catch (err) {
    contenedor.innerHTML = 'No se pudo cargar la información de pago. Intenta de nuevo más tarde.';
  }
}

async function confirmarPedido() {
  const btn = document.getElementById('btnConfirmar');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando…';

  const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked').value;
  const metodoPago  = document.querySelector('input[name="metodo_pago"]:checked').value;
  const esDomicilio = tipoEntrega === 'domicilio';

  const nombre   = esDomicilio ? document.getElementById('envioNombre').value.trim()
                               : document.getElementById('recogeNombre').value.trim();
  const telefono = esDomicilio ? document.getElementById('envioTelefono').value.trim()
                               : document.getElementById('recogeTelefono').value.trim();
  const direccion = esDomicilio ? document.getElementById('envioDireccion').value.trim() : '';
  const ciudad    = esDomicilio ? document.getElementById('envioCiudad').value.trim() : '';
  const notas     = document.getElementById('enviNotas')?.value?.trim() ?? '';
  const descuentoId = document.getElementById('descuentoId').value;

  if (!nombre || !telefono) {
    toast('Completa nombre y teléfono', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Confirmar pedido';
    return;
  }
  if (esDomicilio && !direccion) {
    toast('Ingresa la dirección de entrega', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Confirmar pedido';
    return;
  }

  try {
    const fd = new FormData();
    fd.append('action',       'crear');
    fd.append('nombre',       nombre);
    fd.append('telefono',     telefono);
    fd.append('direccion',    direccion);
    fd.append('ciudad',       ciudad);
    fd.append('notas',        notas);
    fd.append('tipo_entrega', tipoEntrega);
    fd.append('metodo_pago',  metodoPago);
    if (descuentoId) fd.append('descuento_id', descuentoId);

    const r = await fetch(VR.baseUrl + '/api/tienda/pedidos.php', { method: 'POST', body: fd });
    const data = await r.json();

    if (data.success) {
      document.getElementById('modalNumeroPedido').textContent = data.numero;
      document.getElementById('modalMensaje').textContent =
        `Total: $${Number(data.total).toLocaleString('es-CO')} — Método de pago: ${metodoPago}`;
      new bootstrap.Modal(document.getElementById('modalExito')).show();

      // Si es contraentrega y viene URL de factura pública, abrir en nueva pestaña para imprimir
      if (metodoPago === 'contraentrega' && data.factura_url) {
        try { window.open(data.factura_url, '_blank'); } catch(e) { /* noop */ }
      }

      // Abrir chat de WhatsApp (wa.me) hacia la tienda para que el cliente notifique
      // Usa STORE_WHATSAPP_NUMBER si está disponible, sino usa número de Nequi si existe en config
      try {
        const cfg = await apiGet(VR.baseUrl + '/api/tienda/pagos.php?action=get');
        const storeNumber = cfg.config && cfg.config.whatsapp_store_number ? cfg.config.whatsapp_store_number : '';
        // Fallback to server constant exposed via endpoint? we'll use a global value injected server-side
        const configuredNumber = window.STORE_WHATSAPP_NUMBER || storeNumber || '';
        const recipient = configuredNumber || '';
        if (recipient) {
          const lista = (data.detalle || []).map(i => `${i.producto_nombre} x${i.cantidad}`).join(', ');
          const msg = encodeURIComponent(`Hola, he realizado un pedido ${data.numero}. Total: ${data.total}. Productos: ${lista}. Dirección: ${direccion || ''}`);
          const waUrl = `https://wa.me/${recipient}?text=${msg}`;
          window.open(waUrl, '_blank');
        }
      } catch (e) {
        // ignore
      }
    } else {
      toast(data.error || 'Error al procesar el pedido', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Confirmar pedido';
    }
  } catch {
    toast('Error de conexión. Intenta de nuevo.', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Confirmar pedido';
  }
}
</script>
</body>
</html>
