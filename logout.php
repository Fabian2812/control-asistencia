<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
// Cerrar sesión solo acepta POST protegido para evitar cierres por enlaces externos.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
verify_csrf();
// Borra los datos de autenticación y cambia el identificador de la sesión.
$_SESSION = [];
session_regenerate_id(true);
redirect('login.php');
