<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../repositories/TicketRepository.php';


use src\models\Ticket;
use src\repositories\TicketRepository;

class CalledController extends Controller
{
    private TicketRepository $repository;

    public function __construct()
    {
        $this->repository = new TicketRepository();
    }

    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $chamados = $this->repository->listarTodos();

            $this->response([
                'success' => true,
                'data' => $chamados,
            ]);
        } catch (Throwable $e) {
            $this->response([
                'success' => false,
                'error' => 'Erro ao buscar chamados',
            ], 500);
        }
    }

    public function store(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $dados = $this->getBody();

            foreach (['titulo', 'descricao', 'prioridade', 'patrimonio', 'id_categoria', 'id_usuario', 'status'] as $campo) {
                if (!array_key_exists($campo, $dados) || $dados[$campo] === '' || $dados[$campo] === null) {
                    $this->response([
                        'success' => false,
                        'error' => sprintf('Campo obrigatório ausente: %s', $campo),
                    ], 400);
                }
            }

            $ticket = new Ticket(
                null,
                null,
                (string) $dados['titulo'],
                (string) $dados['descricao'],
                $dados['prioridade'] !== '' ? (string) $dados['prioridade'] : null,
                (string) $dados['patrimonio'],
                (string) $dados['status'],
                (int) $dados['id_categoria'],
                (int) $dados['id_usuario'],
                array_key_exists('id_responsavel', $dados) && $dados['id_responsavel'] !== null && $dados['id_responsavel'] !== '' ? (int) $dados['id_responsavel'] : null,
                !empty($dados['data_abertura']) ? new DateTime((string) $dados['data_abertura']) : new DateTime(),
                !empty($dados['data_encerramento']) ? new DateTime((string) $dados['data_encerramento']) : null,
            );

            $this->repository->criarTicket($ticket);

            $this->response([
                'success' => true,
                'message' => 'Chamado criado com sucesso.',
            ], 201);
        } catch (Throwable $e) {
            $this->response([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
