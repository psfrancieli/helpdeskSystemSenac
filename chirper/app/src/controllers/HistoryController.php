<?php

date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../models/History.php';
require_once __DIR__ . '/../repositories/HistoryRepository.php';
require_once __DIR__ . '/../services/HistoryService.php';
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../utils/token_jwt.php';

class HistoryController extends Controller
{

    public function create(array $data)
    {

        try {
            $history = new History(
                $data['data'],
                $data['description'],
                $data['id_chamado'],
                $data['id_usuario_tecnico']
            );

            HistoryService::create($history);

            $this->response([
                "success" => true,
                "message" => "Historico cadastrado com sucesso."
            ], 201);
        } catch (PDOException $e) {
            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);
        }
    }

    public function getId(int $id)
    {
        try {
            if (empty($id)) {
                throw new InvalidArgumentException("historico não existe");
            }

            $data = HistoryService::getById($id);

            $history = [
                "descricao" => $data->getDescricao(),
                "data" => $data->getData()->format("Y-m-d H:i:s"),
                "id_chamado" => $data->getChamado(),
                "id_usuario_tecnico" => $data->getTecnico()
            ];

            $this->response([
                "success" => true,
                "data" => $history
            ], 200);

            exit;
        } catch (Throwable $e) {
            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);
        }
    }

    public function getByTicketId(int $id)
    {
        try {
            if (empty($id)) {
                throw new InvalidArgumentException("Histórico não existe");
            }

            $dados = HistoryService::getByTicketId($id);

            $historicos = [];

            foreach ($dados as $historico) {
                $historicos[] = [
                    "data" => $historico->getData()->format("Y-m-d H:i:s"),
                    "descricao" => $historico->getDescricao(),
                    "id_chamado" => $historico->getChamado(),
                    "id_usuario_tecnico" => $historico->getTecnico()
                ];
            }

            $this->response([
                "success" => true,
                "data" => $historicos
            ], 200);
        } catch (Throwable $e) {
            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);
        }
    }

    public function getByTicketIdV2(): void
    {
        try {
            validateTokenJWT();

            // Pega o ID do chamado da URL
            $id = isset($_GET['id_chamado'])
                ? (int) $_GET['id_chamado']
                : 0;

            if ($id <= 0) {
                $this->response([
                    "success" => false,
                    "message" => "ID do chamado inválido."
                ], 400);

                return;
            }

            // Busca o histórico
            $dados = HistoryService::getByTicketId($id);

            $historicos = [];

            foreach ($dados as $historico) {
                $historicos[] = [
                    "data" => $historico->getData()->format("Y-m-d H:i:s"),
                    "descricao" => $historico->getDescricao(),
                    "id_chamado" => $historico->getChamado(),
                    "id_usuario_tecnico" => $historico->getTecnico()
                ];
            }

            $this->response([
                "success" => true,
                "data" => $historicos
            ], 200);
        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 500);
        }
    }
}
