<?php

require __DIR__ . '/src/controllers/HistoryController.php';

$data = [
    'description' => 'Teste Inserindoo',
    'data' => new DateTime(),
    'id_chamado' => 1,
    'id_usuario_tecnico' => 3
];

HistoryController::create($data);

# teste
?>