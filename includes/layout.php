<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

/** Abre la página HTML, carga Bootstrap y dibuja el menú según el tipo de usuario. */
function page_start(string $title, bool $admin = false): void
{
    $flash = take_flash();
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · Control de asistencia</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light app-nav">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $admin ? 'dashboard.php' : 'index.php' ?>"><span class="brand-mark" aria-hidden="true">◷</span><span>Asistencia</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Abrir menú"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navMain">
      <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <?php if ($admin): ?>
          <a class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
          <a class="nav-link <?= in_array($current, ['empleados.php', 'empleado_form.php'], true) ? 'active' : '' ?>" href="empleados.php">Empleados</a>
          <a class="nav-link <?= $current === 'asistencias.php' ? 'active' : '' ?>" href="asistencias.php">Asistencias</a>
          <a class="nav-link" href="index.php">Marcación pública</a>
          <form method="post" action="logout.php" class="ms-lg-2 my-2 my-lg-0"><?= csrf_field() ?><button class="btn btn-outline-light btn-sm" type="submit">Salir</button></form>
        <?php else: ?>
          <a class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>" href="index.php">Marcar asistencia</a>
          <a class="nav-link <?= $current === 'login.php' ? 'active' : '' ?>" href="login.php">Administración</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<main class="container py-4 py-md-5">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>
  <?php endif; ?>
<?php
}

/** Cierra el contenido y agrega el pie de página y JavaScript de Bootstrap. */
function page_end(): void
{
    ?>
</main>
<footer class="container text-center text-muted small pb-4">Control de asistencia · <?= date('Y') ?></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
