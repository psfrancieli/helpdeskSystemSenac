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
        $ticket = new Ticket (
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

        $this->repository->CriarTicket($ticket);

        return $ticket;
    }

    public function atualizarPrioridade(int $id, array $dadosAtualizados): Ticket {
        $ticket = $this->repository->EncontrarTicketPorId($id);

        if (!$ticket) {
            throw new \Exception("Ticket não encontrado!");
        }

        if (isset($dadosAtualizados['prioridade'])) {
           $novaPrioridade = $dadosAtualizados['prioridade'];

            $this->repository->atualizarPrioridadeTicket($id, $novaPrioridade);   
            $ticket->setPrioridade($novaPrioridade);
        }

        return $ticket;
    }

    public function encerrar(int $id): Ticket {

        $ticket = $this->repository->EncontrarTicketPorId($id);

        if (!$ticket) {
            throw new \Exception("Ticket não encontrado!");
        }

        $this->repository->encerrarTicket($id, 'encerrado');
        $ticket->setStatus('encerrado');

        return $ticket;
    }

    public function atribuirTecnico(int $ticketId, int $idResponsavel): Ticket {
        $ticket = $this->repository->EncontrarTicketPorId($ticketId);

        if (!$ticket) {
            throw new \Exception("Ticket não encontrado!");
        }

        $this->repository->atribuirResponsavelTicket($ticketId, $idResponsavel);
        $ticket->setIdResponsavel($idResponsavel);

        return $ticket;
}
}


//Teste

$service = new TicketServices();

$teste = $service->criarTicket(
    titulo: "Erro no sistema",
    descricao: "Sistema com defeito de dados",
    prioridade: "Alta",
    patrimonio: "pat-001",
    status: "Em andamento",
    id_categoria: 1,
    id_usuario: 1

);

echo "<pre>";
print_r($teste->getAll());
echo "</pre>";