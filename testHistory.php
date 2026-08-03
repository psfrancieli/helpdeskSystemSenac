<?php

require __DIR__ . '/chirper/src/controllers/HistoryController.php';

$data = [
    'description' => 'Hoje e sexta feira',
    'data' => new DateTime(),
    'id_chamado' => 1,
    'id_usuario_tecnico' => 3
];

HistoryController::create($data);

# teste
?>