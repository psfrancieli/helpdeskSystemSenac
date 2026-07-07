<?php

$router->get('/api/chamados', [CalledController::class, 'index']);
$router->post('/api/chamados', [CalledController::class, 'store']);
$router->get('/api/criarUsuario', [UserController::class, 'createUser']);
$router->post('/api/criarUsuario', [UserController::class, 'createUser']);


