<?php

namespace src\repositories;

require_once __DIR__ . "/../configs/Database.php";
require_once __DIR__ . "/../models/Ticket.php";

use src\models\Ticket;

use Database; 
use DateTime;
use PDOException;
use RuntimeException;
use PDO;

class TicketRepository{
    public function encontrarTicketPorId(int $id):Ticket {
        try{
            $sql = 'SELECT * FROM "CHAMADO" WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$id]);
            $dados = $stmt->fetch();
            
            $dataAberturaObj = !empty($dados['data_abertura']) ? new DateTime($dados['data_abertura']) : null;
            $dataEncerramentoObj = !empty($dados['data_encerramento']) ? new DateTime($dados['data_encerramento']) : null;
            
            return new Ticket(
                $dados['id'],  
                $dados['uuid'],
                $dados['titulo'],
                $dados['descricao'],
                $dados['prioridade'],
                $dados['patrimonio'],
                $dados['status'],
                $dados['id_categoria'],
                $dados['id_usuario'], 
                $dados['id_responsavel'],
                $dataAberturaObj,
                $dataEncerramentoObj
            );
        }catch(PDOException $e){
            throw new RuntimeException("Erro ao buscar chamado no banco",0 , $e);
        }
    }

    public function criarTicket(Ticket $ticket):void {
        try {
            $sql = 'INSERT INTO "CHAMADO" (titulo, descricao, prioridade, data_abertura, data_encerramento, patrimonio, id_categoria, id_usuario, id_responsavel, status) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = Database::getConnection()->prepare($sql);
            
            $dataAberturaStr = $ticket->getDataAbertura() ? $ticket->getDataAbertura()->format('Y-m-d H:i:s') : null;
            $dataEncerramentoStr = $ticket->getDataEncerramento() ? $ticket->getDataEncerramento()->format('Y-m-d H:i:s') : null;

            $stmt->execute([
                $ticket->getTitulo(),
                $ticket->getDescricao(),
                $ticket->getPrioridade(),
                $dataAberturaStr,        
                $dataEncerramentoStr,     
                $ticket->getPatrimonio(),
                $ticket->getIdCategoria(),
                $ticket->getIdUsuario(),
                $ticket->getIdResponsavel(),
                $ticket->getStatus()
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao criar chamado no banco: " . $e->getMessage(), 0, $e);
        }
    }

    public function atualizarPrioridadeTicket(int $id, string $prioridade):void {
        try {
            $sql = 'UPDATE "CHAMADO" SET prioridade = ? WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$prioridade, $id]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao atualizar prioridade do chamado no banco",0 , $e);
        }
    }

    public function atualizarStatusTicket(int $id, string $status):void {
        try {
            $sql = 'UPDATE "CHAMADO" SET status = ? WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao atualizar status do chamado no banco",0 , $e);
        }
    }
    
    public function encerrarTicket(int $id, string $status):void{
        try {
            $sql = 'UPDATE "CHAMADO" SET status = ?, data_encerramento = NOW() WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao encerrar chamado no banco",0 , $e);
        }
    }

    public function atribuirResponsavelTicket(int $ticketId, int $idResponsavel):void {
        try {
            $sql = 'UPDATE "CHAMADO" SET id_responsavel = ? WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$idResponsavel, $ticketId]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao atribuir responsável ao chamado no banco", 0, $e);
        }
    }
    public function listarTodos(): array {
        try {
            $sql = '
                SELECT
                    c.id,
                    c.uuid,
                    c.titulo,
                    c.patrimonio,
                    c.prioridade,
                    c.descricao,
                    c.data_abertura,
                    c.data_encerramento,
                    c.status,
                    cat.nome  AS categoria,
                    us.nome   AS solicitante,
                    resp.nome AS responsavel
                FROM "CHAMADO" c
                LEFT JOIN "CATEGORIA" cat  ON c.id_categoria   = cat.id
                LEFT JOIN "USUARIO"   us   ON c.id_usuario      = us.id
                LEFT JOIN "USUARIO"   resp ON c.id_responsavel  = resp.id
                ORDER BY c.data_abertura DESC
            ';

            $stmt = Database::getConnection()->query($sql);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException('Erro ao listar chamados', 0, $e);
        }
    }

    public function buscaPorDataAbertura(DateTime $data): ?array {
        try{
            $sql = 'SELECT * FROM "CHAMADO" WHERE data_abertura::DATE = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$data->format('Y-m-d')]);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($dados)) {
                return null;
            }
            $tickets = [];
            foreach ($dados as $linha) {
                $dataAberturaObj = !empty($linha['data_abertura']) ? new DateTime($linha['data_abertura']) : null;
                $dataEncerramentoObj = !empty($linha['data_encerramento']) ? new DateTime($linha['data_encerramento']) : null;
                
                $tickets[] = new Ticket(
                    $linha['id'],
                    $linha['uuid'],
                    $linha['titulo'] ?? null,
                    $linha['descricao'] ?? null,
                    $linha['prioridade'],
                    $linha['patrimonio'] ?? null,
                    $linha['status'],
                    $linha['id_categoria'] ?? null,
                    $linha['id_usuario'] ?? null,
                    $linha['id_responsavel'] ?? null,
                    $dataAberturaObj,
                    $dataEncerramentoObj
                );
            }
            return $tickets;
        }catch(PDOException $e){
            throw new RuntimeException("Erro ao buscar chamado no banco",0 , $e);
        }
    }

    public function buscaPorDataEncerramento(DateTime $data): ?array {
        try{
            $sql = 'SELECT * FROM "CHAMADO" WHERE data_encerramento::DATE = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$data->format('Y-m-d')]);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($dados)) {
                return null;
            }
            $tickets = [];
            foreach ($dados as $linha) {
                $dataAberturaObj = !empty($linha['data_abertura']) ? new DateTime($linha['data_abertura']) : null;
                $dataEncerramentoObj = !empty($linha['data_encerramento']) ? new DateTime($linha['data_encerramento']) : null;
                
                $tickets[] = new Ticket(
                    $linha['id'],
                    $linha['uuid'],
                    $linha['titulo'] ?? null,
                    $linha['descricao'] ?? null,
                    $linha['prioridade'],
                    $linha['patrimonio'] ?? null,
                    $linha['status'],
                    $linha['id_categoria'] ?? null,
                    $linha['id_usuario'] ?? null,
                    $linha['id_responsavel'] ?? null,
                    $dataAberturaObj,
                    $dataEncerramentoObj
                );
            }
            return $tickets;
        }catch(PDOException $e){
            throw new RuntimeException("Erro ao buscar chamado no banco",0 , $e);
        }
    }

