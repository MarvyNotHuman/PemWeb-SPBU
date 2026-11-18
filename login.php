<?php
require 'includes/init.php';
if (user()) redirect('index.php');
$galat = '';
$username = bersih($_COOKIE['ingat_user'] ?? '');            // cookie: mengingat username
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $username = bersih($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($username === '' || $pass === '') {
        $galat = 'Username dan password wajib diisi.';
    } else {
        $r = query($db, 'SELECT * FROM users WHERE username=?', 's', [$username]);
        if ($r && password_verify($pass, $r[0]['password'])) {
            session_regenerate_id(true);                      // cegah session fixation
            $_SESSION['user'] = ['id' => (int)$r[0]['id'], 'nama' => $r[0]['nama'], 'role' => $r[0]['role']];
            $opsi = ['path' => '/', 'httponly' => true, 'samesite' => 'Lax'];
            if (isset($_POST['ingat'])) setcookie('ingat_user', $username, $opsi + ['expires' => time() + 86400 * 30]);
            else setcookie('ingat_user', '', $opsi + ['expires' => time() - 3600]);
            redirect('index.php');
        }
        $galat = 'Username atau password salah.';
    }
}
$judul = 'Login';
$kelasMain = 'login-wrap';
require 'includes/header.php';
?>
<section class="login-hero">
  <div class="mb-3"><i class="bi bi-fuel-pump-fill fs-1"></i></div>
  <h1>Sistem SPBU<br>UNSOED</h1>
  <p class="lead opacity-75 mb-3">Transaksi pengisian BBM, kuota subsidi harian, dan data kendaraan dalam satu aplikasi web.</p>
  <div><span class="chip"><i class="bi bi-shield-check me-1"></i>Kuota subsidi per hari</span><span class="chip"><i class="bi bi-car-front me-1"></i>Mobil &amp; Motor</span><span class="chip"><i class="bi bi-receipt me-1"></i>Struk otomatis</span></div>
</section>
<section class="login-form"><div class="card w-100" style="max-width:420px"><div class="card-body p-4">
  <h4 class="mb-1">Selamat datang</h4><p class="text-muted">Masuk untuk melanjutkan.</p>
  <?php if ($galat): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= e($galat) ?></div><?php endif; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Username</label><input name="username" class="form-control" value="<?= e($username) ?>" required autofocus></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="ingat" id="ingat" <?= $username ? 'checked' : '' ?>><label class="form-check-label" for="ingat">Ingat username</label></div>
    <button class="btn btn-primary w-100 py-2">Masuk</button>
  </form>
  <p class="text-muted small mt-3 mb-0">Demo: admin / admin123 &middot; kasir / kasir123</p>
</div></div></section>
<?php require 'includes/footer.php'; ?>
