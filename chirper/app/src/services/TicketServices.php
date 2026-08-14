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

    public function atualizarStatus(int $id, array $statusArray): Ticket {
        $ticket = $this->repository->encontrarTicketPorId($id);

        if (!$ticket) {
            throw new \Exception("Ticket não encontrado!");
        }

        $statusValidos = ['pendente', 'concluido', 'cancelado'];
        $novoStatus = $statusArray['status'] ?? '';
        if (!in_array(strtolower($novoStatus), $statusValidos)) {
            throw new \Exception("Status inválido!");
        }

        if ($ticket->getStatus() === strtolower($novoStatus)) {
            throw new \Exception("O status do ticket já está definido como '{$novoStatus}'.");
        }

        if ($ticket->getStatus() !== strtolower($novoStatus)) {
            $this->repository->atualizarStatusTicket($id, strtolower($novoStatus));
            $ticket->setStatus(strtolower($novoStatus));
        }

        return $ticket;
    }

    public function buscaTicketsPorDataAbertura(\DateTime $data): array {
        $tickets = $this->repository->buscaPorDataAbertura($data);
        if(!$tickets) {
            return []; 
        }

        $dadosLimpos = [];

        foreach ($tickets as $ticket) {
            $dadosLimpos[] = $ticket->getAll(); 
        }
        return $dadosLimpos;
    }

    public function buscaTicketsPorDataEncerramento(\DateTime $data): array {
        $tickets = $this->repository->buscaPorDataEncerramento($data);
        if(!$tickets) {
            return []; 
        }
        $dadosLimpos = [];

        foreach ($tickets as $ticket) {
            $dadosLimpos[] = $ticket->getAll(); 
        }
        return $dadosLimpos;
    }

    public function buscaTicketsStatus(string $status): array {
        $tickets = $this->repository->buscarTicketsPorStatus($status);
        if(!$tickets) {
            return []; 
        }
        $dadosLimpos = [];

        foreach ($tickets as $ticket) {
            $dadosLimpos[] = $ticket->getAll(); 
        }
        return $dadosLimpos;
    }

    public function buscarTicketsPorCategoria(int $idCategoria): array {
        $tickets = $this->repository->buscarTicketsPorCategoria($idCategoria);
        if(!$tickets) {
            return []; 
        }
        $dadosLimpos = [];

        foreach ($tickets as $ticket) {
            $dadosLimpos[] = $ticket->getAll(); 
        }
        return $dadosLimpos;
    }

    public function contarChamadosPorPeriodo(\DateTime $dataInicio, \DateTime $dataFim): int {
        return $this->repository->contarChamadosPorPeriodo($dataInicio, $dataFim);
    }

    public function contarChamados(): int {
        return $this->repository->contarChamados();
    }

    public function contarChamadosResolvidos(): int {
        return $this->repository->contarChamadosResolvidos();
    }

    public function contarChamadosPendentes(): int {
        return $this->repository->contarChamadosPendentes();
    }

    public function contarChamadosCancelados(\DateTime $dataInicio, \DateTime $dataFim): int {
        return $this->repository->contarChamadosCancelados($dataInicio, $dataFim);
    }

    public function contarChamadosCanceladosPorPeriodo(\DateTime $dataInicio, \DateTime $dataFim): int {
        return $this->repository->contarChamadosCanceladosPorPeriodo($dataInicio, $dataFim);
    }

    public function buscarTicketsPorUsuario(int $idUsuario): array {
        $tickets = $this->repository->buscarTicketPorUserId($idUsuario);
        if(!$tickets) {
            return []; 
        }
        $dadosLimpos = [];

        foreach ($tickets as $ticket) {
            $dadosLimpos[] = $ticket->getAll(); 
        }
        return $dadosLimpos;
    }

    public function buscarTicketsPorResponsavel(int $idResponsavel): array {
        $tickets = $this->repository->buscarTicketPorResponsavelId($idResponsavel);
        if(!$tickets) {
            return []; 
        }
        $dadosLimpos = [];

        foreach ($tickets as $ticket) {
            $dadosLimpos[] = $ticket->getAll(); 
        }
        return $dadosLimpos;
    }

    public function buscarTicketsPornome(string $nome): array {
        $tickets = $this->repository->buscarChamadosNomeUser($nome);
        if(!$tickets) {
            return []; 
        }
        $dadosLimpos = [];

        foreach ($tickets as $ticket) {
            $dadosLimpos[] = $ticket->getAll(); 
        }
        return $dadosLimpos;
    }
}

// =========================================================================
// BLOCO DE TESTES
// =========================================================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testes do TicketServices</h1>";