    public function contarChamados(): int {
        try {
            $sql = 'SELECT COUNT(*) AS total FROM "CHAMADO"';
            $stmt = Database::getConnection()->query($sql);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao contar chamados no banco", 0, $e);
        }
    }

    public function contarChamadosResolvidos(): int {
        try {
            $sql = 'SELECT COUNT(*) AS total FROM "CHAMADO" WHERE status = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute(['concluido']);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao contar chamados resolvidos no banco", 0, $e);
        }
    }

    public function contarChamadosPendentes(): int {
        try {
            $sql = 'SELECT COUNT(*) AS total FROM "CHAMADO" WHERE status = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute(['pendente']);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$resultado['total'];
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao contar chamados pendentes no banco", 0, $e);
        }
    }

    public function calcularTaxaResolucao(int $totalChamados, int $chamadosResolvidos): float {
        if ($totalChamados === 0) {
            return 0.0;
        }
        $taxa = ($chamadosResolvidos / $totalChamados) * 100;
        return round($taxa, 2);
    }

    public function relatorioPorCategoria(): array {
        try {
            $sql = '
                SELECT 
                    cat.nome AS categoria,
                    COUNT(c.id) AS quantidade,
                    ROUND(COUNT(c.id) * 100.0 / SUM(COUNT(c.id)) OVER(), 2) AS porcentagem
                FROM "CHAMADO" c
                INNER JOIN "CATEGORIA" cat ON c.id_categoria = cat.id
                GROUP BY cat.nome
                ORDER BY quantidade DESC
            ';

            $stmt = Database::getConnection()->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new RuntimeException('Erro ao buscar indicadores de categoria', 0, $e);
        }
    }

