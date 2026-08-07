<?php

date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . "/../services/TicketServices.php";
require_once __DIR__ . "/../models/Ticket.php";
require_once __DIR__ . "/Controller.php";

use src\models\Ticket;
use src\services\TicketServices;

class TicketController extends Controller {
    
    private TicketServices $services;

    public function __construct() {
        $this->services = new TicketServices();
    }

    private function validarDadosTicket(array $dados): void {
        if (empty($dados['titulo'])) {
            throw new InvalidArgumentException("O titulo é obrigatório.");
        }

        if (empty($dados['descricao'])) {
            throw new InvalidArgumentException("A descrição é obrigatória.");
        }

        if (empty($dados['id_usuario'])) {
            throw new InvalidArgumentException("O usuario responsável pela abertura é obrigatório");
        }

        if (isset($dados['prioridade']) && !in_array($dados['prioridade'], ['baixa', 'media', 'alta', 'muito alta'], true)) {
            throw new InvalidArgumentException("Prioridade inválida. Use: baixa, media, alta ou muito alta.");
        }

        if (isset($dados['status']) && !in_array($dados['status'], ['pendente', 'concluido', 'cancelado'], true)) {
            throw new InvalidArgumentException("Status invalido.");
        }
    }
    public function listarTicket(): void {
        try {
            $dados = $this->services->listarTudo();
            
            $this->response([
                "success" => true,
                "data" => $dados
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }
    public function exibirTicket(int $id): void {
        try {
            $ticket = $this->services->exibirTicket($id);
            
            $resultado = [
                "id" => $ticket->getId(),
            ];

            $this->response([
                "success" => true,
                "data" => $resultado
            ]);
            
        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }
    public function criarTicket(array $dadosRequisicao): void {
        try {

            $this->validarDadosTicket($dadosRequisicao);

            $novoTicket = new Ticket(
                id: null,
                uuid: null, 
                titulo: $dadosRequisicao['titulo'] ?? '',
                descricao: $dadosRequisicao['descricao'] ?? '',
                prioridade: $dadosRequisicao['prioridade'] ?? null,
                patrimonio: $dadosRequisicao['patrimonio'] ?? '',
                status: $dadosRequisicao['status'] ?? 'pendente',
                id_categoria: $dadosRequisicao['id_categoria'] ?? null,
                id_usuario: $dadosRequisicao['id_usuario'] ?? 0,
                id_responsavel: $dadosRequisicao['id_responsavel'] ?? null,
                dataAbertura: new DateTime(), 
                dataEncerramento: null
            );

            $this->services->criarTicket($novoTicket);

            $this->response([
                "success" => true,
                "data" => [
                    "titulo" => $novoTicket->getTitulo(),
                    "descricao" => $novoTicket->getDescricao(),
                    "prioridade" => $novoTicket->getPrioridade(),
                    "status" => $novoTicket->getStatus(),
                ]
            ]);

        } catch (InvalidArgumentException $e) {
            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        } catch (\Throwable $e) {
            $this->response([
                "success" => false,
                "message" => ($e->getMessage())
            ], 400);
        }
    }
    public function atualizarPrioridade(int $id, array $dadosRequisicao): void {
        try {
            if (empty($dadosRequisicao['prioridade'])) {
                throw new InvalidArgumentException("A prioridade é obrigatória.");
            }

            $this->services->atualizarPrioridade($id, $dadosRequisicao);

            $this->response([
                "success" => true,
                "message" => "Prioridade do ticket {$id} atualizada com sucesso."
            ]);

        } catch (InvalidArgumentException $e) {
            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        } catch (\Throwable $e) {
            $this->response([
                "success" => false,
                "message" => ($e->getMessage())
            ], 400);
        }
    }
    public function encerrarTicket(int $id): void {
        try {
            $this->services->encerrarTicket($id);

            $this->response([
                "success" => true,
                "message" => "Ticket do id: {$id} Encerrado."
            ]);

        } catch (\Throwable $e) {
            $this->response([
                "success" => false,
                "message" => 'Não foi possivel encerrar: ' . $e->getMessage()
            ], 400);
        }

    }
}


?>