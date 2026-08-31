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
        if (isset($dados['status']) && !in_array($dados['status'], ['pendente', 'concluido', 'cancelado', 'não resolvido'], true)) {
            throw new InvalidArgumentException("Status invalido.");
        }
    }

    // =========================================================================
    // CRUD BÁSICO E ATUALIZAÇÕES
    // =========================================================================

    public function listarTicket(): void {
        try {
            require_once __DIR__ . '/../../Http/Support/ChamadoActions.php'; 
            
            $actions = new \ChamadoActions();
            $chamados = $actions->listarComTecnicoId();
            
            $this->response([
                "success" => true,
                "data" => $chamados
            ]);

        } catch (\Throwable $e) {
            $this->response([
                "success" => false,
                "message" => "Erro no servidor: " . $e->getMessage()
            ], 400);
    }
    }

    public function exibir(int $id): void {
        try {
            $ticket = $this->services->exibirTicket($id);
            $this->response(["success" => true, "data" => $ticket->getAll()]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
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
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }
    
    public function atualizarPrioridade(int $id, array $dadosRequisicao): void {
        try {
            if (empty($dadosRequisicao['prioridade'])) {
                throw new InvalidArgumentException("A prioridade é obrigatória.");
            }
            $this->services->atualizarPrioridade($id, $dadosRequisicao);
            $this->response(["success" => true, "message" => "Prioridade atualizada com sucesso."]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    public function atualizarStatus(int $id, array $dadosRequisicao): void {
        try {
            if (empty($dadosRequisicao['status'])) {
                throw new InvalidArgumentException("O status é obrigatório.");
            }
            $this->services->atualizarStatus($id, $dadosRequisicao);
            $this->response(["success" => true, "message" => "Status atualizado com sucesso."]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    public function encerrar(int $id): void {
        try {
            $this->services->encerrarTicket($id);
            $this->response(["success" => true, "message" => "Ticket Encerrado com sucesso."]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => 'Não foi possivel encerrar: ' . $e->getMessage()], 400);
        }
    }

    // =========================================================================
    // FILTROS DE BUSCA
    // =========================================================================

    public function buscarPorStatus(string $status): void {
        try {
            $dados = $this->services->buscaTicketsStatus($status);
            $this->response(["success" => true, "data" => $dados]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    public function buscarPorDataAbertura(string $data): void {
        try {
            $dataObj = new \DateTime($data);
            $dados = $this->services->buscaTicketsPorDataAbertura($dataObj);
            $this->response(["success" => true, "data" => $dados]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => "Formato de data inválido. Use YYYY-MM-DD."], 400);
        }
    }

    public function buscarPorDataEncerramento(string $data): void {
        try {
            $dataObj = new \DateTime($data);
            $dados = $this->services->buscaTicketsPorDataEncerramento($dataObj);
            $this->response(["success" => true, "data" => $dados]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => "Formato de data inválido. Use YYYY-MM-DD."], 400);
        }
    }

    public function buscarPorUsuario(int $idUsuario): void {
        try {
            $dados = $this->services->buscarTicketsPorUsuario($idUsuario);
            $this->response(["success" => true, "data" => $dados]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    public function buscarPorNomeUsuario(string $nome): void {
        try {
            $dados = $this->services->buscarTicketsPornome($nome);
            $this->response(["success" => true, "data" => $dados]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    public function buscarPorNomeChamado(string $nomeChamado): void {
        try {
            $dados = $this->services->buscarTicketsPornomeChamado($nomeChamado);
            $this->response(["success" => true, "data" => $dados]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    public function buscarPorCategoria(int $idCategoria): void {
        try {
            $dados = $this->services->buscarTicketsPorCategoria($idCategoria);
            $this->response(["success" => true, "data" => $dados]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    public function buscarNaoResolvidos(): void {
        try {
            $dados = $this->services->buscarTicketNaoResolvido();
            $this->response(["success" => true, "data" => $dados]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    public function buscarPorResponsavel(int $idResponsavel): void {
        try {
            $dados = $this->services->buscarTicketsPorResponsavel($idResponsavel);
            $this->response(["success" => true, "data" => $dados]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    // =========================================================================
    // MÉTRICAS E RELATÓRIOS (DASHBOARD)
    // =========================================================================

    public function metricasGerais(): void {
        try {
            $total = $this->services->contarChamados();
            $resolvidos = $this->services->contarChamadosResolvidos();
            $pendentes = $this->services->contarChamadosPendentes();
            $taxa = $this->services->calcularTaxaResolucao($total, $resolvidos);

            $this->response([
                "success" => true,
                "data" => [
                    "total_chamados" => $total,
                    "resolvidos" => $resolvidos,
                    "pendentes" => $pendentes,
                    "taxa_resolucao_porcentagem" => $taxa
                ]
            ]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    public function metricasPorPeriodo(): void {
        try {
            $dados = $this->getBody();
            $inicio = new \DateTime($dados['data_inicio']);
            $fim = new \DateTime($dados['data_fim']);

            $resolvidos = $this->services->contarChamadosResolvidosPorPeriodo($inicio, $fim);
            $pendentes = $this->services->contarChamadosPendentesPorPeriodo($inicio, $fim);
            $cancelados = $this->services->contarChamadosCanceladosPorPeriodo($inicio, $fim);

            $this->response([
                "success" => true,
                "data" => [
                    "resolvidos" => $resolvidos,
                    "pendentes" => $pendentes,
                    "cancelados" => $cancelados
                ]
            ]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => "Formato de data inválido."], 400);
        }
    }

    public function relatorioCategoria(): void {
        try {
            $dados = $this->services->relatorioPorCategoria();
            $this->response(["success" => true, "data" => $dados]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    public function relatorioCategoriaPorPeriodo(): void {
        try {
            $dados = $this->getBody();
            $inicio = new \DateTime($dados['data_inicio']);
            $fim = new \DateTime($dados['data_fim']);
            $data = $this->services->relatorioPorCategoriaPorPeriodo($inicio, $fim);
            $this->response(["success" => true, "data" => $data]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => "Formato de data inválido."], 400);
        }
    }

    public function atribuirTecnico(int $idChamado, array $dadosRequisicao): void {
        try {
            if (empty($dadosRequisicao['id_responsavel'])) {
                throw new InvalidArgumentException("O ID do responsável (técnico) é obrigatório.");
            }
            $this->services->atribuirTecnico($idChamado, $dadosRequisicao['id_responsavel']);
            $this->response(["success" => true, "message" => "Técnico atribuído com sucesso."]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => $e->getMessage()], 400);
        }
    }

    public function buscarChamadosAbertosPeriodo(string $dataInicio, string $dataFim): void {
        try {
            $inicio = new \DateTime($dataInicio);
            $fim = new \DateTime($dataFim);
            $dados = $this->services->chamadosAbertosPorPeriodo($inicio, $fim);
            
            $this->response(["success" => true, "data" => $dados]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => "Formato de data inválido ou erro: " . $e->getMessage()], 400);
        }
    }

    public function taxaResolucaoPeriodo(string $dataInicio, string $dataFim): void {
        try {
            $inicio = new \DateTime($dataInicio);
            $fim = new \DateTime($dataFim);
            $taxa = $this->services->calcularTaxaResolucaoPeriodo($inicio, $fim);
            
            $this->response([
                "success" => true, 
                "data" => [$taxa]
            ]);
        } catch (\Throwable $e) {
            $this->response(["success" => false, "message" => "Formato de data inválido ou erro: " . $e->getMessage()], 400);
        }
    }

    public function dashboardPorPeriodo(): void {
        try {
            $dados = $this->getBody();
            $inicio = new \DateTime($dados['data_inicio']);
            $fim = new \DateTime($dados['data_fim']);
            $data = $this->services->relatorioDashboardPorPeriodo($inicio, $fim);
            $this->response([
                "success" => true, 
                "data" => $data
            ]);
        } catch (\Throwable $e) {
            $this->response([
                "success" => false, 
                "message" => "Erro ao gerar dashboard: " . $e->getMessage()
            ], 400);
        }
    }

//    public function atribuirTecnico(int $chamadoId, int $tecnicoId): void
// {
//     try {

//         $this->services->atribuirTecnico(
//             $chamadoId,
//             $tecnicoId
//         );

//         $this->response([
//             'success' => true,
//             'message' => 'Técnico atribuído ao chamado com sucesso.',
//             'data' => [
//                 'id_chamado' => $chamadoId,
//                 'tecnico_id' => $tecnicoId
//             ]
//         ]);

//     } catch (\Throwable $e) {

//         $this->response([
//             'success' => false,
//             'message' => $e->getMessage()
//         ], 400);
//     }
// }
}

/*
=========================================================================
TESTES DO CONTROLLER (Descomente apenas UM por vez)
=========================================================================
*/

ini_set('display_errors', 1);
error_reporting(E_ALL);

$controller = new TicketController();

// =========================================================================
// 1. TESTE: BUSCAR TICKETS POR STATUS
// =========================================================================
// echo "<h3>1. Teste: Buscar Tickets por Status ('pendente')</h3>";
// try {
//     $controller->buscarPorStatus('pendente');
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 2. TESTE: BUSCAR TICKETS POR DATA DE ABERTURA
// =========================================================================
// echo "<h3>2. Teste: Buscar Tickets por Data de Abertura</h3>";
// try {
//     $controller->buscarPorDataAbertura('2026-08-08'); 
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 3. TESTE: BUSCAR TICKETS POR DATA DE ENCERRAMENTO
// =========================================================================
// echo "<h3>3. Teste: Buscar Tickets por Data de Encerramento</h3>";
// try {
//     $controller->buscarPorDataEncerramento('2026-08-11'); 
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 4. TESTE: ATUALIZAR STATUS DO TICKET
// =========================================================================
// echo "<h3>4. Teste: Atualizar Status do Ticket</h3>";
// try {
//     $idTicket = 2; 
//     $novoStatusTeste = ['status' => 'pendente']; 
//     $controller->atualizarStatus($idTicket, $novoStatusTeste);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 5. TESTE: EXIBIR TICKET ESPECÍFICO
// =========================================================================
// echo "<h3>5. Teste: Exibir Ticket Específico</h3>";
// try {
//     $idTicket = 1;
//     $controller->exibir($idTicket);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 6. TESTE: ATUALIZAR PRIORIDADE
// =========================================================================
// echo "<h3>6. Teste: Atualizar Prioridade</h3>";
// try {
//     $idTicket = 4;
//     $dadosPrioridade = ['prioridade' => 'alta'];
//     $controller->atualizarPrioridade($idTicket, $dadosPrioridade);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 7. TESTE: ENCERRAR TICKET
// =========================================================================
// echo "<h3>7. Teste: Encerrar Ticket</h3>";
// try {
//     $idTicket = 2;
//     $controller->encerrar($idTicket);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 8. TESTE: BUSCAR TICKETS POR USUÁRIO ID
// =========================================================================
// echo "<h3>8. Teste: Buscar Tickets por Usuário (ID)</h3>";
// try {
//     $idUsuario = 1;
//     $controller->buscarPorUsuario($idUsuario);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 9. TESTE: BUSCAR TICKETS POR NOME DO USUÁRIO
// =========================================================================
// echo "<h3>9. Teste: Buscar Tickets por Nome de Usuário</h3>";
// try {
//     $nomeUsuario = 'Fran';
//     $controller->buscarPorNomeUsuario($nomeUsuario);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 10. TESTE: BUSCAR TICKETS POR NOME DO CHAMADO (TÍTULO)
// =========================================================================
// echo "<h3>10. Teste: Buscar Tickets por Nome do Chamado</h3>";
// try {
//     $titulo = 'computador';
//     $controller->buscarPorNomeChamado($titulo);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 11. TESTE: BUSCAR TICKETS POR CATEGORIA (ID)
// =========================================================================
// echo "<h3>11. Teste: Buscar Tickets por Categoria</h3>";
// try {
//     $idCategoria = 1;
//     $controller->buscarPorCategoria($idCategoria);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 12. TESTE: BUSCAR TICKETS NÃO RESOLVIDOS
// =========================================================================
// echo "<h3>12. Teste: Buscar Tickets Não Resolvidos</h3>";
// try {
//     $controller->buscarNaoResolvidos();
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 13. TESTE: BUSCAR TICKETS POR RESPONSÁVEL (TÉCNICO)
// =========================================================================
// echo "<h3>13. Teste: Buscar Tickets por Responsável</h3>";
// try {
//     $idTecnico = 2;
//     $controller->buscarPorResponsavel($idTecnico);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 14. TESTE: MÉTRICAS GERAIS (DASHBOARD)
// =========================================================================
// echo "<h3>14. Teste: Métricas Gerais (Dashboard)</h3>";
// try {
//     $controller->metricasGerais();
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 15. TESTE: MÉTRICAS POR PERÍODO
// =========================================================================
// echo "<h3>15. Teste: Métricas por Período</h3>";
// try {
//     $dataInicio = '2026-07-01';
//     $dataFim = '2026-09-30';
//     $controller->metricasPorPeriodo($dataInicio, $dataFim);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 16. TESTE: RELATÓRIO POR CATEGORIA (GERAL)
// =========================================================================
// echo "<h3>16. Teste: Relatório por Categoria</h3>";
// try {
//     $controller->relatorioCategoria();
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 17. TESTE: RELATÓRIO POR CATEGORIA POR PERÍODO
// =========================================================================
// echo "<h3>17. Teste: Relatório por Categoria por Período</h3>";
// try {
//     $dataInicio = '2026-07-01';
//     $dataFim = '2026-09-30';
//     $controller->relatorioCategoriaPorPeriodo($dataInicio, $dataFim);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 18. TESTE: LISTAR TODOS OS TICKETS
// =========================================================================
// echo "<h3>18. Teste: Listar Todos</h3>";
// try {
//     $controller->listarTicket();
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 19. TESTE: CRIAR TICKET
// =========================================================================
// echo "<h3>19. Teste: Criar Ticket</h3>";
// try {
//     $dadosNovoTicket = [
//         'titulo' => 'Teclado com defeito',
//         'descricao' => 'Teclas W, A, S, D pararam de funcionar',
//         'prioridade' => 'baixa',
//         'patrimonio' => 'TEC-999',
//         'status' => 'pendente',
//         'id_categoria' => 1,
//         'id_usuario' => 1,
//         'id_responsavel' => null
//     ];
//     $controller->criarTicket($dadosNovoTicket);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 20. TESTE: ATRIBUIR TÉCNICO
// =========================================================================
// echo "<h3>20. Teste: Atribuir Técnico</h3>";
// try {
//     $idChamado = 1;
//     $dadosTecnico = ['id_responsavel' => 2]; 
//     $controller->atribuirTecnico($idChamado, $dadosTecnico);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 21. TESTE: CHAMADOS ABERTOS POR PERÍODO
// =========================================================================
// echo "<h3>21. Teste: Buscar Chamados Abertos por Período</h3>";
// try {
//     $dataInicio = '2026-07-01';
//     $dataFim = '2026-09-30';
//     $controller->buscarChamadosAbertosPeriodo($dataInicio, $dataFim);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 22. TESTE: CALCULAR TAXA DE RESOLUÇÃO POR PERÍODO
// =========================================================================
// echo "<h3>22. Teste: Calcular Taxa de Resolução por Período</h3>";
// try {
//     $dataInicio = '2026-07-01';
//     $dataFim = '2026-09-30';
//     $controller->taxaResolucaoPeriodo($dataInicio, $dataFim);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 28. TESTE: DASHBOARD GERAL POR PERÍODO (AGRUPADO)
// =========================================================================
// echo "<h3>28. Teste: Dashboard Geral por Período</h3>";
// try {
//     $dataInicio = '2026-07-01';
//     $dataFim = '2026-12-31'; 
    
//     $controller->dashboardPorPeriodo($dataInicio, $dataFim);
// } catch (\Exception $e) {
//     echo "<b>Erro:</b> " . $e->getMessage() . "<br>";
// }
?>