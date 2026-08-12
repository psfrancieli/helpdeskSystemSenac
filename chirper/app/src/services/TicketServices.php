<?php
namespace src\services;

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

        $ticket = $this->repository->encontrarTicketPorId($id);

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

    public function atualizarStatus(int $id, string $novoStatus): Ticket {
        $ticket = $this->repository->encontrarTicketPorId($id);

        if (!$ticket) {
            throw new \Exception("Ticket não encontrado!");
        }

        $statusValidos = ['pendente', 'concluido', 'cancelado'];
        if (!in_array(strtolower($novoStatus), $statusValidos)) {
            throw new \Exception("Status inválido!");
        }

        if ($ticket->getStatus() !== strtolower($novoStatus)) {
            $this->repository->atualizarStatusTicket($id, strtolower($novoStatus));
            $ticket->setStatus(strtolower($novoStatus));
        }

        return $ticket;
    }
}

// =========================================================================
// BLOCO DE TESTES
// =========================================================================

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// echo "<h1>Testes do TicketServices</h1>";

// $service = new TicketServices();

// // =========================================================================
// // 1. TESTE: LISTAR TUDO
// // =========================================================================
// echo "<h3>1. Listar Tudo</h3>";
// try {
//     $todos = $service->listarTudo();
//     echo "Sucesso! Foram encontrados " . count($todos) . " chamados.<br>";
//     echo "<pre>";
//     print_r($todos);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 2. TESTE: CRIAR TICKET
// // =========================================================================
// echo "<h3>2. Criar Ticket</h3>";
// try {
//     $novoTicket = new Ticket(
//         id: null,
//         uuid: null,
//         titulo: "Mouse parou de funcionar",
//         descricao: "O clique direito não responde mais.",
//         prioridade: "baixa",
//         patrimonio: "HW-12345",
//         status: "pendente",
//         id_categoria: 1, // Hardware
//         id_usuario: 5,   // ID de um usuário válido
//         id_responsavel: null,
//         dataAbertura: new \DateTime(),
//         dataEncerramento: null
//     );
    
//     $ticketCriado = $service->criarTicket($novoTicket);
//     echo "Sucesso! Ticket criado. Veja o objeto abaixo:<br>";
//     echo "<pre>";
//     print_r($ticketCriado);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao criar:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // VARIÁVEL PARA OS PRÓXIMOS TESTES (Mude para o ID de um chamado que exista)
// // =========================================================================
// $idTeste = 23; 
// echo "<hr><i>Rodando testes de atualização para o Ticket ID: {$idTeste}</i><hr>";

// // =========================================================================
// // 3. TESTE: EXIBIR TICKET
// // =========================================================================
// echo "<h3>3. Exibir Ticket</h3>";
// try {
//     $meuTicket = $service->exibirTicket($idTeste);
//     echo "Sucesso! Veja o objeto encontrado:<br>";
//     echo "<pre>";
//     print_r($meuTicket);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao exibir:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 4. TESTE: ATUALIZAR PRIORIDADE
// // =========================================================================
// echo "<h3>4. Atualizar Prioridade</h3>";
// try {
//     $dadosFormulario = ['prioridade' => 'alta']; 
    
//     $ticketAtualizado = $service->atualizarPrioridade($idTeste, $dadosFormulario);
//     echo "Sucesso! Prioridade atualizada. Veja o objeto modificado:<br>";
//     echo "<pre>";
//     print_r($ticketAtualizado);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao atualizar prioridade:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 5. TESTE: ATRIBUIR TÉCNICO
// // =========================================================================
// echo "<h3>5. Atribuir Técnico</h3>";
// try {
//     $idDoTecnico = 2; // Coloque um ID válido da sua tabela de usuários
//     $ticketAtribuido = $service->atribuirTecnico($idTeste, $idDoTecnico);
//     echo "Sucesso! Técnico atribuído. Veja o objeto modificado:<br>";
//     echo "<pre>";
//     print_r($ticketAtribuido);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao atribuir técnico:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 6. TESTE: ATUALIZAR STATUS 
// // =========================================================================
// echo "<h3>6. Atualizar Status</h3>";
// try {
//     $ticketStatus = $service->atualizarStatus($idTeste, 'pendente');
//     echo "Sucesso! Status atualizado. Veja o objeto modificado:<br>";
//     echo "<pre>";
//     print_r($ticketStatus);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao atualizar status:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 7. TESTE: ENCERRAR TICKET
// // =========================================================================
// echo "<h3>7. Encerrar Ticket</h3>";
// try {
    
//     $ticketEncerrado = $service->encerrarTicket($idTeste);
//     echo "Sucesso! Ticket encerrado. Veja o objeto fechado:<br>";
//     echo "<pre>";
//     print_r($ticketEncerrado);
//     echo "</pre>";
//     echo "<i>Teste comentado para não fechar o ticket no banco sem querer. Descomente o bloco para testar.</i><br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao encerrar:</b> " . $e->getMessage() . "<br>";
// }
?>