<?php

require_once __DIR__ . '/../repositories/ChamadoRepository.php';

class CalledController
{
    private ChamadoRepository $repository;

    public function __construct()
    {
        $this->repository = new ChamadoRepository();
    }

    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');

        try {
            $chamados = $this->repository->listarTodos();
            echo json_encode(['success' => true, 'data' => $chamados]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao buscar chamados']);
        }
    }
}
