<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$config = require BASE_PATH . '/config/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Rome');

if (($config['app']['env'] ?? 'production') === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

require BASE_PATH . '/app/Support/Autoloader.php';
Archium\Support\Autoloader::register(BASE_PATH . '/app', 'Archium');
require BASE_PATH . '/app/Support/helpers.php';

use Archium\Support\Logger;
use Archium\Support\Router;
use Archium\Support\SecurityHeaders;
use Archium\Support\Session;

Logger::configure($config['security']['log_path']);
Session::start($config);
SecurityHeaders::apply($config);

$router = new Router();
require BASE_PATH . '/routes/web.php';
require BASE_PATH . '/routes/api.php';

try {
    echo $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/', $config);
} catch (\Throwable $e) {
    Logger::error('Eccezione non gestita', [
        'msg'  => $e->getMessage(),
        'file' => $e->getFile() . ':' . $e->getLine(),
    ]);
    http_response_code(500);
    echo 'Errore interno del server.';
}