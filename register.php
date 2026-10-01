<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if (!$name || !$email || !$password) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $result = registerUser($name, $email, $password);
        if ($result === true) {
            loginUser($email, $password);
            setFlash('success', 'Registrasi berhasil! Selamat datang, ' . $name . '.');
            redirect(BASE_URL . '/index.php');
        } else {
            $error = $result;
        }
    }
}

$pageTitle = 'Daftar — ' . getSetting('site_name', 'Tokonesia');
require_once __DIR__ . '/includes/header.php';
?>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>

<div class="container" style="max-width:440px;padding-top:48px;padding-bottom:60px;">
  <div class="text-center mb-4">
    <div style="width:64px;height:64px;border-radius:20px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 12px;">
      <i class="bi bi-person-plus-fill" style="color:var(--primary);"></i>
    </div>
    <h1 class="fw-800" style="font-size:1.5rem;">Buat Akun Baru</h1>
    <p class="text-muted small">Bergabung dan mulai belanja sekarang</p>
  </div>

  <div class="bg-white rounded-4 border shadow-sm p-4">
    <?php if ($error): ?>
      <div class="alert alert-danger rounded-3 py-2 small"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="mb-3">
        <label class="form-label fw-600 small">Nama Lengkap</label>
        <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? '') ?>" placeholder="John Doe" required autofocus />
      </div>
      <div class="mb-3">
        <label class="form-label fw-600 small">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" placeholder="nama@email.com" required />
      </div>
      <div class="mb-3">
        <label class="form-label fw-600 small">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required />
      </div>
      <div class="mb-4">
        <label class="form-label fw-600 small">Konfirmasi Password</label>
        <input type="password" name="confirm" class="form-control" placeholder="Ulangi password" required />
      </div>
      <button class="btn btn-primary-custom w-100 py-2" type="submit">
        <i class="bi bi-person-check me-2"></i>Daftar Sekarang
      </button>
    </form>

    <hr class="my-3" />
    <p class="text-center small text-muted mb-0">
      Sudah punya akun?
      <a href="<?= BASE_URL ?>/login.php" class="fw-700 text-primary text-decoration-none">Masuk</a>
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
