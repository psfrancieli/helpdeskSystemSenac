<?php

//Importações
use src\repositories\TicketRepository; 
use src\models\Ticket;

require_once __DIR__ . "/../models/Ticket.php";
require_once __DIR__ . "/../repositories/TicketRepository.php";

//Codificação dos metodos
class TicketServices {

    private TicketRepository $repository;

    public function __construct()
    {
        $this->repository = new TicketRepository();
    }

    public function criar(
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
            $titulo,
            $descricao,
            $prioridade,
            $patrimonio,
            $status,
            $id_categoria,
            $id_usuario
        );

        return $this->repository->save($ticket);
    }

    public function atualizar(int $id, array $dadosAtualizados): Ticket {
        $ticket = $this->repository->findById($id);

        if (!$ticket) {
            throw new \Exception("Ticket não encontrado!");
        }

        if (isset($dadosAtualizados['titulo'])) {
            $ticket->setTitulo($dadosAtualizados['titulo']);
        }

        if (isset($dadosAtualizados['descricao'])) {
            $ticket->setDescricao($dadosAtualizados['descricao']);
        }

        return $this->repository->update($ticket);
    }

    public function encerrar(int $id): Ticket {

        $ticket = $this->repository->findById($id);

        if (!$ticket) {
            throw new \Exception("Ticket não encontrado!");
        }

        $ticket->setStatus('encerrado');

        return $this->repository->update($ticket);
    }

    public function atribuirTecnico(int $ticketId, int $tecnicoId): Ticket {
        $ticket = $this->repository->findById($ticketId);

        if (!$ticket) {
            throw new \Exception("Ticket não encontrado!");
        }

        $ticket->setIdResponsavel($tecnicoId);
        $ticket->setStatus("Em andamento");

        return $this->repository->update($ticket); 
    }
}


//Teste

$service = new TicketServices();

$teste = $service->criar(
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