    public function contarChamadosPorPeriodo(\DateTime $dataInicial, \DateTime $dataFinal): int {
        try {
            $sql = 'SELECT COUNT(*) AS total FROM "CHAMADO" WHERE data_abertura::DATE BETWEEN ? AND ?';
            $stmt = Database::getConnection()->prepare($sql);
            
            $stmt->execute([
                $dataInicial->format('Y-m-d'), 
                $dataFinal->format('Y-m-d')
            ]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($resultado['total'] ?? 0);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao contar chamados por período", 0, $e);
        }
    }

    public function buscarTicketsPorStatus(string $status): ? array {
        try {
            $sql = 'SELECT * FROM "CHAMADO" WHERE status = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$status]);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($dados)) {
                return null;
            }
            $tickets = [];
            foreach ($dados as $linha) {
                $dataAberturaObj = !empty($linha['data_abertura']) ? new DateTime($linha['data_abertura']) : null;
                $dataEncerramentoObj = !empty($linha['data_encerramento']) ? new DateTime($linha['data_encerramento']) : null;
                
                $tickets[] = new Ticket(
                    $linha['id'],
                    $linha['uuid'],
                    $linha['titulo'] ?? null,
                    $linha['descricao'] ?? null,
                    $linha['prioridade'],
                    $linha['patrimonio'] ?? null,
                    $linha['status'],
                    $linha['id_categoria'] ?? null,
                    $linha['id_usuario'] ?? null,
                    $linha['id_responsavel'] ?? null,
                    $dataAberturaObj,
                    $dataEncerramentoObj
                );
            }
            return $tickets;
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao buscar chamados por status no banco", 0, $e);
        }
    }

