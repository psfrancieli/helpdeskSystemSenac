<?php

require_once __DIR__ . '/chirper/src/controllers/HistoryController.php';

$data = [
    'description' => 'Criado Agora pouco',
    'data' => new DateTime(),
    'id_chamado' => 1,
    'id_usuario_tecnico' => 3
];

HistoryController::create($data);

$id = 207;

$tickets = HistoryController::getId($id);

echo $tickets;

?>