$service = new TicketServices();

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
$idTeste = 82; 
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
// } catch (\Exception $e) {
//     echo "<b>Erro ao encerrar:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 8. TESTE: BUSCA POR DATA DE ABERTURA
// // =========================================================================
// echo "<h3>8. Busca por Data de Abertura</h3>";
// try {
//     $dataBusca = new \DateTime('2026-08-11');
//     $ticketsPorData = $service->buscaTicketsPorDataAbertura($dataBusca);
//     echo "Sucesso! Foram encontrados " . count($ticketsPorData) . " chamados abertos em " . $dataBusca->format('Y-m-d') . ".<br>";
//     echo "<pre>";
//     print_r($ticketsPorData);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro na busca por data:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 9. TESTE: BUSCA POR DATA DE ENCERRAMENTO
// // =========================================================================
// echo "<h3>9. Busca por Data de Encerramento</h3>";
// try {
//     $data = new \DateTime('2026-08-08');
//     $ticketsEncerrados = $service->buscaTicketsPorDataEncerramento($data);
//     echo "Sucesso! Foram encontrados " . count($ticketsEncerrados) . " chamados encerrados em " . $data->format('Y-m-d') . ".<br>";
//     echo "<pre>";
//     print_r($ticketsEncerrados);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro na busca por data de encerramento:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 10. TESTE: BUSCA POR STATUS
// // =========================================================================
// echo "<h3>10. Busca por Status</h3>";
// try {
//     $statusBusca = 'cancelado';
//     $ticketsPorStatus = $service->buscaTicketsStatus($statusBusca);
//     echo "Sucesso! Foram encontrados " . count($ticketsPorStatus) . " chamados com status '{$statusBusca}'.<br>";
//     echo "<pre>";
//     print_r($ticketsPorStatus);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro na busca por status:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 11. TESTE: BUSCA POR CATEGORIA
// // =========================================================================
// echo "<h3>11. Busca por Categoria</h3>";
// try {
//     $idCategoria = 1; 
//     $ticketsPorCategoria = $service->buscarTicketsPorCategoria($idCategoria);
//     echo "Sucesso! Foram encontrados " . count($ticketsPorCategoria) . " chamados na categoria ID {$idCategoria}.<br>";
//     echo "<pre>";
//     print_r($ticketsPorCategoria);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro na busca por categoria:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 12. TESTE: CONTAR CHAMADOS POR PERÍODO
// =========================================================================
// echo "<h3>12. Contar Chamados por Período</h3>";
// try {
//     $dataInicio = new \DateTime('2026-08-01');
//     $dataFim = new \DateTime('2026-08-31');
//     $totalChamados = $service->contarChamadosPorPeriodo($dataInicio, $dataFim);
//     echo "Sucesso! Foram encontrados {$totalChamados} chamados entre " . $dataInicio->format('Y-m-d') . " e " . $dataFim->format('Y-m-d') . ".<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar chamados por período:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 13. TESTE: CONTAR CHAMADOS TOTAIS
// =========================================================================
// echo "<h3>13. Contar Chamados Totais</h3>";
// try {
//     $totalChamados = $service->contarChamados();
//     echo "Sucesso! Total de chamados: {$totalChamados}.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar chamados totais:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 14. TESTE: CONTAR CHAMADOS RESOLVIDOS
// =========================================================================
// echo "<h3>14. Contar Chamados Resolvidos</h3>";
// try {
//     $totalResolvidos = $service->contarChamadosResolvidos();
//     echo "Sucesso! Total de chamados resolvidos: {$totalResolvidos}.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar chamados resolvidos:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 15. TESTE: CONTAR CHAMADOS PENDENTES
// =========================================================================
// echo "<h3>15. Contar Chamados Pendentes</h3>";
// try {
//     $totalPendentes = $service->contarChamadosPendentes();
//     echo "Sucesso! Total de chamados pendentes: {$totalPendentes}.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar chamados pendentes:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 16. TESTE: CONTAR CHAMADOS CANCELADOS POR PERÍODO
// =========================================================================
// echo "<h3>16. Contar Chamados Cancelados por Período</h3>";
// try {
//     $dataInicio = new \DateTime('2026-08-01');
//     $dataFim = new \DateTime('2026-08-31');
//     $totalCancelados = $service->contarChamadosCanceladosPorPeriodo($dataInicio, $dataFim);
//     echo "Sucesso! Foram encontrados {$totalCancelados} chamados cancelados entre " . $dataInicio->format('Y-m-d') . " e " . $dataFim->format('Y-m-d') . ".<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar chamados cancelados por período:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 17. TESTE: BUSCAR TICKETS POR USUÁRIO ID
// =========================================================================
// echo "<h3>17. Buscar Tickets por Usuário ID</h3>";
// try {
//     $idUsuario = 17;
//     $ticketsPorUsuario = $service->buscarTicketsPorUsuario($idUsuario);
//     echo "Sucesso! Foram encontrados " . count($ticketsPorUsuario) . " chamados para o usuário ID {$idUsuario}.<br>";
//     echo "<pre>";
//     print_r($ticketsPorUsuario);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao buscar tickets por usuário:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 18. TESTE: BUSCAR TICKETS POR RESPONSÁVEL ID
// =========================================================================
// echo "<h3>18. Buscar Tickets por Responsável ID</h3>";
// try {
//     $idResponsavel = 14;
//     $ticketsPorResponsavel = $service->buscarTicketsPorResponsavel($idResponsavel);
//     echo "Sucesso! Foram encontrados " . count($ticketsPorResponsavel) . " chamados para o responsável ID {$idResponsavel}.<br>";
//     echo "<pre>";
//     print_r($ticketsPorResponsavel);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao buscar tickets por responsável:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 19. TESTE: BUSCAR TICKETS POR NOME DE USUÁRIO
// =========================================================================
// echo "<h3>19. Buscar Tickets por Nome de Usuário</h3>";
// try {
//     $nomeUsuario = 'Fran';
//     $ticketsPorNome = $service->buscarTicketsPornome($nomeUsuario);
//     echo "Sucesso! Foram encontrados " . count($ticketsPorNome) . " chamados para o usuário com nome '{$nomeUsuario}'.<br>";
//     echo "<pre>";
//     print_r($ticketsPorNome);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao buscar tickets por nome de usuário:</b> " . $e->getMessage() . "<br>";
// }
?>