    public function buscarTicketsPorCategoria(int $idCategoria): ? array {
        try {
            $sql = 'SELECT * FROM "CHAMADO" WHERE id_categoria = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$idCategoria]);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($dados)) {
                return null;
            }
            $tickets = [];
            foreach ($dados as $linha) {
                $dataAberturaObj = !empty($linha['data_abertura']) ? new DateTime($linha['data_abertura']) : null;
                $dataEncerramentoObj = !empty($linha['data_encerramento']) ? new DateTime($linha['data_encerramento']) : null;
                
                $tickets[] = new Ticket(
                    $linha['id'],
                    $linha['uuid'],
                    $linha['titulo'] ?? null,
                    $linha['descricao'] ?? null,
                    $linha['prioridade'],
                    $linha['patrimonio'] ?? null,
                    $linha['status'],
                    $linha['id_categoria'] ?? null,
                    $linha['id_usuario'] ?? null,
                    $linha['id_responsavel'] ?? null,
                    $dataAberturaObj,
                    $dataEncerramentoObj
                );
            }
            return $tickets;
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao buscar chamados por categoria no banco", 0, $e);
        }
    }

    public function contarChamadosResolvidosPorPeriodo(\DateTime $dataInicial, \DateTime $dataFinal): int {
        try {
            $sql = 'SELECT COUNT(*) AS total FROM "CHAMADO" WHERE status = ? AND data_encerramento::DATE BETWEEN ? AND ?';
            $stmt = Database::getConnection()->prepare($sql);
            
            $stmt->execute([
                'concluido', 
                $dataInicial->format('Y-m-d'),
                $dataFinal->format('Y-m-d') 
            ]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($resultado['total'] ?? 0);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao contar chamados resolvidos por período", 0, $e);
        }
    }

    public function contarChamadosCancelados(\DateTime $dataInicial, \DateTime $dataFinal): int {
        try {
            $sql = 'SELECT COUNT(*) AS total FROM "CHAMADO" WHERE status = ? AND data_encerramento::DATE BETWEEN ? AND ?';
            $stmt = Database::getConnection()->prepare($sql);
            
            $stmt->execute([
                'cancelado', 
                $dataInicial->format('Y-m-d'),
                $dataFinal->format('Y-m-d') 
            ]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($resultado['total'] ?? 0);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao contar chamados cancelados por período", 0, $e);
        }
    }

    public function contarChamadosCanceladosPorPeriodo(\DateTime $dataInicial, \DateTime $dataFinal): int {
        try {
            $sql = 'SELECT COUNT(*) AS total FROM "CHAMADO" WHERE status = ? AND data_encerramento::DATE BETWEEN ? AND ?';
            $stmt = Database::getConnection()->prepare($sql);
            
            $stmt->execute([
                'cancelado', 
                $dataInicial->format('Y-m-d'),
                $dataFinal->format('Y-m-d') 
            ]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($resultado['total'] ?? 0);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao contar chamados cancelados por período", 0, $e);
        }
    }

    public function contarChamadosPendentesPorPeriodo(\DateTime $dataInicial, \DateTime $dataFinal): int {
        try {
            $sql = 'SELECT COUNT(*) AS total FROM "CHAMADO" WHERE status = ? AND data_abertura::DATE BETWEEN ? AND ?';
            $stmt = Database::getConnection()->prepare($sql);
            
            $stmt->execute([
                'pendente', 
                $dataInicial->format('Y-m-d'),
                $dataFinal->format('Y-m-d') 
            ]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($resultado['total'] ?? 0);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao contar chamados pendentes por período", 0, $e);
        }
    }

    public function relatorioPorCategoriaPorPeriodo(\DateTime $dataInicial, \DateTime $dataFinal): array {
        try {
            $sql = '
                SELECT 
                    cat.nome AS categoria,
                    COUNT(c.id) AS quantidade,
                    ROUND(COUNT(c.id) * 100.0 / SUM(COUNT(c.id)) OVER(), 2) AS porcentagem
                FROM "CHAMADO" c
                INNER JOIN "CATEGORIA" cat ON c.id_categoria = cat.id
                WHERE c.data_abertura::DATE BETWEEN ? AND ?
                GROUP BY cat.nome
                ORDER BY quantidade DESC
            ';

            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([
                $dataInicial->format('Y-m-d'),
                $dataFinal->format('Y-m-d')
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            throw new RuntimeException('Erro ao buscar indicadores de categoria por período', 0, $e);
        }
    }

    public function buscarTicketPorUserId(int $userId): ?array {
        try {
            $sql = 'SELECT * FROM "CHAMADO" WHERE id_usuario = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$userId]);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($dados)) {
                return null;
            }
            $tickets = [];
            foreach ($dados as $linha) {
                $dataAberturaObj = !empty($linha['data_abertura']) ? new DateTime($linha['data_abertura']) : null;
                $dataEncerramentoObj = !empty($linha['data_encerramento']) ? new DateTime($linha['data_encerramento']) : null;
                
                $tickets[] = new Ticket(
                    $linha['id'],
                    $linha['uuid'],
                    $linha['titulo'] ?? null,
                    $linha['descricao'] ?? null,
                    $linha['prioridade'],
                    $linha['patrimonio'] ?? null,
                    $linha['status'],
                    $linha['id_categoria'] ?? null,
                    $linha['id_usuario'] ?? null,
                    $linha['id_responsavel'] ?? null,
                    $dataAberturaObj,
                    $dataEncerramentoObj
                );
            }
            return $tickets;
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao buscar chamados por usuário no banco", 0, $e);
        }
    }

    public function buscarTicketNaoResolvido(): ?array {
        try {
            $sql = 'SELECT * FROM "CHAMADO" WHERE status = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute(['não resolvido']);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($dados)) {
                return [];
            }
            $tickets = [];
            foreach ($dados as $linha) {
                $dataAberturaObj = !empty($linha['data_abertura']) ? new DateTime($linha['data_abertura']) : null;
                $dataEncerramentoObj = !empty($linha['data_encerramento']) ? new DateTime($linha['data_encerramento']) : null;
                
                $tickets[] = new Ticket(
                    $linha['id'],
                    $linha['uuid'],
                    $linha['titulo'] ?? null,
                    $linha['descricao'] ?? null,
                    $linha['prioridade'],
                    $linha['patrimonio'] ?? null,
                    $linha['status'],
                    $linha['id_categoria'] ?? null,
                    $linha['id_usuario'] ?? null,
                    $linha['id_responsavel'] ?? null,
                    $dataAberturaObj,
                    $dataEncerramentoObj
                );
            }
            return $tickets;
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao buscar chamados não resolvidos no banco", 0, $e);
        }
    }

    public function buscarTicketPorResponsavelId(int $responsavelId): ?array {
        try {
            $sql = 'SELECT * FROM "CHAMADO" WHERE id_responsavel = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$responsavelId]);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($dados)) {
                return [];
            }
            $tickets = [];
            foreach ($dados as $linha) {
                $dataAberturaObj = !empty($linha['data_abertura']) ? new DateTime($linha['data_abertura']) : null;
                $dataEncerramentoObj = !empty($linha['data_encerramento']) ? new DateTime($linha['data_encerramento']) : null;
                
                $tickets[] = new Ticket(
                    $linha['id'],
                    $linha['uuid'],
                    $linha['titulo'] ?? null,
                    $linha['descricao'] ?? null,
                    $linha['prioridade'],
                    $linha['patrimonio'] ?? null,
                    $linha['status'],
                    $linha['id_categoria'] ?? null,
                    $linha['id_usuario'] ?? null,
                    $linha['id_responsavel'] ?? null,
                    $dataAberturaObj,
                    $dataEncerramentoObj
                );
            }
            return $tickets;
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao buscar chamados por responsável no banco", 0, $e);
        }
    }

    public function buscarChamadosNomeUser(string $nomeUsuario): ?array {
        try {
            $sql = 'SELECT * FROM "CHAMADO" c INNER JOIN "USUARIO" u ON c.id_usuario = u.id WHERE u.nome ILIKE ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute(['%' . $nomeUsuario . '%']);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($dados)) {
                return [];
            }
            $tickets = [];
            foreach ($dados as $linha) {
                $dataAberturaObj = !empty($linha['data_abertura']) ? new DateTime($linha['data_abertura']) : null;
                $dataEncerramentoObj = !empty($linha['data_encerramento']) ? new DateTime($linha['data_encerramento']) : null;
                
                $tickets[] = new Ticket(
                    $linha['id'],
                    $linha['uuid'],
                    $linha['titulo'] ?? null,
                    $linha['descricao'] ?? null,
                    $linha['prioridade'],
                    $linha['patrimonio'] ?? null,
                    $linha['status'],
                    $linha['id_categoria'] ?? null,
                    $linha['id_usuario'] ?? null,
                    $linha['id_responsavel'] ?? null,
                    $dataAberturaObj,
                    $dataEncerramentoObj
                );
            }
            return $tickets;
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao buscar chamados por nome de usuário no banco", 0, $e);
        }
    }

    public function buscarChamadosNomeChamado(string $nomeChamado): ?array {   
        try {
            $sql = 'SELECT * FROM "CHAMADO" WHERE titulo ILIKE ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute(['%' . $nomeChamado . '%']);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($dados)) {
                return [];
            }
            $tickets = [];
            foreach ($dados as $linha) {
                $dataAberturaObj = !empty($linha['data_abertura']) ? new DateTime($linha['data_abertura']) : null;
                $dataEncerramentoObj = !empty($linha['data_encerramento']) ? new DateTime($linha['data_encerramento']) : null;
                
                $tickets[] = new Ticket(
                    $linha['id'],
                    $linha['uuid'],
                    $linha['titulo'] ?? null,
                    $linha['descricao'] ?? null,
                    $linha['prioridade'],
                    $linha['patrimonio'] ?? null,
                    $linha['status'],
                    $linha['id_categoria'] ?? null,
                    $linha['id_usuario'] ?? null,
                    $linha['id_responsavel'] ?? null,
                    $dataAberturaObj,
                    $dataEncerramentoObj
                );
            }
            return $tickets;
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao buscar chamados por nome de chamado no banco", 0, $e);
        }
    }
}

