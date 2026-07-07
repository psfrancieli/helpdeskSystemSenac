<?php

date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../models/History.php';
require_once __DIR__ . '/../repositories/HistoryRepository.php';

class HistoryController{

    public static function create(array $data) {

        $history = new History(
            $data['data'],
            $data['description'],
            $data['id_chamado'],
            $data['id_usuario_tecnico']
            );

        $history->setDescricao($data['description']);
        $history->setData($data['data']);
        $history->setChamado($data['id_chamado']);
        $history->setTecnico($data['id_usuario_tecnico']);

        HistoryRepository::create($history);
    }


    public static function getId(int $id) {
        if (empty($id)) {
            throw new InvalidArgumentException("historico não existe");
        }
        return HistoryRepository::getById($id);
    }
}

?>