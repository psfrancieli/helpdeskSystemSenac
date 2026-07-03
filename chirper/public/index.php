<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/Router.php';

require_once __DIR__ . '/../app/configs/Database.php';
require_once __DIR__ . '/../app/repositories/ChamadoRepository.php';
require_once __DIR__ . '/../app/Controllers/CalledController.php';

use App\Core\Router;

$router = new Router();

require __DIR__ . '/../routes/web.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

