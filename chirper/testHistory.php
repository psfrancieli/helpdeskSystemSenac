<?php

require_once './chirper/src/controllers/HistoryController.php';

$data = [
    'description' => 'Técnico atribuído alterado',
    'data' => new DateTime(),
    'id_chamado' => 1,
    'id_usuario_tecnico' => 3
];

HistoryController::create($data);

# teste
?>