// =========================================================================
// BLOCO DE TESTES
// =========================================================================

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// echo "<h1>Testes do TicketRepository</h1>";

// // AQUI ESTAVA A CAGADA: Faltava instanciar a própria classe do repositório!
// $repository = new \src\repositories\TicketRepository();

// =========================================================================
// 1. TESTE: BUSCAR CHAMADOS NÃO RESOLVIDOS
// =========================================================================
// echo "<h3>1. Buscar Chamados Não Resolvidos</h3>";
// try {
//     $ticketsNaoResolvidos = $repository->buscarTicketNaoResolvido();
//     echo "Sucesso! Foram encontrados " . (is_array($ticketsNaoResolvidos) ? count($ticketsNaoResolvidos) : 0) . " chamados não resolvidos.<br>";
//     echo "<pre>";
//     print_r($ticketsNaoResolvidos);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao buscar chamados não resolvidos:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 2. TESTE: BUSCA CHAMADOS POR NOME DO CHAMADO (TÍTULO)
// =========================================================================
// echo "<h3>2. Busca Chamados por Nome do Chamado</h3>";
// try {
//     $nomeChamado = "Computador"; // Substitua pelo texto que deseja buscar
//     $ticketsNomeChamado = $repository->buscarChamadosNomeChamado($nomeChamado);
//     echo "Sucesso! Foram encontrados " . (is_array($ticketsNomeChamado) ? count($ticketsNomeChamado) : 0) . " chamados com o termo '{$nomeChamado}'.<br>";
//     echo "<pre>";
//     print_r($ticketsNomeChamado);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro na busca por nome de chamado:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 3. TESTE: BUSCAR CHAMADOS POR NOME DE USUÁRIO
// =========================================================================
// echo "<h3>3. Buscar Chamados por Nome de Usuário</h3>";
// try {
//     $nomeUser = "r"; // Substitua pelo nome desejado
//     $ticketsNomeUser = $repository->buscarChamadosNomeUser($nomeUser);
//     echo "Sucesso! Foram encontrados " . (is_array($ticketsNomeUser) ? count($ticketsNomeUser) : 0) . " chamados criados por usuários contendo '{$nomeUser}'.<br>";
//     echo "<pre>";
//     print_r($ticketsNomeUser);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro na busca por nome de usuário:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 4. TESTE: BUSCAR CHAMADOS POR RESPONSÁVEL ID
// =========================================================================
// echo "<h3>4. Buscar Chamados por Responsável ID</h3>";
// try {
//     $idResponsavel = 2; // ID do técnico
//     $ticketsResponsavel = $repository->buscarTicketPorResponsavelId($idResponsavel);
//     echo "Sucesso! Foram encontrados " . (is_array($ticketsResponsavel) ? count($ticketsResponsavel) : 0) . " chamados atribuídos ao técnico ID {$idResponsavel}.<br>";
//     echo "<pre>";
//     print_r($ticketsResponsavel);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro na busca por responsável:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 5. TESTE: BUSCAR CHAMADOS POR USER ID (CRIADOR)
// =========================================================================
// echo "<h3>5. Buscar Chamados por User ID</h3>";
// try {
//     $idUsuario = 1; // ID do usuário que abriu o chamado
//     $ticketsUsuario = $repository->buscarTicketPorUserId($idUsuario);
//     echo "Sucesso! Foram encontrados " . (is_array($ticketsUsuario) ? count($ticketsUsuario) : 0) . " chamados criados pelo usuário ID {$idUsuario}.<br>";
//     echo "<pre>";
//     print_r($ticketsUsuario);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro na busca por ID do usuário:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 6. TESTE: CONTAR CHAMADOS PENDENTES POR PERÍODO
// =========================================================================
// echo "<h3>6. Contar Chamados por Período</h3>";
// try {
//     $dataInicial = new \DateTime('2026-07-01');
//     $dataFinal = new \DateTime('2026-09-30');
//     $quantidadePeriodo = $repository->contarChamadosPorPeriodo($dataInicial, $dataFinal);
//     echo "Sucesso! Total de chamados abertos entre " . $dataInicial->format('Y-m-d') . " e " . $dataFinal->format('Y-m-d') . ": <b>{$quantidadePeriodo}</b>.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar chamados por período:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 7. TESTE: CONTAR CHAMADOS RESOLVIDOS
// =========================================================================
// echo "<h3>7. Contar Chamados Resolvidos</h3>";
// try {
//     $quantidadeResolvidos = $repository->contarChamadosResolvidos();
//     echo "Sucesso! Total de chamados resolvidos no banco: <b>{$quantidadeResolvidos}</b>.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar chamados resolvidos:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 8. TESTE: BUSCAR CHAMADOS POR STATUS
// =========================================================================
// echo "<h3>8. Buscar Chamados por Status</h3>";
// try {
//     $statusBusca = "pendente";
//     $ticketsStatus = $repository->buscarTicketsPorStatus($statusBusca);
//     echo "Sucesso! Foram encontrados " . (is_array($ticketsStatus) ? count($ticketsStatus) : 0) . " chamados com o status '{$statusBusca}'.<br>";
//     echo "<pre>";
//     print_r($ticketsStatus);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro na busca por status:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 9. TESTE: ATUALIZAR O STATUS DO CHAMADO
// =========================================================================
// echo "<h3>9. Atualizar o Status do Chamado</h3>";
// try {
//     $idChamado = 317; // Coloque um ID válido
//     $novoStatus = "pendente"; // pendente, concluido, cancelado
    
