<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/asistencia_query.php';
require_admin();
[, $errors, $where, $params] = attendance_filters($_GET);
if ($errors) {
    http_response_code(400);
    exit('Filtros inválidos.');
}
$stmt = db()->prepare('SELECT a.fecha, a.hora_entrada, a.hora_salida, e.nombre, e.cedula FROM asistencias a JOIN empleados e ON e.id = a.empleado_id' . $where . ' ORDER BY a.fecha DESC, a.hora_entrada DESC, a.id DESC');
$stmt->execute($params);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="asistencias-' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store');
$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Cédula', 'Empleado', 'Fecha', 'Entrada', 'Salida', 'Horas trabajadas'], ';');
while ($row = $stmt->fetch()) {
    // Evita que Excel interprete datos de usuarios como fórmulas.
    $safe = static fn(string $value): string => preg_match('/^[\s]*[=+@-]/u', $value) ? "'" . $value : $value;
    fputcsv($output, [
        $safe($row['cedula']),
        $safe($row['nombre']),
        date_view($row['fecha']),
        time_view($row['hora_entrada']),
        time_view($row['hora_salida']),
        hours_view($row['hora_entrada'], $row['hora_salida']),
    ], ';');
}
fclose($output);
