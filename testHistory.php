<?php

require_once __DIR__ . '/chirper/src/controllers/HistoryController.php';

$data = [
    'description' => 'Feito al',
    'data' => new DateTime(),
    'id_chamado' => 2,
    'id_usuario_tecnico' => 3
];

$history = new HistoryController();

$history->create($data);



?>