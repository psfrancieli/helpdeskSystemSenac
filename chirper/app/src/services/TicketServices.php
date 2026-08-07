<?php
namespace src\services;

//Importações
require_once __DIR__ . "/../models/Ticket.php";
require_once __DIR__ . "/../repositories/TicketRepository.php";

use src\repositories\TicketRepository; 
use src\models\Ticket;


//Codificação dos metodos
class TicketServices {

    private TicketRepository $repository;

    public function __construct()
    {
        $this->repository = new TicketRepository();
    }

    public function listarTudo(): array {
        $dados = $this->repository->listarTodos();
        $objetos = [];

        if(!$dados) {
            return []; 
        }

        foreach ($dados as $linha) {
            $ticket = new Ticket(
                id: $linha['id'],
                uuid: $linha['uuid'],
                titulo: $linha['titulo'],
                descricao: $linha['descricao'], 
                prioridade: $linha['prioridade'],
                patrimonio: $linha['patrimonio'],
                status: $linha['status'],
                dataAbertura: new \DateTime($linha['data_abertura']),
                dataEncerramento: $linha['data_encerramento'] ? new \DateTime($linha['data_encerramento']) : null
            );

            $objetos[] = $ticket;
        }

        return $objetos;
    }

    public function exibirTicket(int $id): Ticket {
        if($id <= 0) {
            throw new \InvalidArgumentException("ID está incorreto!");
        }

        $ticket = $this->repository->EncontrarTicketPorId($id);

        if(!$ticket) {
            throw new \RuntimeException("Não foi possivel encontrar o ticket.");
        }

        return $ticket;
    }

    public function criarTicket(Ticket $ticket): Ticket
    {
        $prioridadesValidas = ['baixa', 'media', 'alta', 'muito alta'];
        if (!in_array(strtolower($ticket->getPrioridade()), $prioridadesValidas)) {
            throw new \Exception("A prioridade informada é inválida.");
        }

        $statusValidos = ['pendente', 'concluido', 'cancelado'];
            $status = strtolower(trim((string) ($ticket->getStatus() ?: 'pendente')));
            if (!in_array($status, $statusValidos, true)) {
                throw new \InvalidArgumentException("O status informado é inválido.");
            }

        $idUsuario = $ticket->getIdUsuario();
            if (empty($idUsuario) || (int) $idUsuario <= 0) {
                throw new \InvalidArgumentException("O usuário solicitante é obrigatório.");
            }

        $ticket->setStatus($status);

        $this->repository->CriarTicket($ticket);

        return $ticket;

    }

    public function atualizarPrioridade(int $id, array $dadosAtualizados): Ticket {
        $ticket = $this->repository->encontrarTicketPorId($id);

        if (!$ticket) {
            throw new \Exception("Ticket não encontrado!");
        }

        if ($ticket->getStatus() === 'concluido') {
            throw new \Exception("Não é possível alterar a prioridade de um ticket que ja tenha sido encerrado.");
        }

        if (isset($dadosAtualizados['prioridade'])) {
           $novaPrioridade = strtolower($dadosAtualizados['prioridade']);

        $prioridadesValidas = ['baixa', 'media', 'alta', 'muito alta'];
        if (!in_array($novaPrioridade, $prioridadesValidas)) {
            throw new \Exception("Prioridade inválida!");
        }

        if ($ticket->getPrioridade() !== $novaPrioridade) {
            $this->repository->atualizarPrioridadeTicket($id, $novaPrioridade);   
            $ticket->setPrioridade($novaPrioridade);
        }
    }

        return $ticket;
    }

    public function encerrarTicket(int $id): Ticket {

        $ticket = $this->repository->encontrarTicketPorId($id);

        if (!$ticket) {
            throw new \Exception("Ticket não encontrado!");
        }

        if ($ticket->getStatus() === 'concluido') {
            throw new \Exception("Não é possivel encerrar um ticket ja concluido.");
        }


        $this->repository->encerrarTicket($id, 'concluido');
        $ticket->setStatus('concluido');

        return $ticket;
    }

    public function atribuirTecnico(int $ticketId, int $idResponsavel): Ticket {
        $ticket = $this->repository->encontrarTicketPorId($ticketId);

        if (!$ticket) {
            throw new \Exception("Ticket não encontrado!");
        }

        $this->repository->atribuirResponsavelTicket($ticketId, $idResponsavel);
        $ticket->setIdResponsavel($idResponsavel);

        return $ticket;
    }
}
?>