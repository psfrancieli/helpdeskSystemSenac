<?php

require_once __DIR__ . '/chirper/src/controllers/HistoryController.php';

$data = [
    'description' => 'Criado Agora pouquinhoo',
    'data' => new DateTime(),
    'id_chamado' => 1,
    'id_usuario_tecnico' => 3
];

$history = new HistoryController();

// $history->create($data);

$response = $history->getByTicketId(1);

echo $response;

?>