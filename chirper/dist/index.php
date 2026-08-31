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
	$isLoginRequest = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
		&& str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/login');

	$rememberMe = false;

	if ($isLoginRequest) {
		$rawBody = file_get_contents('php://input');
		$GLOBALS['__cachedRequestBody'] = $rawBody;
		$decoded = json_decode($rawBody ?: '', true);
		$rememberMe = is_array($decoded) && !empty($decoded['remember']);
	}

	$lifetime = $rememberMe ? 60 * 60 * 24 * 30 : 0;

	session_set_cookie_params([
		'lifetime' => $lifetime,
		'path' => '/',
		'domain' => '',
		'secure' => false,
		'httponly' => true,
		'samesite' => 'Lax',
	]);

	session_start();
}

$router = new Router();

require __DIR__ . '/../routes/web.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);