<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
if ((int)db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) {
    redirect('login.php');
}
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $usuario = trim((string)($_POST['usuario'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($nombre === '' || $usuario === '' || $password === '') {
        $error = 'Complete todos los campos.';
    } elseif (mb_strlen($nombre) > 120 || !preg_match('/^[a-zA-Z0-9_.-]{3,60}$/', $usuario)) {
        $error = 'Revise el nombre y el usuario (3 a 60 caracteres; letras, números, punto, guion o guion bajo).';
    } elseif (strlen($password) < 12) {
        $error = 'La contraseña debe tener al menos 12 caracteres.';
    } else {
        $pdo = db();
        try {
            $locked = (int)$pdo->query("SELECT GET_LOCK('control_asistencia_instalacion', 5)")->fetchColumn();
            if ($locked !== 1) {
                throw new RuntimeException('No fue posible bloquear la instalación.');
            }
            if ((int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) {
                redirect('login.php');
            }
            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, password, estado) VALUES (?, ?, ?, 1)');
            $stmt->execute([$nombre, $usuario, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Administrador creado. Ya puede iniciar sesión.');
            redirect('login.php');
        } catch (Throwable $exception) {
            error_log((string)$exception);
            $error = 'No se pudo crear el administrador. Inténtelo nuevamente.';
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('control_asistencia_instalacion')");
        }
    }
}
page_start('Configuración inicial');
?>
<div class="card max-form mx-auto"><div class="card-body p-4 p-md-5">
  <h1 class="page-title h2 mb-2">Crear administrador</h1>
  <p class="text-muted mb-4">Este formulario está disponible únicamente antes de crear el primer usuario administrativo.</p>
  <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="instalar.php">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="nombre">Nombre completo</label><input class="form-control" id="nombre" name="nombre" maxlength="120" required value="<?= e($_POST['nombre'] ?? '') ?>"></div>
    <div class="mb-3"><label class="form-label" for="usuario">Usuario</label><input class="form-control" id="usuario" name="usuario" maxlength="60" minlength="3" pattern="[a-zA-Z0-9_.-]{3,60}" required value="<?= e($_POST['usuario'] ?? '') ?>"></div>
    <div class="mb-4"><label class="form-label" for="password">Contraseña (mínimo 12 caracteres)</label><input class="form-control" type="password" id="password" name="password" minlength="12" required></div>
    <button class="btn btn-primary w-100" type="submit">Crear administrador</button>
  </form>
</div></div>
<?php page_end(); ?>
