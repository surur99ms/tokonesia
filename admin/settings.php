<?php
// ============================================================
// TOKONESIA — Admin: Pengaturan Toko
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// SAVE Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'site_name', 'hero_title', 'hero_subtitle',
        'whatsapp_number', 'primary_color', 'currency_symbol'
    ];
    foreach ($fields as $key) {
        $val  = trim($_POST[$key] ?? '');
        $stmt = $db->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?');
        $stmt->execute([$key, $val, $val]);
    }

    // Upload Logo
    if (!empty($_FILES['site_logo']['tmp_name'])) {
        $file = $_FILES['site_logo'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            $logoName = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $logoName)) {
                // Hapus logo lama
                $oldLogo = getSetting('site_logo');
                if ($oldLogo && file_exists(UPLOAD_DIR . $oldLogo)) unlink(UPLOAD_DIR . $oldLogo);
                $stmt = $db->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?');
                $stmt->execute(['site_logo', $logoName, $logoName]);
            }
        }
    }

    setFlash('success', 'Pengaturan toko berhasil disimpan!');
    redirect(BASE_URL . '/admin/settings.php');
}

// Load current settings
$settingKeys = ['site_name','hero_title','hero_subtitle','whatsapp_number','primary_color','currency_symbol','site_logo'];
$settings = [];
foreach ($settingKeys as $key) {
    $settings[$key] = getSetting($key);
}

$logoUrl = $settings['site_logo']
  ? (file_exists(UPLOAD_DIR . $settings['site_logo']) ? UPLOAD_URL . $settings['site_logo'] : '')
  : '';

$pageTitle = 'Pengaturan Toko — Admin';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<form method="POST" enctype="multipart/form-data">
  <div class="row g-4 align-items-start">

    <!-- Left: General Settings -->
    <div class="col-lg-8">

      <!-- Informasi Toko -->
      <div class="admin-card mb-3">
        <h6 class="fw-700 mb-3"><i class="bi bi-shop me-2"></i>Informasi Toko</h6>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-600 small">Nama Toko</label>
            <input type="text" name="site_name" class="form-control"
                   value="<?= e($settings['site_name']) ?>" placeholder="Tokonesia" />
          </div>
          <div class="col-md-6">
            <label class="form-label fw-600 small">Nomor WhatsApp CS</label>
            <div class="input-group">
              <span class="input-group-text text-muted small">+</span>
              <input type="text" name="whatsapp_number" class="form-control"
                     value="<?= e($settings['whatsapp_number']) ?>"
                     placeholder="6281234567890 (tanpa +)" />
            </div>
            <div class="form-text">Format internasional tanpa + (misal: 6281234567890)</div>
          </div>
        </div>
      </div>

      <!-- Hero Banner -->
      <div class="admin-card mb-3">
        <h6 class="fw-700 mb-3"><i class="bi bi-image me-2"></i>Teks Hero Banner (Halaman Beranda)</h6>
        <div class="mb-3">
          <label class="form-label fw-600 small">Judul Utama</label>
          <input type="text" name="hero_title" class="form-control"
                 value="<?= e($settings['hero_title']) ?>"
                 placeholder="Belanja Lebih Mudah, Lebih Hemat!" />
        </div>
        <div class="mb-0">
          <label class="form-label fw-600 small">Teks Deskripsi</label>
          <textarea name="hero_subtitle" class="form-control" rows="3"
                    placeholder="Temukan ribuan produk pilihan..."><?= e($settings['hero_subtitle']) ?></textarea>
        </div>
      </div>

      <!-- Tampilan -->
      <div class="admin-card mb-3">
        <h6 class="fw-700 mb-3"><i class="bi bi-palette me-2"></i>Tampilan & Branding</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-600 small">Warna Utama (Primary Color)</label>
            <div class="d-flex gap-2 align-items-center">
              <input type="color" name="primary_color" class="form-control form-control-color"
                     value="<?= e($settings['primary_color'] ?: '#6C63FF') ?>" style="width:50px;height:38px;" />
              <input type="text" id="colorHex" class="form-control"
                     value="<?= e($settings['primary_color'] ?: '#6C63FF') ?>"
                     placeholder="#6C63FF" readonly />
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-600 small">Simbol Mata Uang</label>
            <input type="text" name="currency_symbol" class="form-control"
                   value="<?= e($settings['currency_symbol'] ?: 'Rp') ?>"
                   placeholder="Rp" maxlength="5" />
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary fw-700 px-5 py-2">
        <i class="bi bi-save me-2"></i>Simpan Pengaturan
      </button>
    </div>

    <!-- Right: Logo Upload -->
    <div class="col-lg-4">
      <div class="admin-card">
        <h6 class="fw-700 mb-3"><i class="bi bi-image-fill me-2"></i>Logo Toko</h6>

        <!-- Preview -->
        <div class="text-center mb-3">
          <?php if ($logoUrl): ?>
            <img id="logoPreview" src="<?= e($logoUrl) ?>" alt="Logo"
                 style="max-width:180px;max-height:80px;object-fit:contain;border:1px solid var(--gray-200);border-radius:10px;padding:8px;" />
          <?php else: ?>
            <div id="logoPlaceholder" style="width:180px;height:80px;border:2px dashed var(--gray-200);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto;color:var(--gray-500);font-size:.8rem;">
              <span>Belum ada logo</span>
            </div>
            <img id="logoPreview" src="" alt="" style="display:none;max-width:180px;max-height:80px;object-fit:contain;border:1px solid var(--gray-200);border-radius:10px;padding:8px;" />
          <?php endif; ?>
        </div>

        <div class="upload-zone" onclick="document.getElementById('logoInput').click()">
          <i class="bi bi-upload me-2"></i>
          <span class="small">Upload Logo Baru</span><br>
          <span style="font-size:.72rem;color:var(--gray-500);">PNG/JPG/SVG, maks 2MB</span>
        </div>
        <input type="file" id="logoInput" name="site_logo" accept="image/*" style="display:none;"
               onchange="previewLogo(this)" />

        <div class="mt-3 p-3 rounded-3" style="background:#f8fafc;font-size:.78rem;color:var(--gray-500);">
          <i class="bi bi-info-circle me-1"></i>
          Logo akan tampil di navigasi atas website. Direkomendasikan ukuran 300×80px atau lebih dengan background transparan.
        </div>
      </div>
    </div>

  </div>
</form>

<script>
// Color picker sync
document.querySelector('input[name=primary_color]').addEventListener('input', function() {
  document.getElementById('colorHex').value = this.value;
});

function previewLogo(input) {
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    const prev = document.getElementById('logoPreview');
    const placeholder = document.getElementById('logoPlaceholder');
    prev.src = e.target.result;
    prev.style.display = '';
    if (placeholder) placeholder.style.display = 'none';
  };
  reader.readAsDataURL(input.files[0]);
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
