<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function attendance_filters(array $input): array
{
    $values = [];
    $errors = [];
    foreach (['empleado', 'cedula', 'fecha', 'desde', 'hasta'] as $key) {
        $values[$key] = trim((string)($input[$key] ?? ''));
    }
    foreach (['empleado', 'cedula'] as $key) {
        if (mb_strlen($values[$key]) > 120) {
            $errors[] = 'El filtro de ' . $key . ' es demasiado largo.';
        }
    }
    foreach (['fecha', 'desde', 'hasta'] as $key) {
        if ($values[$key] !== '' && !valid_date($values[$key])) {
            $errors[] = 'La fecha de ' . $key . ' no es válida.';
        }
    }
    if ($values['desde'] !== '' && $values['hasta'] !== '' && $values['desde'] > $values['hasta']) {
        $errors[] = 'La fecha inicial debe ser anterior o igual a la fecha final.';
    }
    $where = [];
    $params = [];
    if ($values['empleado'] !== '') {
        $where[] = 'e.nombre LIKE ?';
        $params[] = '%' . $values['empleado'] . '%';
    }
    if ($values['cedula'] !== '') {
        $where[] = 'e.cedula LIKE ?';
        $params[] = '%' . $values['cedula'] . '%';
    }
    if ($values['fecha'] !== '') {
        $where[] = 'a.fecha = ?';
        $params[] = $values['fecha'];
    }
    if ($values['desde'] !== '') {
        $where[] = 'a.fecha >= ?';
        $params[] = $values['desde'];
    }
    if ($values['hasta'] !== '') {
        $where[] = 'a.fecha <= ?';
        $params[] = $values['hasta'];
    }
    return [$values, $errors, $where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}