//     // Na repository o método retorna void (não retorna o objeto de volta)
//     $repository->atualizarStatusTicket($idChamado, $novoStatus);
//     echo "Sucesso! Status do chamado {$idChamado} atualizado para '{$novoStatus}' direto no banco.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao atualizar status:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 10. TESTE: BUSCA DE UM CHAMADO POR DATA DE ENCERRAMENTO
// =========================================================================
// echo "<h3>10. Busca de um Chamado por Data de Encerramento</h3>";
// try {
//     $dataEncerramento = new \DateTime('2026-08-06');
//     $ticketsDataEncerramento = $repository->buscaPorDataEncerramento($dataEncerramento);
//     echo "Sucesso! Foram encontrados " . (is_array($ticketsDataEncerramento) ? count($ticketsDataEncerramento) : 0) . " chamados encerrados na data " . $dataEncerramento->format('Y-m-d') . ".<br>";
//     echo "<pre>";
//     print_r($ticketsDataEncerramento);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro na busca por data de encerramento:</b> " . $e->getMessage() . "<br>";
// }

// =========================================================================
// 11. TESTE: ENCONTRAR TICKET POR ID
// =========================================================================
// echo "<h3>11. Encontrar Ticket por ID</h3>";
// try {
//     $idBuscar = 1; // Substitua por um ID que exista
//     $ticket = $repository->encontrarTicketPorId($idBuscar);
//     echo "Sucesso! Ticket ID {$idBuscar} encontrado:<br><pre>";
//     print_r($ticket);
//     echo "</pre>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao encontrar ticket:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 12. TESTE: CRIAR TICKET
// // =========================================================================
// echo "<h3>12. Criar Ticket</h3>";
// try {
//     $novoTicket = new \src\models\Ticket(
//         null, null, "Erro na catraca", "Catraca travada", "alta", "PAT-123", "pendente", 1, 1, null, new \DateTime(), null
//     );
//     $repository->criarTicket($novoTicket);
//     echo "Sucesso! Novo ticket inserido no banco.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao criar ticket:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 13. TESTE: ATUALIZAR PRIORIDADE DO TICKET
// // =========================================================================
// echo "<h3>13. Atualizar Prioridade</h3>";
// try {
//     $idAtualizar = 1; 
//     $repository->atualizarPrioridadeTicket($idAtualizar, 'baixa');
//     echo "Sucesso! Prioridade do chamado {$idAtualizar} alterada para 'baixa'.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao atualizar prioridade:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 14. TESTE: ENCERRAR TICKET
// // =========================================================================
// echo "<h3>14. Encerrar Ticket</h3>";
// try {
//     $idEncerrar = 1; 
//     $repository->encerrarTicket($idEncerrar, 'concluido');
//     echo "Sucesso! Chamado {$idEncerrar} foi encerrado com a data de hoje.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao encerrar ticket:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 15. TESTE: ATRIBUIR RESPONSÁVEL AO TICKET
// // =========================================================================
// echo "<h3>15. Atribuir Responsável</h3>";
// try {
//     $idChamadoResp = 1;
//     $idTecnico = 2;
//     $repository->atribuirResponsavelTicket($idChamadoResp, $idTecnico);
//     echo "Sucesso! Técnico {$idTecnico} atribuído ao chamado {$idChamadoResp}.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao atribuir responsável:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 16. TESTE: LISTAR TODOS OS CHAMADOS
// // =========================================================================
// echo "<h3>16. Listar Todos</h3>";
// try {
//     $todos = $repository->listarTodos();
//     echo "Sucesso! Foram encontrados " . count($todos) . " chamados no total (com JOINs).<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao listar todos:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 17. TESTE: BUSCAR POR DATA DE ABERTURA
// // =========================================================================
// echo "<h3>17. Buscar por Data de Abertura</h3>";
// try {
//     $dataAbertura = new \DateTime(); // Hoje
//     $ticketsAbertura = $repository->buscaPorDataAbertura($dataAbertura);
//     echo "Sucesso! Foram encontrados " . (is_array($ticketsAbertura) ? count($ticketsAbertura) : 0) . " chamados abertos em " . $dataAbertura->format('Y-m-d') . ".<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro na busca por data de abertura:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 18. TESTE: CONTAR CHAMADOS TOTAIS
// // =========================================================================
// echo "<h3>18. Contar Chamados Totais</h3>";
// try {
//     $total = $repository->contarChamados();
//     echo "Sucesso! Total de registros na tabela: <b>{$total}</b>.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar totais:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 19. TESTE: CONTAR CHAMADOS PENDENTES (GERAL)
// // =========================================================================
// echo "<h3>19. Contar Chamados Pendentes</h3>";
// try {
//     $totalPendentes = $repository->contarChamadosPendentes();
//     echo "Sucesso! Total de chamados pendentes gerais: <b>{$totalPendentes}</b>.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar pendentes:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 20. TESTE: CALCULAR TAXA DE RESOLUÇÃO
// // =========================================================================
// echo "<h3>20. Calcular Taxa de Resolução</h3>";
// try {
//     $totalGeral = $repository->contarChamados();
//     $totalResolvidos = $repository->contarChamadosResolvidos();
//     $taxa = $repository->calcularTaxaResolucao($totalGeral, $totalResolvidos);
//     echo "Sucesso! A taxa atual de resolução é de <b>{$taxa}%</b>.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao calcular taxa:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 21. TESTE: RELATÓRIO POR CATEGORIA (GERAL)
// // =========================================================================
// echo "<h3>21. Relatório por Categoria</h3>";
// try {
//     $relatorioCat = $repository->relatorioPorCategoria();
//     echo "Sucesso! Relatório gerado com " . count($relatorioCat) . " categorias.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro no relatório de categorias:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 22. TESTE: BUSCAR TICKETS POR CATEGORIA (ID)
// // =========================================================================
// echo "<h3>22. Buscar Tickets por Categoria ID</h3>";
// try {
//     $idCat = 1;
//     $ticketsCat = $repository->buscarTicketsPorCategoria($idCat);
//     echo "Sucesso! Encontrados " . (is_array($ticketsCat) ? count($ticketsCat) : 0) . " chamados da categoria {$idCat}.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao buscar por categoria:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 23. TESTE: CONTAR RESOLVIDOS POR PERÍODO
// // =========================================================================
// echo "<h3>23. Contar Resolvidos por Período</h3>";
// try {
//     $qtdResolvidos = $repository->contarChamadosResolvidosPorPeriodo(new \DateTime('2020-01-01'), new \DateTime('2030-01-01'));
//     echo "Sucesso! Resolvidos no período: <b>{$qtdResolvidos}</b>.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar resolvidos período:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 24. TESTE: CONTAR CANCELADOS (FUNÇÃO DUPLICADA NO REPOSITÓRIO 1)
// // =========================================================================
// echo "<h3>24. Contar Cancelados</h3>";
// try {
//     $qtdCancelados1 = $repository->contarChamadosCancelados(new \DateTime('2020-01-01'), new \DateTime('2030-01-01'));
//     echo "Sucesso! Cancelados no período (Módulo 1): <b>{$qtdCancelados1}</b>.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar cancelados:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 25. TESTE: CONTAR CANCELADOS POR PERÍODO (FUNÇÃO DUPLICADA NO REPOSITÓRIO 2)
// // =========================================================================
// echo "<h3>25. Contar Cancelados por Período</h3>";
// try {
//     $qtdCancelados2 = $repository->contarChamadosCanceladosPorPeriodo(new \DateTime('2020-01-01'), new \DateTime('2030-01-01'));
//     echo "Sucesso! Cancelados no período (Módulo 2): <b>{$qtdCancelados2}</b>.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar cancelados período:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 26. TESTE: CONTAR PENDENTES POR PERÍODO
// // =========================================================================
// echo "<h3>26. Contar Pendentes por Período</h3>";
// try {
//     $qtdPendentesPeriodo = $repository->contarChamadosPendentesPorPeriodo(new \DateTime('2020-01-01'), new \DateTime('2030-01-01'));
//     echo "Sucesso! Pendentes no período: <b>{$qtdPendentesPeriodo}</b>.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro ao contar pendentes período:</b> " . $e->getMessage() . "<br>";
// }

// // =========================================================================
// // 27. TESTE: RELATÓRIO CATEGORIA POR PERÍODO
// // =========================================================================
// echo "<h3>27. Relatório Categoria por Período</h3>";
// try {
//     $relatorioCatPeriodo = $repository->relatorioPorCategoriaPorPeriodo(new \DateTime('2020-01-01'), new \DateTime('2030-01-01'));
//     echo "Sucesso! Relatório de categorias do período gerado com " . count($relatorioCatPeriodo) . " resultados.<br>";
// } catch (\Exception $e) {
//     echo "<b>Erro no relatório categoria/período:</b> " . $e->getMessage() . "<br>";
// }
?>