<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';

// La pantalla es pública: solo solicita cédula y código, sin sesión administrativa.
$error = null;
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cedula = trim((string)($_POST['cedula'] ?? ''));
    $codigo = strtoupper(trim((string)($_POST['codigo'] ?? '')));
    if ($cedula === '' || $codigo === '') {
        $error = 'Complete la cédula y el código de acceso.';
    } elseif (strlen($cedula) > 30 || !preg_match('/^[A-Z0-9]{6}$/', $codigo)) {
        $error = 'Cédula o código de acceso incorrecto.';
    } else {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            // El bloqueo del empleado serializa marcaciones simultáneas del mismo usuario.
            $stmt = $pdo->prepare('SELECT id, nombre, estado, codigo_acceso FROM empleados WHERE cedula = ? FOR UPDATE');
            $stmt->execute([$cedula]);
            $empleado = $stmt->fetch();
            if (!$empleado || !hash_equals($empleado['codigo_acceso'], $codigo)) {
                $error = 'Cédula o código de acceso incorrecto.';
            } elseif ((int)$empleado['estado'] !== 1) {
                $error = 'El empleado está inactivo. Consulte al administrador.';
            } else {
                $now = new DateTimeImmutable();
                $fecha = $now->format('Y-m-d');
                $hora = $now->format('Y-m-d H:i:s');
                // La última asistencia de hoy determina si toca cerrar turno o abrir otro.
                $stmt = $pdo->prepare('SELECT id, hora_entrada, hora_salida FROM asistencias WHERE empleado_id = ? AND fecha = ? ORDER BY id DESC LIMIT 1 FOR UPDATE');
                $stmt->execute([$empleado['id'], $fecha]);
                $ultima = $stmt->fetch();
                if ($ultima && $ultima['hora_salida'] === null) {
                    $stmt = $pdo->prepare('UPDATE asistencias SET hora_salida = ? WHERE id = ? AND hora_salida IS NULL');
                    $stmt->execute([$hora, $ultima['id']]);
                    $tipo = 'Salida';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO asistencias (empleado_id, fecha, hora_entrada) VALUES (?, ?, ?)');
                    $stmt->execute([$empleado['id'], $fecha, $hora]);
                    $tipo = 'Entrada';
                }
                $result = ['nombre' => $empleado['nombre'], 'fecha' => $fecha, 'hora' => $hora, 'tipo' => $tipo];
            }
            // La transacción confirma la marca antes de mostrar el resultado.
            $pdo->commit();
            if ($result) {
                $_SESSION['mark_result'] = $result;
                redirect('index.php');
            }
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log((string)$exception);
            $error = 'No se pudo registrar la marcación. Inténtelo nuevamente.';
        }
    }
} elseif (isset($_SESSION['mark_result'])) {
    // Muestra una sola vez el resultado guardado tras la redirección POST/GET.
    $result = $_SESSION['mark_result'];
    unset($_SESSION['mark_result']);
}

page_start('Marcar asistencia');
?>
<div class="row justify-content-center g-4">
  <div class="col-lg-8">
    <div class="card hero-card mb-4"><div class="card-body">
      <span class="badge text-bg-light mb-3">Registro público</span>
      <h1 class="display-6 fw-bold mb-2">Marca tu asistencia</h1>
      <p class="mb-0 opacity-75">Ingresa tu cédula y código de acceso. El sistema registrará automáticamente tu entrada o salida.</p>
    </div></div>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($result): ?>
      <div class="card mark-result mb-4" role="status"><div class="card-body">
        <h2 class="h5 mb-3"><?= e($result['tipo']) ?> registrada correctamente.</h2>
        <div><strong>Empleado:</strong> <?= e($result['nombre']) ?></div>
        <div><strong>Fecha:</strong> <?= e(date_view($result['fecha'])) ?></div>
        <div><strong>Hora de <?= strtolower(e($result['tipo'])) ?>:</strong> <?= e(time_view($result['hora'])) ?></div>
      </div></div>
    <?php endif; ?>
    <div class="card"><div class="card-body p-4">
      <form method="post" action="index.php" autocomplete="off">
        <?= csrf_field() ?>
        <div class="mb-3"><label for="cedula" class="form-label">Cédula</label><input class="form-control form-control-lg" id="cedula" name="cedula" maxlength="30" required autofocus value="<?= e($_POST['cedula'] ?? '') ?>"></div>
        <div class="mb-4"><label for="codigo" class="form-label">Código de acceso</label><input class="form-control form-control-lg text-uppercase code-text" id="codigo" name="codigo" maxlength="6" pattern="[A-Za-z0-9]{6}" required></div>
        <button class="btn btn-primary btn-lg w-100" type="submit">Registrar</button>
      </form>
    </div></div>
  </div>
</div>
<?php page_end(); ?>
