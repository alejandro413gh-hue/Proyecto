<?php
$pageTitle = 'Información de la Tienda';
require_once __DIR__ . '/../config/config.php';
requireLogin();
if (!isAdmin()) {
    header('Location: ' . BASE_URL . '/views/dashboard.php');
    exit();
}
require_once __DIR__ . '/../models/TiendaConfig.php';

$configModel = new TiendaConfig();
$config = $configModel->getAll();
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'guardar_info') {
        $whatsapp = $configModel->normalizeWhatsapp(trim((string) ($_POST['whatsapp_number'] ?? '')));
        $email = trim((string) ($_POST['support_email'] ?? ''));
        $address = trim((string) ($_POST['physical_address'] ?? ''));
        $latitude = trim((string) ($_POST['latitude'] ?? ''));
        $longitude = trim((string) ($_POST['longitude'] ?? ''));
        $mapsUrl = trim((string) ($_POST['google_maps_url'] ?? ''));

        if ($whatsapp === '') {
            $error = 'El número de WhatsApp es obligatorio.';
        } elseif (strlen($whatsapp) < 8 || strlen($whatsapp) > 15) {
            $error = 'El número de WhatsApp debe tener entre 8 y 15 dígitos.';
        } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Debes ingresar un correo electrónico válido.';
        } elseif ($address === '') {
            $error = 'La dirección física es obligatoria.';
        } else {
            if ($mapsUrl !== '' && ($latitude === '' || $longitude === '')) {
                [$latitude, $longitude] = $configModel->extractCoordinatesFromMapsUrl($mapsUrl);
            }

            if ($mapsUrl === '' && ($latitude === '' || $longitude === '')) {
                $error = 'Debes ingresar al menos un enlace de Google Maps o las coordenadas de ubicación.';
            } elseif ($latitude !== '' && !$configModel->validateLatitude($latitude)) {
                $error = 'La latitud no tiene un formato válido.';
            } elseif ($longitude !== '' && !$configModel->validateLongitude($longitude)) {
                $error = 'La longitud no tiene un formato válido.';
            } else {
                $finalMapsUrl = $mapsUrl !== ''
                    ? $mapsUrl
                    : $configModel->buildMapsUrl($latitude, $longitude);
                $save = $configModel->save([
                    'store_name' => $config['store_name'] ?? 'Visión Real',
                    'whatsapp_number' => $whatsapp,
                    'support_email' => $email,
                    'physical_address' => $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'google_maps_url' => $finalMapsUrl,
                ]);

                if (isset($save['success'])) {
                    $msg = 'La información de la tienda se actualizó correctamente.';
                    $config = $configModel->getAll();
                } else {
                    $error = $save['error'] ?? 'No se pudo guardar la información.';
                }
            }
        }
    }
}

