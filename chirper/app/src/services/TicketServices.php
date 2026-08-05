<?php
namespace src\services;

//Importações
require_once __DIR__ . "/../models/Ticket.php";
require_once __DIR__ . "/../repositories/TicketRepository.php";

use src\repositories\TicketRepository; 
use src\models\Ticket;
use DateTime;


//Codificação dos metodos
class TicketServices {

    private TicketRepository $repository;

    public function __construct()
    {
        $this->repository = new TicketRepository();
    }

    public function criarTicket(
        string $titulo,
        string $descricao,
        string $prioridade,
        string $patrimonio,
        string $status,
        int $id_categoria,
        int $id_usuario
    ): Ticket
    {
    $prioridadesValidas = ['baixa', 'media', 'alta', 'muito alta'];
    if (!in_array(strtolower($prioridade), $prioridadesValidas)) {
        throw new \Exception("A prioridade informada é inválida.");
    }

    $ticket = new Ticket(
        0,
        null,
        $titulo, 
        $descricao, 
        $prioridade, 
        $patrimonio,
        $status, 
        $id_categoria, 
        $id_usuario, 
        null, 
        new DateTime(), 
        null
    );

    $this->repository->criarTicket($ticket);

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

    public function encerrar(int $id): Ticket {

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


//Teste

// $service = new TicketServices();

// echo "<h3>Testando a conexão e criação...</h3>";

// $novoTicket = $service->criarTicket(
//     'Teste Simples',       
//     'Descrição de teste.', 
//     'alta',                
//     'pat-001',            
//     'pendente',           
//     1,                     
//     1                 
// );

// echo "<pre>";
// print_r($novoTicket);
// echo "</pre>";