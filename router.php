<?php
declare(strict_types=1);
// Router de desarrollo: bloquea archivos de configuración y código interno.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);
if (str_contains($path, '..') || preg_match('~(^|/)(\.|includes/|database\.sql$|README\.md$|router\.php$)~i', $path)) {
    http_response_code(404);
    exit('No encontrado.');
}
if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}
return false;
