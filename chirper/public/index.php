<?php

declare(strict_types=1);

function apiSendFatal(string $message): void
{
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $message,
    ], JSON_UNESCAPED_UNICODE);
}

set_exception_handler(function (Throwable $e): void {
    apiSendFatal($e->getMessage() . ' (' . get_class($e) . ' em ' . $e->getFile() . ':' . $e->getLine() . ')');
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        apiSendFatal($error['message'] . ' em ' . $error['file'] . ':' . $error['line']);
    }
});

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/Router.php';

require_once __DIR__ . '/../app/src/configs/Database.php';
require_once __DIR__ . '/../app/src/models/Ticket.php';
require_once __DIR__ . '/../app/src/repositories/TicketRepository.php';
require_once __DIR__ . '/../app/src/controllers/CalledController.php';
require_once __DIR__ . '/../app/src/controllers/UserController.php';

use App\Core\Router;

if (session_status() === PHP_SESSION_NONE) {
	// Serverless runtimes may not have a writable default session.save_path.
	@ini_set('session.save_path', sys_get_temp_dir());
	session_start();
}

$router = new Router();

require __DIR__ . '/../routes/web.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

