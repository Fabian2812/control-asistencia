<?php
declare(strict_types=1);

// Archivo compartido por todas las páginas. El .env nunca se publica en Git.
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                $value = trim($parts[1]);
                if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                    $value = substr($value, 1, -1);
                }
                if (getenv($key) === false) {
                    putenv($key . '=' . $value);
                }
            }
        }
    }
}

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/Costa_Rica');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_save_path(getenv('SESSION_SAVE_PATH') ?: sys_get_temp_dir());
    session_name('asistencia_sesion');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function db(): PDO
{
    static $connection = null;
    if ($connection === null) {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'control_asistencia';
        if (!ctype_digit($port) || !preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new RuntimeException('Configuración de base de datos inválida.');
        }
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $connection = new PDO($dsn, getenv('DB_USER') ?: 'root', getenv('DB_PASSWORD') ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $connection;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $received = $_POST['csrf_token'] ?? '';
    if (!is_string($received) || !hash_equals(csrf_token(), $received)) {
        http_response_code(419);
        exit('La sesión del formulario expiró. Recargue la página e inténtelo nuevamente.');
    }
}

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
    $stmt = db()->prepare('SELECT estado FROM usuarios WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    if ((int)$stmt->fetchColumn() !== 1) {
        unset($_SESSION['admin_id'], $_SESSION['admin_nombre']);
        header('Location: login.php');
        exit;
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function valid_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function date_view(?string $value): string
{
    return $value ? (new DateTimeImmutable($value))->format('d/m/Y') : '—';
}

function time_view(?string $value): string
{
    return $value ? (new DateTimeImmutable($value))->format('h:i A') : '—';
}

function hours_view(?string $start, ?string $end): string
{
    if (!$start || !$end) {
        return '—';
    }
    $seconds = max(0, (new DateTimeImmutable($end))->getTimestamp() - (new DateTimeImmutable($start))->getTimestamp());
    $minutes = intdiv($seconds, 60);
    return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
}
