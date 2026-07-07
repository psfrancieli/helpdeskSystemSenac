<?php

$router->get('/api/chamados', [TicketController::class, 'index']);
$router->post('/api/chamados', [TicketController::class, 'store']);
$router->get('/api/criarUsuario', [UserController::class, 'createUser']);
$router->post('/api/criarUsuario', [UserController::class, 'createUser']);


