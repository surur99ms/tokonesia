<?php
// ============================================================
// TOKONESIA — Login
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    if (!$email || !$password) {
        $error = 'Email dan password wajib diisi.';
    } elseif (loginUser($email, $password)) {
        redirect(isAdmin() ? BASE_URL . '/admin/index.php' : BASE_URL . '/index.php');
    } else {
        $error = 'Email atau password salah.';
    }
}

$pageTitle = 'Masuk — ' . getSetting('site_name', 'Tokonesia');
require_once __DIR__ . '/includes/header.php';
?>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container" style="max-width:420px;padding-top:48px;padding-bottom:60px;">
  <div class="text-center mb-4">
    <div style="width:64px;height:64px;border-radius:20px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 12px;">
      <i class="bi bi-person-fill" style="color:var(--primary);"></i>
    </div>
    <h1 class="fw-800" style="font-size:1.5rem;">Selamat Datang</h1>
    <p class="text-muted small">Masuk ke akun Anda</p>
  </div>

  <div class="bg-white rounded-4 border shadow-sm p-4">
    <?php if ($error): ?>
      <div class="alert alert-danger rounded-3 py-2 small"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="mb-3">
        <label class="form-label fw-600 small">Email</label>
        <input type="email" name="email" class="form-control"
               value="<?= e($_POST['email'] ?? '') ?>"
               placeholder="nama@email.com" required autofocus />
      </div>
      <div class="mb-4">
        <label class="form-label fw-600 small">Password</label>
        <div class="input-group">
          <input type="password" name="password" class="form-control" id="pw" placeholder="••••••••" required />
          <button class="btn btn-outline-secondary border" type="button" onclick="togglePw()">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>
      <button class="btn btn-primary-custom w-100 py-2" type="submit">
        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
      </button>
    </form>

    <hr class="my-3" />
    <p class="text-center small text-muted mb-0">
      Belum punya akun?
      <a href="<?= BASE_URL ?>/register.php" class="fw-700 text-primary text-decoration-none">Daftar Sekarang</a>
    </p>
  </div>
</div>

<script>
function togglePw() {
  const pw = document.getElementById('pw');
  const ic = document.getElementById('eyeIcon');
  pw.type = pw.type === 'password' ? 'text' : 'password';
  ic.className = pw.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
