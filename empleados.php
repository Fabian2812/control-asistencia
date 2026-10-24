<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $estado = $_POST['estado'] ?? null;
    if (!$id || !in_array($estado, ['0', '1'], true)) {
        flash('danger', 'Solicitud inválida.');
    } else {
        $stmt = db()->prepare('UPDATE empleados SET estado = ? WHERE id = ?');
        $stmt->execute([(int)$estado, $id]);
        flash($stmt->rowCount() ? 'success' : 'warning', $stmt->rowCount() ? 'Estado del empleado actualizado.' : 'No se encontró el empleado o no hubo cambios.');
    }
    redirect('empleados.php');
}
$buscar = trim((string)($_GET['buscar'] ?? ''));
$estado = (string)($_GET['estado'] ?? 'todos');
$where = [];
$params = [];
if ($buscar !== '') {
    $where[] = '(nombre LIKE ? OR cedula LIKE ?)';
    $params[] = '%' . $buscar . '%';
    $params[] = '%' . $buscar . '%';
}
if (in_array($estado, ['0', '1'], true)) {
    $where[] = 'estado = ?';
    $params[] = (int)$estado;
}
$sql = 'SELECT * FROM empleados' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY nombre ASC LIMIT 500';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$empleados = $stmt->fetchAll();
page_start('Empleados', true);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="page-title h2 mb-1">Empleados</h1><p class="text-muted mb-0">Consulta, edición y estado de los empleados.</p></div><a class="btn btn-primary" href="empleado_form.php">+ Nuevo empleado</a></div>
<div class="card mb-4"><div class="card-body"><form method="get" action="empleados.php" class="row g-3 align-items-end">
  <div class="col-md-7"><label class="form-label" for="buscar">Nombre o cédula</label><input class="form-control" id="buscar" name="buscar" maxlength="120" value="<?= e($buscar) ?>"></div>
  <div class="col-md-3"><label class="form-label" for="estado">Estado</label><select class="form-select" id="estado" name="estado"><option value="todos">Todos</option><option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Activos</option><option value="0" <?= $estado === '0' ? 'selected' : '' ?>>Inactivos</option></select></div>
  <div class="col-md-2"><button class="btn btn-primary w-100">Buscar</button></div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Cédula</th><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Código</th><th>Estado</th><th>Registro</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($empleados as $empleado): ?><tr>
  <td><?= e($empleado['cedula']) ?></td><td class="fw-semibold"><?= e($empleado['nombre']) ?></td><td><?= e($empleado['correo']) ?></td><td><?= e($empleado['telefono']) ?></td><td><span class="code-text"><?= e($empleado['codigo_acceso']) ?></span></td>
  <td><span class="badge <?= (int)$empleado['estado'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int)$empleado['estado'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
  <td><?= e(date_view($empleado['fecha_registro'])) ?></td><td><div class="d-flex gap-2 actions"><a class="btn btn-outline-primary btn-sm" href="empleado_form.php?id=<?= (int)$empleado['id'] ?>">Editar</a><form method="post" action="empleados.php" onsubmit="return confirm('¿Cambiar el estado de este empleado?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$empleado['id'] ?>"><input type="hidden" name="estado" value="<?= (int)$empleado['estado'] === 1 ? '0' : '1' ?>"><button type="submit" class="btn btn-outline-secondary btn-sm"><?= (int)$empleado['estado'] === 1 ? 'Desactivar' : 'Activar' ?></button></form></div></td>
</tr><?php endforeach; ?>
<?php if (!$empleados): ?><tr><td colspan="8" class="text-center text-muted py-4">No se encontraron empleados.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php if (count($empleados) === 500): ?><p class="text-muted small mt-2">Se muestran los primeros 500 resultados. Ajuste la búsqueda para encontrar otros empleados.</p><?php endif; ?>
<?php page_end(); ?>
