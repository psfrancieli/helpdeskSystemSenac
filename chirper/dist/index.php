<?php

declare(strict_types=1);

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
	session_start();
}

$router = new Router();

require __DIR__ . '/../routes/web.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

