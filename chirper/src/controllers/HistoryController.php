<?php

date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../models/History.php';
require_once __DIR__ . '/../repositories/HistoryRepository.php';
require_once __DIR__ . '/../services/HistoryService.php';
require_once __DIR__ . '/Controller.php';

class HistoryController extends Controller {

    public function create(array $data) {

        try {
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

            HistoryService::create($history);
            
            $this->response([
                "success" => true,
                "message" => "Historico cadastrado com sucesso."
            ], 201);

        } catch (PDOException $e) {

        return $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);
    }
    }


    public function getId(int $id) {
        try {
            if (empty($id)) {
            throw new InvalidArgumentException("historico não existe");
            }

            $data = HistoryRepository::getById($id);


            return $this->response([
                "success" => true,
                "data" => $data
            ], 201);
        } catch (Throwable $e) {

            return $this->response([
                        "success" => false,
                        "message" => $e->getMessage()
                    ], 400);

        }
    }
}

?>