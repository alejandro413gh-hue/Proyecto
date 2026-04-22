<?php
$pageTitle = 'Agregar Inventario';
require_once __DIR__ . '/../config/config.php';
requireLogin();
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Categoria.php';

$pm = new Producto();
$cm = new Categoria();
$categorias = $cm->getAll();
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <h1 class="page-title">Agregar Inventario por Código</h1>
    </div>
  </header>

  <div class="content">
    <div class="card" style="max-width:600px;margin:0 auto">
      <div class="card-header">
        <span class="card-title">📦 Agregar o Actualizar Inventario</span>
      </div>
      <div class="card-body">
        <form id="form-inventario">
          <input type="hidden" name="action" value="agregar_inventario">
          <div class="form-group">
            <label>Código del Producto *</label>
            <input type="text" id="codigo" name="codigo" placeholder="Ingresa el código..." required>
            <small style="color:var(--white-muted);font-size:.75rem">Si el código existe, se autocompletará el nombre.</small>
          </div>
          <div class="form-group">
            <label>Nombre del Producto</label>
            <input type="text" id="nombre" name="nombre" placeholder="Se autocompletará si el código existe">
          </div>
          <div class="form-group">
            <label>Cantidad a Agregar *</label>
            <input type="number" name="cantidad" min="1" required>
          </div>
          <div class="form-group">
            <label>Talla (opcional)</label>
            <input type="text" name="talla" placeholder="Ej: M, XL, 32...">
            <small style="color:var(--white-muted);font-size:.75rem">Deja vacío si no aplica tallas.</small>
          </div>
          <div class="form-group">
            <label>Precio (COP) - opcional</label>
            <input type="number" name="precio" min="0" step="0.01" placeholder="Actualiza solo si deseas cambiar">
            <small style="color:var(--white-muted);font-size:.75rem">Deja vacío para mantener precio actual.</small>
          </div>
          <!-- Campos para nuevo producto -->
          <div id="campos-nuevo" style="display:none;border-top:1px solid var(--border);padding-top:16px;margin-top:16px">
            <div class="form-group">
              <label>Descripción</label>
              <textarea name="descripcion" placeholder="Descripción del producto"></textarea>
            </div>
            <div class="form-group">
              <label>Categoría</label>
              <select name="categoria_id">
                <option value="0">— Sin categoría —</option>
                <?php foreach($categorias as $c): ?>
                <option value="<?=$c['id']?>"><?=htmlspecialchars($c['nombre'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
            <button type="button" class="btn btn-outline" onclick="limpiar()">Limpiar</button>
            <button type="submit" class="btn btn-primary">✓ Agregar Inventario</button>
          </div>
        </form>
      </div>
    </div>

    <div id="toast-container"></div>
  </div>
</div>
</div>

<script>
const CTRL_INVENTARIO = '<?=BASE_URL?>/controllers/InventarioController.php';

document.getElementById('codigo').addEventListener('input', function() {
    const codigo = this.value.trim();
    if (codigo.length < 3) return; // Evitar búsquedas innecesarias

    fetch(CTRL_INVENTARIO + '?action=buscar_codigo&codigo=' + encodeURIComponent(codigo))
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('nombre').value = d.producto.nombre;
            document.getElementById('campos-nuevo').style.display = 'none';
            document.querySelector('[name="nombre"]').required = false;
        } else {
            document.getElementById('nombre').value = '';
            document.getElementById('campos-nuevo').style.display = 'block';
            document.querySelector('[name="nombre"]').required = true;
        }
    })
    .catch(e => console.error('Error:', e));
});

document.getElementById('form-inventario').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch(CTRL_INVENTARIO, {method: 'POST', body: fd})
    .then(r => r.json())
    .then(d => {
        mostrarToast(d.success ? 'success' : 'error', d.success ? d.success : d.error);
        if (d.success) {
            limpiar();
        }
    })
    .catch(e => {
        mostrarToast('error', 'Error de conexión');
    });
});

function limpiar() {
    document.getElementById('form-inventario').reset();
    document.getElementById('campos-nuevo').style.display = 'none';
    document.querySelector('[name="nombre"]').required = false;
}

function mostrarToast(tipo, mensaje) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + tipo;
    toast.innerHTML = '<span>' + mensaje + '</span><button onclick="this.parentElement.remove()">✕</button>';
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}

const mt = document.getElementById('menu-toggle'), sb = document.getElementById('sidebar');
if (mt && sb) mt.addEventListener('click', () => sb.classList.toggle('open'));
</script>
</body></html>