$mapOpenUrl = $configModel->buildMapsUrl($config['latitude'] ?? '', $config['longitude'] ?? '', $config['google_maps_url'] ?? '');
$mapEmbedUrl = '';
if (!empty($config['latitude']) && !empty($config['longitude'])) {
    $mapEmbedUrl = $configModel->buildMapsEmbedUrl($config['latitude'] ?? '', $config['longitude'] ?? '');
} elseif (!empty($config['google_maps_url'])) {
    $mapEmbedUrl = $configModel->buildMapsEmbedFromUrl($config['google_maps_url']);
}
include __DIR__ . '/partials/head.php';
?>
<div class="layout">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="menu-toggle" id="menu-toggle">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <h1 class="page-title">Información de la Tienda</h1>
    </div>
    <div class="topbar-right">
      <span class="badge-gold">Configuración central</span>
    </div>
  </header>

  <div class="content">
    <?php if ($msg): ?>
      <div class="alert alert-success" style="margin-bottom:20px">✓ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom:20px">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:24px">
      <div class="card-header">
        <span class="card-title">Gestión centralizada de contacto y ubicación</span>
      </div>
      <div class="card-body" style="padding:24px">
        <div class="row g-4">
          <div class="col-lg-6">
            <form method="POST" class="row g-3">
              <input type="hidden" name="action" value="guardar_info">

              <div class="col-12">
                <label class="form-label fw-semibold">Número de WhatsApp *</label>
                <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars($config['whatsapp_number'] ?? '') ?>" placeholder="573125420576" required>
                <small class="text-muted">Solo números. Ejemplo: 573125420576</small>
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold">Correo electrónico *</label>
                <input type="email" name="support_email" class="form-control" value="<?= htmlspecialchars($config['support_email'] ?? '') ?>" placeholder="tienda@ejemplo.com" required>
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold">Dirección física *</label>
                <textarea name="physical_address" class="form-control" rows="3" placeholder="Dirección completa del local" required><?= htmlspecialchars($config['physical_address'] ?? '') ?></textarea>
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold">Enlace de Google Maps (opcional)</label>
                <input type="url" name="google_maps_url" class="form-control" value="<?= htmlspecialchars($config['google_maps_url'] ?? '') ?>" placeholder="https://www.google.com/maps?q=...">
                <small class="text-muted">Con pegar el enlace es suficiente. Las coordenadas son opcionales.</small>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Latitud</label>
                <input type="text" name="latitude" id="latitude" class="form-control" value="<?= htmlspecialchars($config['latitude'] ?? '') ?>" placeholder="7.8318385">
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Longitud</label>
                <input type="text" name="longitude" id="longitude" class="form-control" value="<?= htmlspecialchars($config['longitude'] ?? '') ?>" placeholder="-72.4764455">
              </div>

              <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">✓ Guardar cambios</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="centrarMapa()">Actualizar vista previa</button>
              </div>
            </form>
          </div>

          <div class="col-lg-6">
            <div class="card p-4" style="border-radius:18px; background:var(--bg-panel); border:1px solid var(--border)">
              <h5 class="fw-bold mb-3">Vista previa</h5>
              <div style="display:grid;gap:12px;margin-bottom:16px">
                <div><strong>WhatsApp:</strong> <?= htmlspecialchars($config['whatsapp_number'] ?? '') ?></div>
                <div><strong>Correo:</strong> <?= htmlspecialchars($config['support_email'] ?? '') ?></div>
                <div><strong>Dirección:</strong> <?= htmlspecialchars($config['physical_address'] ?? '') ?></div>
                <div><strong>Coordenadas:</strong> <span id="coordsPreview"><?= htmlspecialchars(($config['latitude'] ?? '') . ', ' . ($config['longitude'] ?? '')) ?></span></div>
              </div>

              <div class="ratio ratio-16x9 rounded overflow-hidden border" style="background:#f7f7f7">
                <?php if ($mapEmbedUrl): ?>
                  <iframe id="mapPreview" src="<?= htmlspecialchars($mapEmbedUrl) ?>" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                <?php else: ?>
                  <div id="mapPlaceholder" class="d-flex align-items-center justify-content-center text-muted" style="min-height:280px;text-align:center;padding:20px">
                    Ingresa un enlace de Google Maps o coordenadas para ver la ubicación.
                  </div>
                  <iframe id="mapPreview" src="about:blank" style="border:0;display:none" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                <?php endif; ?>
              </div>

              <div class="d-flex gap-2 flex-wrap mt-3">
                <?php if ($mapOpenUrl): ?>
                  <a id="openMapsBtn" href="<?= htmlspecialchars($mapOpenUrl) ?>" target="_blank" class="btn btn-gold">Abrir en Google Maps</a>
                <?php else: ?>
                  <a id="openMapsBtn" href="#" target="_blank" class="btn btn-gold disabled" aria-disabled="true">Abrir en Google Maps</a>
                <?php endif; ?>
                <span class="text-muted align-self-center" style="font-size:.82rem">La tienda en línea tomará estos datos automáticamente.</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<script>
function buildMapsUrl(lat, lng) {
  lat = (lat || '').trim();
  lng = (lng || '').trim();
  if (!lat || !lng) return '';
  return `https://maps.google.com/maps?output=embed&q=${encodeURIComponent(lat + ',' + lng)}`;
}

function centrarMapa() {
  const lat = document.getElementById('latitude').value.trim();
  const lng = document.getElementById('longitude').value.trim();
  const url = buildMapsUrl(lat, lng);
  const preview = document.getElementById('mapPreview');
  const placeholder = document.getElementById('mapPlaceholder');
  const coords = document.getElementById('coordsPreview');
  const btn = document.getElementById('openMapsBtn');

  if (coords) coords.textContent = lat && lng ? `${lat}, ${lng}` : 'Sin coordenadas';

  if (url) {
    if (preview) {
      preview.src = url;
      preview.style.display = 'block';
    }
    if (placeholder) placeholder.style.display = 'none';
    if (btn) {
      btn.href = url;
      btn.classList.remove('disabled');
      btn.setAttribute('aria-disabled', 'false');
    }
  } else {
    if (preview) {
      preview.src = 'about:blank';
      preview.style.display = 'none';
    }
    if (placeholder) placeholder.style.display = 'flex';
    if (btn) {
      btn.href = '#';
      btn.classList.add('disabled');
      btn.setAttribute('aria-disabled', 'true');
    }
  }
}

document.addEventListener('DOMContentLoaded', centrarMapa);
</script>
</body>
</html>
