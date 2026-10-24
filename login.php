<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
// Si todavía no existe administrador, conduce a la configuración inicial.
if (!empty($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}
$count = (int)db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
if ($count === 0) {
    redirect('instalar.php');
}
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica credenciales con el hash guardado y limita intentos por sesión.
    verify_csrf();
    $usuario = trim((string)($_POST['usuario'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $limitUntil = (int)($_SESSION['login_limit_until'] ?? 0);
    if (time() < $limitUntil) {
        $error = 'Demasiados intentos. Espere cinco minutos e inténtelo nuevamente.';
    } elseif ($usuario === '' || $password === '') {
        $error = 'Complete el usuario y la contraseña.';
    } else {
        $stmt = db()->prepare('SELECT id, nombre, password, estado FROM usuarios WHERE usuario = ? LIMIT 1');
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch();
        if ($admin && (int)$admin['estado'] === 1 && password_verify($password, $admin['password'])) {
            // Renueva el ID de sesión al iniciar para evitar fijación de sesión.
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_nombre'] = $admin['nombre'];
            unset($_SESSION['login_attempts'], $_SESSION['login_limit_until']);
            redirect('dashboard.php');
        }
        $_SESSION['login_attempts'] = (int)($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_limit_until'] = time() + 300;
            $_SESSION['login_attempts'] = 0;
        }
        $error = 'Usuario o contraseña incorrectos.';
    }
}
page_start('Acceso administrativo');
?>
<div class="card max-form mx-auto"><div class="card-body p-4 p-md-5">
  <h1 class="page-title h2 mb-2">Acceso administrativo</h1>
  <p class="text-muted mb-4">Ingrese sus credenciales para administrar empleados y asistencias.</p>
  <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="login.php">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="usuario">Usuario</label><input class="form-control" id="usuario" name="usuario" maxlength="60" autocomplete="username" required value="<?= e($_POST['usuario'] ?? '') ?>"></div>
    <div class="mb-4"><label class="form-label" for="password">Contraseña</label><input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required></div>
    <button class="btn btn-primary w-100" type="submit">Ingresar</button>
  </form>
</div></div>
<?php page_end(); ?>
