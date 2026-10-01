<?php
// ============================================================
// TOKONESIA — Admin: Manajemen Kategori (CRUD)
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db     = getDB();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// DELETE
if ($action === 'delete' && $editId) {
    $check = $db->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $check->execute([$editId]);
    if ((int)$check->fetchColumn() > 0) {
        setFlash('error', 'Kategori tidak bisa dihapus karena masih ada produk di dalamnya.');
    } else {
        $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$editId]);
        setFlash('success', 'Kategori berhasil dihapus.');
    }
    redirect(BASE_URL . '/admin/categories.php');
}

// SAVE
$errors      = [];
$editCategory = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-tag');
    $slug = makeSlug($name);

    if (!$name) $errors[] = 'Nama kategori wajib diisi.';

    if (!$errors) {
        $slugCheck = $db->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
        $slugCheck->execute([$slug, $editId ?: 0]);
        if ($slugCheck->fetch()) $slug .= '-' . time();

        if ($editId) {
            $db->prepare('UPDATE categories SET name=?, slug=?, icon=? WHERE id=?')->execute([$name, $slug, $icon, $editId]);
            setFlash('success', 'Kategori berhasil diperbarui.');
        } else {
            $db->prepare('INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)')->execute([$name, $slug, $icon]);
            setFlash('success', 'Kategori berhasil ditambahkan.');
        }
        redirect(BASE_URL . '/admin/categories.php');
    }
}

if ($action === 'edit' && $editId) {
    $s = $db->prepare('SELECT * FROM categories WHERE id = ?');
    $s->execute([$editId]);
    $editCategory = $s->fetch();
}

// List
$categories = $db->query(
    'SELECT c.*, COUNT(p.id) AS product_count
     FROM categories c LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.name'
)->fetchAll();

// Popular BI icons for categories
$iconOptions = [
    'bi-cpu','bi-bag','bi-house','bi-trophy','bi-stars','bi-phone','bi-book',
    'bi-cart','bi-gift','bi-music-note','bi-camera','bi-bicycle','bi-tools',
    'bi-flower1','bi-egg-fried','bi-controller','bi-palette','bi-heart',
    'bi-laptop','bi-watch','bi-handbag','bi-tag','bi-grid',
];

$pageTitle = 'Manajemen Kategori — Admin';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="row g-4 align-items-start">

  <!-- Form Add/Edit -->
  <div class="col-lg-4">
    <div class="admin-card">
      <h6 class="fw-700 mb-3">
        <i class="bi bi-<?= $action === 'edit' ? 'pencil' : 'plus-circle' ?> me-2"></i>
        <?= $action === 'edit' ? 'Edit Kategori' : 'Tambah Kategori' ?>
      </h6>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger small py-2">
          <?= implode('<br>', array_map('e', $errors)) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>/admin/categories.php<?= $editId ? '?action=edit&id=' . $editId : '' ?>">
        <div class="mb-3">
          <label class="form-label fw-600 small">Nama Kategori <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control"
                 value="<?= e($editCategory['name'] ?? $_POST['name'] ?? '') ?>"
                 placeholder="Elektronik" required />
        </div>

        <div class="mb-3">
          <label class="form-label fw-600 small">Ikon Bootstrap Icons</label>
          <select name="icon" class="form-select" id="iconSelect" onchange="updateIconPreview()">
            <?php foreach ($iconOptions as $ic): ?>
              <option value="<?= $ic ?>"
                <?= ($editCategory['icon'] ?? $_POST['icon'] ?? 'bi-tag') === $ic ? 'selected' : '' ?>>
                <?= $ic ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="mt-2 d-flex align-items-center gap-2">
            <div id="iconPreview" style="width:40px;height:40px;border-radius:10px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--primary);">
              <i class="bi <?= e($editCategory['icon'] ?? 'bi-tag') ?>" id="iconEl"></i>
            </div>
            <span class="small text-muted">Preview ikon</span>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary fw-600 flex-grow-1">
            <i class="bi bi-save me-1"></i><?= $editId ? 'Simpan' : 'Tambah' ?>
          </button>
          <?php if ($editId): ?>
            <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-outline-secondary">Batal</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Kategori -->
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="admin-card-header">
        <h5 class="admin-card-title"><i class="bi bi-tags me-2"></i>Daftar Kategori</h5>
      </div>
      <div class="table-responsive">
        <table class="table admin-table">
          <thead>
            <tr><th>#</th><th>Ikon</th><th>Nama</th><th>Slug</th><th>Jumlah Produk</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $i => $cat): ?>
              <tr>
                <td class="text-muted"><?= $i + 1 ?></td>
                <td>
                  <div style="width:34px;height:34px;border-radius:8px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;color:var(--primary);">
                    <i class="bi <?= e($cat['icon']) ?>"></i>
                  </div>
                </td>
                <td class="fw-700"><?= e($cat['name']) ?></td>
                <td class="text-muted small"><code><?= e($cat['slug']) ?></code></td>
                <td>
                  <span class="badge bg-light text-dark fw-600"><?= $cat['product_count'] ?> produk</span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="<?= BASE_URL ?>/admin/categories.php?action=edit&id=<?= $cat['id'] ?>"
                       class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <?php if ($cat['product_count'] == 0): ?>
                      <button onclick="confirmDelete('<?= BASE_URL ?>/admin/categories.php?action=delete&id=<?= $cat['id'] ?>','<?= e(addslashes($cat['name'])) ?>')"
                              class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    <?php else: ?>
                      <button class="btn btn-sm btn-outline-secondary" disabled title="Ada produk dalam kategori ini">
                        <i class="bi bi-lock"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script>
function updateIconPreview() {
  const val = document.getElementById('iconSelect').value;
  document.getElementById('iconEl').className = 'bi ' + val;
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
