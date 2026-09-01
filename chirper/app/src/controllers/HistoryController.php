<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../models/History.php';
require_once __DIR__ . '/../repositories/HistoryRepository.php';
require_once __DIR__ . '/../services/HistoryService.php';
require_once __DIR__ . '/Controller.php';

class HistoryController extends Controller
{
    public function create(array $data)
    {
        try {
            $descricao = isset($data['descricao'])
                ? trim((string) $data['descricao'])
                : '';

            $idChamado = isset($data['id_chamado'])
                ? (int) $data['id_chamado']
                : 0;

            $idUsuario = isset($data['id_usuario'])
                ? (int) $data['id_usuario']
                : 0;

            if ($descricao === '') {
                throw new InvalidArgumentException(
                    'Comentário é obrigatório.'
                );
            }

            if ($idChamado <= 0) {
                throw new InvalidArgumentException(
                    'Chamado inválido.'
                );
            }

            if ($idUsuario <= 0) {
                throw new InvalidArgumentException(
                    'Usuário inválido.'
                );
            }

            $history = new History(
                $descricao,
                $idChamado,
                $idUsuario
            );

            $result = HistoryService::create($history);

            if (!$result) {
                throw new RuntimeException(
                    'Não foi possível salvar o comentário.'
                );
            }

            $this->response([
                'success' => true,
                'message' => 'Comentário adicionado com sucesso.'
            ], 201);

        } catch (Throwable $e) {

            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function getId(int $id)
    {
        try {

            if ($id <= 0) {
                throw new InvalidArgumentException(
                    'Histórico inválido.'
                );
            }

            $data = HistoryService::getById($id);

            $history = [
                'descricao' => $data->getDescricao(),
                'data' => $data->getData()->format('Y-m-d H:i:s'),
                'id_chamado' => $data->getChamado(),
                'id_usuario' => $data->getUsuario()
            ];

            $this->response([
                'success' => true,
                'data' => $history
            ], 200);

        } catch (Throwable $e) {

            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function getByTicketId(int $id)
    {
        try {

            if ($id <= 0) {
                throw new InvalidArgumentException(
                    'Chamado inválido.'
                );
            }

            $dados = HistoryService::getByTicketId($id);

            $historicos = [];

            foreach ($dados as $historico) {

                $historicos[] = [
                    'data' => $historico->getData()
                        ->format('Y-m-d H:i:s'),

                    'descricao' => $historico->getDescricao(),

                    'id_chamado' => $historico->getChamado(),

                    'id_usuario' => $historico->getUsuario()
                ];
            }

            $this->response([
                'success' => true,
                'data' => $historicos
            ], 200);

        } catch (Throwable $e) {

            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}