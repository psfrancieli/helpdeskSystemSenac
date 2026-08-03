<?php

date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . "/../repositories/TicketRepository.php";
require_once __DIR__ . "/../models/Ticket.php";

use src\repositories\TicketRepository;
use src\models\Ticket;

class TicketController {
    
    private TicketRepository $repository;

    public function __construct() {
        $this->repository = new TicketRepository();
    }

    public function listar(): array {
        try {
            $dados = $this->repository->listarTodos();
            
            return [
                'status' => 'success',
                'data' => $dados
            ];

        } catch (RuntimeException $e) {
            
        http_response_code(500);

            return [
                'status' => 'error',
                'message' => 'Erro ao listar tickets: ' . $e->getMessage()
            ];
        }
    }

    public function exibir(int $id): array {
        try {
            $ticket = $this->repository->EncontrarTicketPorId($id);
            
            http_response_code(200);

            return [
                'status' => 'success',
                'data' => $ticket->getAll()
            ];
            
        } catch (RuntimeException $e) {

            http_response_code(404);

            return [
                'status' => 'error',
                'message' => 'Ticket não encontrado.'
            ];
        }
    }

    public function criar(array $dadosRequisicao): array {
        try {
            $novoTicket = new Ticket(
                id: null,
                uuid: null, 
                titulo: $dadosRequisicao['titulo'] ?? '',
                descricao: $dadosRequisicao['descricao'] ?? '',
                prioridade: $dadosRequisicao['prioridade'] ?? null,
                patrimonio: $dadosRequisicao['patrimonio'] ?? '',
                status: $dadosRequisicao['status'] ?? 'Aberto',
                id_categoria: $dadosRequisicao['id_categoria'] ?? null,
                id_usuario: $dadosRequisicao['id_usuario'] ?? 0,
                id_responsavel: $dadosRequisicao['id_responsavel'] ?? null,
                dataAbertura: new DateTime(), 
                dataEncerramento: null
            );

            $this->repository->CriarTicket($novoTicket);

            return [
                'status' => 'success',
                'message' => 'Ticket criado com sucesso!'
            ];

        } catch (InvalidArgumentException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (RuntimeException $e) {
            return ['status' => 'error', 'message' => 'Erro no banco: ' . $e->getMessage()];
        }
    }

    public function atualizarPrioridade(int $id, array $dadosRequisicao): array {
        try {
            if (empty($dadosRequisicao['prioridade'])) {
                throw new InvalidArgumentException("A prioridade é obrigatória.");
            }

            $this->repository->atualizarPrioridadeTicket($id, $dadosRequisicao['prioridade']);

            return [
                'status' => 'success',
                'message' => "Prioridade do ticket {$id} atualizada com sucesso."
            ];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    public function encerrar(int $id, array $dadosRequisicao): array {
        try {
            $status = $dadosRequisicao['status'] ?? 'concluido';
            
            $this->repository->encerrarTicket($id, $status);

            return [
                'status' => 'success',
                'message' => "Ticket {$id} encerrado com sucesso."
            ];

        } catch (RuntimeException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

?>