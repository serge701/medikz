<?php
declare(strict_types=1);

/**
 * ÚNICO punto de entrada de la aplicación.
 * Todas las peticiones pasan por aquí (ver .htaccess).
 */

use App\Core\Config;
use App\Core\Auth;
use App\Core\Router;

define('BASE_DIR', __DIR__);

// --- Autoloader PSR-4 propio (no requiere Composer para el cimiento) ---
spl_autoload_register(static function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = BASE_DIR . '/app/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Si más adelante instalas librerías con Composer (Dompdf, QR, PHPMailer),
// este bloque las cargará automáticamente.
if (is_file(BASE_DIR . '/vendor/autoload.php')) {
    require BASE_DIR . '/vendor/autoload.php';
}

// --- Bootstrap ---
Config::boot();

$app = Config::get('app');
if ($app['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_DIR . '/storage/logs/php-error.log');
}

require BASE_DIR . '/app/Core/helpers.php';
require BASE_DIR . '/app/Views/errors/_error_helper.php';

Auth::startSession();

// --- Rutas ---
$router = new Router();
(require BASE_DIR . '/routes.php')($router);

try {
    $router->dispatch();
} catch (\Throwable $e) {
    if ($app['debug']) {
        http_response_code(500);
        echo '<pre style="padding:1rem;font-family:monospace;">';
        echo 'Error: ' . htmlspecialchars($e->getMessage()) . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        error_log($e->getMessage());
        http_response_code(500);
        echo 'Ocurrió un error. Intenta más tarde.';
    }
}
