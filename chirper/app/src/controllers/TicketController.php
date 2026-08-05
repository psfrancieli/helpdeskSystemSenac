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

        if (isset($dados['prioridade']) && !in_array($dados['prioridade'], ['baixa', 'media', 'alta'], true)) {
            throw new InvalidArgumentException("Prioridade inválida. Use: baixa, media ou alta.");
        }

        if (isset($dados['status']) && !in_array($dados['status'], ['pendente', 'concluido', 'cancelado'], true)) {
            throw new InvalidArgumentException("Status invalido.");
        }
    }

    public function listarTicket(): array {
        try {
            $dados = $this->repository->listarTodos();
            
            return [
                'status' => 'success',
                'data' => $dados
            ];

        } catch (RuntimeException $e) {
            
            return [
                'status' => 'error',
                'message' => 'Erro ao listar tickets: ' . $e->getMessage()
            ];
        }
    }

    public function exibirTicket(int $id): array {
        try {
            $ticket = $this->repository->encontrarTicketPorId($id);
            
            return [
                'status' => 'success',
                'data' => $ticket->getAll()
            ];
            
        } catch (RuntimeException $e) {

            return [
                'status' => 'error',
                'message' => 'Erro ao buscar Ticket.' 
            ];
        }
    }

    public function criar(array $dadosRequisicao): array {
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

            $this->repository->criarTicket($novoTicket);

            return [
                'status' => 'success',
                'message' => 'Ticket criado com sucesso'
            ];

        } catch (InvalidArgumentException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (RuntimeException $e) {
            return ['status' => 'error', 'message' => error_log($e->getMessage())];
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

        } catch (InvalidArgumentException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];

        } catch (RuntimeException $e) {
            return ['status' => 'error', 'message' => error_log($e->getMessage())];
        }
    }
    
    public function encerrarTicket(int $id, array $dadosRequisicao): array {
        try {
            $status = $dadosRequisicao['status'] ?? 'concluido';
            
            $this->repository->encerrarTicket($id, $status);

            return [
                'status' => 'success',
                'message' => "Ticket {$id} encerrado com sucesso."
            ];

        } catch (RuntimeException $e) {
            return ['status' => 'error', 'message' => 'Não foi possivel encerrar' . $e->getMessage()];
        }
    }
}

//Testes
$controller = new TicketController();

// //Listagem
// $respostaListar = $controller->listarTicket();
// print_r($respostaListar);
// echo "<hr>";

// // Criar
// $dadosNovoTicket = [
//     'id' => '1111',
//     'titulo' => 'Teclado parou de funcionar',
//     'descricao' => 'As teclas de espaço e enter não respondem.',
//     'prioridade' => 'media',
//     'patrimonio' => 'PAT-102030',
//     'status' => 'pendente',
//     'id_categoria' => 2,
//     'id_usuario' => 1,
//     'id_responsavel' => null
// ];
// $respostaCriar = $controller->criar($dadosNovoTicket);
// print_r($respostaCriar);
// echo "<hr>";

// //Exibir pelo id expecifico 
// $idExibir = 1111; 
// $respostaExibir = $controller->exibirTicket($idExibir);
// print_r($respostaExibir);
// echo "<hr>";


// //Atualizar prioridade
// $idAtualizar = 317; 
// $dadosPrioridade = [
//     'prioridade' => 'alta'
// ];
// $respostaAtualizar = $controller->atualizarPrioridade($idAtualizar, $dadosPrioridade);
// print_r($respostaAtualizar);
// echo "<hr>";

// //Encerrar ticket
// $idEncerrar = 317; 
// $dadosEncerrar = [
//     'status' => 'concluido'
// ];
// $respostaEncerrar = $controller->encerrarTicket($idEncerrar, $dadosEncerrar);
// print_r($respostaEncerrar);
// echo "<hr>";

// echo "</pre>";

?>