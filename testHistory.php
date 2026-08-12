<?php

require_once __DIR__ . '/chirper/src/controllers/HistoryController.php';

$data = [
    'description' => 'Atualizado a descriçao',
    'data' => new DateTime(),
    'id_chamado' => 1,
    'id_usuario_tecnico' => 3
];


$history = new HistoryController();

$history->getByTicketId(4);

?>