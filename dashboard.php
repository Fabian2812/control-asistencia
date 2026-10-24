<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_admin();
// Calcula indicadores diarios y totales a partir de empleados y asistencias.
$today = date('Y-m-d');
$stats = [
    'total' => (int)db()->query('SELECT COUNT(*) FROM empleados')->fetchColumn(),
    'activos' => (int)db()->query('SELECT COUNT(*) FROM empleados WHERE estado = 1')->fetchColumn(),
];
$stmt = db()->prepare('SELECT COUNT(DISTINCT empleado_id) FROM asistencias WHERE fecha = ?');
$stmt->execute([$today]);
$stats['ingresaron'] = (int)$stmt->fetchColumn();
$stats['dentro'] = (int)db()->query('SELECT COUNT(DISTINCT a.empleado_id) FROM asistencias a JOIN empleados e ON e.id = a.empleado_id WHERE a.hora_salida IS NULL AND e.estado = 1')->fetchColumn();
// Cada entrada cuenta como una marcación; una salida registrada suma otra.
$stmt = db()->prepare('SELECT COALESCE(SUM(1 + (hora_salida IS NOT NULL)), 0) FROM asistencias WHERE fecha = ?');
$stmt->execute([$today]);
$stats['registros'] = (int)$stmt->fetchColumn();
// Limita la tabla inicial a los ocho turnos más recientes del día.
$stmt = db()->prepare('SELECT a.fecha, a.hora_entrada, a.hora_salida, e.nombre, e.cedula FROM asistencias a JOIN empleados e ON e.id = a.empleado_id WHERE a.fecha = ? ORDER BY a.hora_entrada DESC LIMIT 8');
$stmt->execute([$today]);
$recent = $stmt->fetchAll();
page_start('Dashboard', true);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div><h1 class="page-title h2 mb-1">Dashboard</h1><p class="text-muted mb-0">Bienvenido, <?= e($_SESSION['admin_nombre'] ?? 'Administrador') ?>. Resumen del <?= e(date_view($today)) ?>.</p></div>
  <a class="btn btn-primary" href="empleado_form.php">+ Nuevo empleado</a>
</div>
<div class="row g-3 mb-4">
  <?php foreach ([['Total de empleados', 'total'], ['Empleados activos', 'activos'], ['Ingresaron hoy', 'ingresaron'], ['Actualmente dentro', 'dentro'], ['Registros de hoy', 'registros']] as [$label, $key]): ?>
    <div class="col-6 col-lg"><div class="card stat-card h-100"><div class="card-body"><div class="text-muted small mb-2"><?= e($label) ?></div><div class="stat-number"><?= e($stats[$key]) ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<div class="card"><div class="card-header d-flex justify-content-between align-items-center py-3"><h2 class="h5 mb-0">Marcaciones recientes</h2><a href="asistencias.php" class="btn btn-outline-primary btn-sm">Ver historial</a></div>
  <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Empleado</th><th>Cédula</th><th>Entrada</th><th>Salida</th><th>Horas</th></tr></thead><tbody>
    <?php foreach ($recent as $row): ?><tr><td><?= e($row['nombre']) ?></td><td><?= e($row['cedula']) ?></td><td><?= e(time_view($row['hora_entrada'])) ?></td><td><?= e(time_view($row['hora_salida'])) ?></td><td><?= e(hours_view($row['hora_entrada'], $row['hora_salida'])) ?></td></tr><?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="5" class="text-center text-muted py-4">Todavía no hay marcaciones hoy.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<?php page_end(); ?>
