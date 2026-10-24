<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_admin();
// Un ID en la URL activa la edición; sin ID se presenta el formulario de alta.
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (isset($_GET['id']) && !$id) {
    http_response_code(400);
    exit('ID de empleado inválido.');
}
$empleado = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM empleados WHERE id = ?');
    $stmt->execute([$id]);
    $empleado = $stmt->fetch();
    if (!$empleado) {
        http_response_code(404);
        exit('Empleado no encontrado.');
    }
}
$values = $empleado ?: ['cedula' => '', 'nombre' => '', 'correo' => '', 'telefono' => '', 'estado' => 1];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Normaliza los campos y valida longitud, formato y estado antes de escribir.
    verify_csrf();
    foreach (['cedula', 'nombre', 'correo', 'telefono'] as $field) {
        $values[$field] = trim((string)($_POST[$field] ?? ''));
    }
    $values['estado'] = $_POST['estado'] ?? '1';
    if ($values['cedula'] === '' || $values['nombre'] === '' || $values['correo'] === '' || $values['telefono'] === '') {
        $errors[] = 'Complete todos los campos obligatorios.';
    }
    if (mb_strlen($values['cedula']) > 30 || !preg_match('/^[\pL\pN .-]+$/u', $values['cedula'])) {
        $errors[] = 'La cédula debe tener hasta 30 caracteres y contener solo letras, números, espacios, puntos o guiones.';
    }
    if (mb_strlen($values['nombre']) > 120) {
        $errors[] = 'El nombre puede tener hasta 120 caracteres.';
    }
    if (mb_strlen($values['correo']) > 190 || !filter_var($values['correo'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Ingrese un correo electrónico válido.';
    }
    if (mb_strlen($values['telefono']) > 30 || !preg_match('/^[0-9+() .-]{6,30}$/', $values['telefono'])) {
        $errors[] = 'Ingrese un teléfono válido de 6 a 30 caracteres.';
    }
    if (!in_array((string)$values['estado'], ['0', '1'], true)) {
        $errors[] = 'Seleccione un estado válido.';
    }
    if (!$errors) {
        // Comprueba si la cédula pertenece ya a otro empleado.
        $stmt = db()->prepare('SELECT id FROM empleados WHERE cedula = ? AND id <> ? LIMIT 1');
        $stmt->execute([$values['cedula'], $id ?: 0]);
        if ($stmt->fetch()) {
            $errors[] = 'Ya existe un empleado con esa cédula.';
        }
    }
    if (!$errors) {
        try {
            if ($id) {
                // Editar conserva el código asignado al registrar al empleado.
                $stmt = db()->prepare('UPDATE empleados SET cedula = ?, nombre = ?, correo = ?, telefono = ?, estado = ? WHERE id = ?');
                $stmt->execute([$values['cedula'], $values['nombre'], $values['correo'], $values['telefono'], (int)$values['estado'], $id]);
                flash('success', 'Empleado actualizado correctamente.');
            } else {
                // Genera seis caracteres aleatorios; el índice UNIQUE permite reintentar colisiones.
                $created = false;
                for ($attempt = 0; $attempt < 10; $attempt++) {
                    $codigo = strtoupper(bin2hex(random_bytes(3)));
                    try {
                        $stmt = db()->prepare('INSERT INTO empleados (cedula, nombre, correo, telefono, codigo_acceso, estado) VALUES (?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$values['cedula'], $values['nombre'], $values['correo'], $values['telefono'], $codigo, (int)$values['estado']]);
                        $created = true;
                        break;
                    } catch (PDOException $exception) {
                        if ($exception->getCode() !== '23000') {
                            throw $exception;
                        }
                        $stmt = db()->prepare('SELECT id FROM empleados WHERE cedula = ?');
                        $stmt->execute([$values['cedula']]);
                        if ($stmt->fetch()) {
                            $errors[] = 'Ya existe un empleado con esa cédula.';
                            break;
                        }
                    }
                }
                if (!$created && !$errors) {
                    $errors[] = 'No se pudo generar un código único. Inténtelo nuevamente.';
                }
                if ($created) {
                    flash('success', 'Empleado registrado. Código de acceso: ' . $codigo);
                }
            }
            if (!$errors) {
                redirect('empleados.php');
            }
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'Ya existe un empleado con esa cédula.';
            } else {
                error_log((string)$exception);
                $errors[] = 'No se pudo guardar el empleado. Inténtelo nuevamente.';
            }
        }
    }
}
page_start($id ? 'Editar empleado' : 'Nuevo empleado', true);
?>
<div class="max-form mx-auto"><div class="d-flex justify-content-between align-items-center gap-2 mb-4"><div><h1 class="page-title h2 mb-1"><?= $id ? 'Editar empleado' : 'Nuevo empleado' ?></h1><p class="text-muted mb-0"><?= $id ? 'Actualice los datos del empleado.' : 'El código de acceso se creará automáticamente.' ?></p></div><a href="empleados.php" class="btn btn-outline-secondary">Volver</a></div>
<?php if ($errors): ?><div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="card"><div class="card-body p-4">
  <form method="post" action="empleado_form.php<?= $id ? '?id=' . (int)$id : '' ?>">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label" for="cedula">Cédula *</label><input class="form-control" id="cedula" name="cedula" maxlength="30" required value="<?= e($values['cedula']) ?>"></div>
    <div class="mb-3"><label class="form-label" for="nombre">Nombre completo *</label><input class="form-control" id="nombre" name="nombre" maxlength="120" required value="<?= e($values['nombre']) ?>"></div>
    <div class="mb-3"><label class="form-label" for="correo">Correo electrónico *</label><input class="form-control" type="email" id="correo" name="correo" maxlength="190" required value="<?= e($values['correo']) ?>"></div>
    <div class="mb-3"><label class="form-label" for="telefono">Teléfono *</label><input class="form-control" type="tel" id="telefono" name="telefono" maxlength="30" required value="<?= e($values['telefono']) ?>"></div>
    <div class="mb-4"><label class="form-label" for="estado">Estado *</label><select class="form-select" id="estado" name="estado"><option value="1" <?= (string)$values['estado'] === '1' ? 'selected' : '' ?>>Activo</option><option value="0" <?= (string)$values['estado'] === '0' ? 'selected' : '' ?>>Inactivo</option></select></div>
    <?php if ($id): ?><div class="alert alert-light border">Código de acceso: <strong class="code-text"><?= e($empleado['codigo_acceso']) ?></strong><br><small class="text-muted">El código se conserva al editar.</small></div><?php endif; ?>
    <button class="btn btn-primary" type="submit"><?= $id ? 'Guardar cambios' : 'Registrar empleado' ?></button>
  </form>
</div></div></div>
<?php page_end(); ?>
