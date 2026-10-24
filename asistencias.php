<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/asistencia_query.php';
require_admin();
// Comparte validación de filtros con la exportación para obtener los mismos datos.
[$filters, $errors, $where, $params] = attendance_filters($_GET);
$page = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? $page : 1;
$perPage = 50;
$total = 0;
$rows = [];
if (!$errors) {
    // Cuenta todas las coincidencias y consulta solo 50 filas para esta página.
    $from = ' FROM asistencias a JOIN empleados e ON e.id = a.empleado_id';
    $stmt = db()->prepare('SELECT COUNT(*)' . $from . $where);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;
    $stmt = db()->prepare('SELECT a.id, a.fecha, a.hora_entrada, a.hora_salida, e.nombre, e.cedula' . $from . $where . ' ORDER BY a.fecha DESC, a.hora_entrada DESC, a.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}
// La función anónima omite filtros vacíos al formar el enlace de exportación.
$query = http_build_query(array_filter($filters, static fn($v) => $v !== ''));
page_start('Historial de asistencia', true);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="page-title h2 mb-1">Historial de asistencia</h1><p class="text-muted mb-0">Entradas, salidas y horas trabajadas.</p></div><?php if (!$errors): ?><a class="btn btn-outline-primary" href="exportar_csv.php<?= $query ? '?' . e($query) : '' ?>">Exportar CSV</a><?php endif; ?></div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="card mb-4"><div class="card-body"><form method="get" action="asistencias.php" class="row g-3 align-items-end">
  <div class="col-md-6 col-lg-3"><label class="form-label" for="empleado">Empleado</label><input class="form-control" id="empleado" name="empleado" maxlength="120" value="<?= e($filters['empleado']) ?>"></div>
  <div class="col-md-6 col-lg-3"><label class="form-label" for="cedula">Cédula</label><input class="form-control" id="cedula" name="cedula" maxlength="120" value="<?= e($filters['cedula']) ?>"></div>
  <div class="col-md-4 col-lg-2"><label class="form-label" for="fecha">Fecha exacta</label><input class="form-control" type="date" id="fecha" name="fecha" value="<?= e($filters['fecha']) ?>"></div>
  <div class="col-md-4 col-lg-2"><label class="form-label" for="desde">Desde</label><input class="form-control" type="date" id="desde" name="desde" value="<?= e($filters['desde']) ?>"></div>
  <div class="col-md-4 col-lg-2"><label class="form-label" for="hasta">Hasta</label><input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($filters['hasta']) ?>"></div>
  <div class="col-auto"><button class="btn btn-primary" type="submit">Filtrar</button></div><div class="col-auto"><a href="asistencias.php" class="btn btn-outline-secondary">Limpiar</a></div>
</form></div></div>
<div class="card"><div class="card-header py-3"><strong><?= e($total) ?></strong> registro<?= $total === 1 ? '' : 's' ?></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Cédula</th><th>Empleado</th><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Horas trabajadas</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= e($row['cedula']) ?></td><td><?= e($row['nombre']) ?></td><td><?= e(date_view($row['fecha'])) ?></td><td><?= e(time_view($row['hora_entrada'])) ?></td><td><?= e(time_view($row['hora_salida'])) ?></td><td><?= e(hours_view($row['hora_entrada'], $row['hora_salida'])) ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">No hay registros con estos filtros.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php if (!$errors && $total > $perPage): ?><nav class="mt-4" aria-label="Páginas de asistencia"><ul class="pagination justify-content-center">
  <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="asistencias.php?<?= e(http_build_query(array_merge($filters, ['pagina' => $page - 1]))) ?>">Anterior</a></li><?php endif; ?>
  <li class="page-item disabled"><span class="page-link">Página <?= e($page) ?> de <?= e($pages) ?></span></li>
  <?php if ($page < $pages): ?><li class="page-item"><a class="page-link" href="asistencias.php?<?= e(http_build_query(array_merge($filters, ['pagina' => $page + 1]))) ?>">Siguiente</a></li><?php endif; ?>
</ul></nav><?php endif; ?>
<?php page_end(); ?>
