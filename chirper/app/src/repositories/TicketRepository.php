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

    public function buscarTicketPorResponsavelId(int $responsavelId): ?array {
        try {
            $sql = 'SELECT * FROM "CHAMADO" WHERE id_responsavel = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$responsavelId]);
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
            throw new RuntimeException("Erro ao buscar chamados por nome de usuário no banco", 0, $e);
        }
    }
}

    // EXEMPLOS DE USO DO REPOSITÓRIO DE CHAMADOS

    // BUSCA DE CHAMADOS POR RESPONSÁVEL ID
    // $repository = new TicketRepository();
    // $tickets = $repository->buscarTicketPorResponsavelId(2);
    // echo "Chamados do responsável com ID 12:\n";
    // if ($tickets) {
    //     foreach ($tickets as $ticket) {
    //         echo "<br>" . "ID: " . $ticket->getId() . ", Título: " . $ticket->getTitulo() . ", Status: " . $ticket->getStatus() . "<br>";
    //     }
    // } else {
    //     echo "Nenhum chamado encontrado para o responsável especificado.\n";
    // }

    // BUSCA DE CHAMADOS POR USER ID
    // $repository = new TicketRepository();
    // $tickets = $repository->buscarTicketPorUserId(1);
    // echo "Chamados do usuário com ID 1:\n";
    // if ($tickets) {
    //     foreach ($tickets as $ticket) {
    //         echo "<br>" . "ID: " . $ticket->getId() . ", Título: " . $ticket->getTitulo() . ", Status: " . $ticket->getStatus() . "<br>";
    //     }
    // } else {
    //     echo "Nenhum chamado encontrado para o usuário especificado.\n";
    // }

    // CONTAR CHAMADOS PENDENTES POR PERÍODO
    // $repository = new TicketRepository();
    // $dataInicial = new DateTime('2026-07-01');
    // $dataFinal = new DateTime('2026-09-30');
    // $indicadores = $repository->relatorioPorCategoriaPorPeriodo($dataInicial, $dataFinal);
    // foreach ($indicadores as $linha) {
    //     echo "Categoria: " . $linha['categoria'] . " | ";
    //     echo "Quantidade: " . $linha['quantidade'] . " | ";
    //     echo "Porcentagem: " . $linha['porcentagem'] . "%<br>";
    // }

    // CONTAR CHAMADOS PENDENTES POR PERÍODO
    // $repository = new TicketRepository();
    // $dataInicial = new DateTime('2026-07-01');
    // $dataFinal = new DateTime('2026-09-30');
    // $quantidadePendentes = $repository->contarChamadosPendentesPorPeriodo($dataInicial, $dataFinal);
    // echo "Quantidade de chamados pendentes entre " . $dataInicial->format('Y-m-d') . " e " . $dataFinal->format('Y-m-d') . ": " . "Quantidade: " . $quantidadePendentes . "\n";

    // CONTAR CHAMADOS RESOLVIDOS POR PERÍODO
    // $repository = new TicketRepository();
    // $dataInicial = new DateTime('2026-07-01');
    // $dataFinal = new DateTime('2026-09-30');
    // $quantidadeResolvidos = $repository->contarChamadosResolvidosPorPeriodo($dataInicial, $dataFinal);
    // echo "Quantidade de chamados resolvidos entre " . $dataInicial->format('Y-m-d') . " e " . $dataFinal->format('Y-m-d') . ": " . "Quantidade" . $quantidadeResolvidos . "\n";

    // BUSCAR CHAMADOS POR STATUS
    // $repository = new TicketRepository();
    // $tickets = $repository->buscarTicketsPorStatus("pendente");
    // echo "Chamados com status 'pendente':\n";
    // if ($tickets) {
    //     foreach ($tickets as $ticket) {
    //         echo "<br>" . "ID: " . $ticket->getId() . ", Título: " . $ticket->getTitulo() . ", Status: " . $ticket->getStatus() . "<br>";
    //     }
    // } else {
    //     echo "Nenhum chamado encontrado com o status especificado.\n";
    // }

    // ATUALIZAR O STATUS DO CHAMADO
    // $repository = new TicketRepository();
    // $repository->atualizarStatusTicket(317, "pendente");

    // RELATÓRIO DE CHAMADOS POR PERÍODO
    // $repositorio = new TicketRepository();
    // $dataInicial = new DateTime('2026-06-01');
    // $dataFinal = new DateTime('2026-06-30');
    // $quantidadeChamados = $repositorio->contarChamadosPorPeriodo($dataInicial, $dataFinal);
    // echo "Quantidade de chamados entre " . $dataInicial->format('Y-m-d') . " e " . $dataFinal->format('Y-m-d') . ": " .  "quantidade: " . $quantidadeChamados . "\n";

    // RELATÓRIO DE CHAMADOS POR CATEGORIA
    // $repositorio = new TicketRepository();
    // $indicadores = $repositorio->relatorioPorCategoria();
    // foreach ($indicadores as $linha) {
    //     echo "Categoria: " . $linha['categoria'] . " | ";
    //     echo "Quantidade: " . $linha['quantidade'] . " | ";
    //     echo "Porcentagem: " . $linha['porcentagem'] . "%<br>";
    //  }

    // TAXA DE RESOLUÇÃO DE CHAMADOS
    // $repository = new TicketRepository();
    // $totalChamados = $repository->contarChamados();
    // $chamadosResolvidos = $repository->contarChamadosResolvidos();
    // $taxaResolucao = $repository->calcularTaxaResolucao($totalChamados, $chamadosResolvidos);
    // echo "Total de chamados: $totalChamados\n";
    // echo "Chamados resolvidos: $chamadosResolvidos\n";
    // echo "Taxa de resolução: $taxaResolucao%\n";

    // BUSCA DE UM CHAMADO POR DATA DE ENCERRAMENTO
    // $ticket = new TicketRepository();
    // $ticketEncontrado = $ticket->buscaPorDataEncerramento(new DateTime('2026-08-06'));
    // var_dump($ticketEncontrado);

    // BUSCA DE UM CHAMADO POR DATA DE ABERTURA
    // $ticket = new TicketRepository();
    // $ticketEncontrado = $ticket->buscaPorDataAbertura(new DateTime('2026-06-08'));
    // var_dump($ticketEncontrado);
    
    // BUSCA DE UM CHAMADO POR ID
    // $ticket = new TicketRepository();
    // echo $ticket->encontrarTicketPorId(317)->getStatus();

    // BUSCA DE TODOS OS CHAMADOS
    // $repositorio = new TicketRepository();
    // $json = $repositorio->EncontrarTodosTickets();
    // header('Content-Type: application/json; charset=utf-8');
    // echo $json;

    // CRIAR UM NOVO CHAMADO
    // $repository = new TicketRepository();
    // $dataAbertura = new DateTime(); 
    // $novoTicket = new Ticket(                                                       
    //     null,                                 
    //     null, // '550e8400-e29b-41d4-a716-446655440000',                       
    //     "Computador não liga",               
    //     "Quebrou tudo.",    
    //     null,                              
    //     "PAT-98765",                       
    //     "pendente",                            
    //     1,                                    
    //     1,                                    
    //     2,                                   
    //     $dataAbertura,                     
    //     null                                 
    // );
    // try {
    //     $repository->criarTicket($novoTicket);
    //     echo "\nDeu certo! O ticket foi criado no DB.\n";
    // } catch (Exception $e) {
    //     echo "\nDeu ruim na hora de salvar no banco: " . $e->getMessage() . "\n";
    // }

    // ATUALIZAÇÃO DA PRIORIDADE DE UM CHAMADO
    // $repository = new TicketRepository();
    // $idChamado = 317;
    // $novaPrioridade = "baixa";
    // try {
    //     $repository->atualizarPrioridadeTicket($idChamado, $novaPrioridade);
    //     echo "\nPrioridade do chamado atualizada com sucesso.\n";
    // } catch (Exception $e) {
    //     echo "\nErro ao atualizar prioridade do chamado: " . $e->getMessage() . "\n";
    // }

    // ENCERRAMENTO DE UM CHAMADO
    // $repository = new TicketRepository();
    // $idChamado = 317;
    // $novoStatus = "concluido";
    // try {
    //     $repository->encerrarTicket($idChamado, $novoStatus);
    //     echo "\nChamado encerrado com sucesso.\n";
    // } catch (Exception $e) {
    //     echo "\nErro ao encerrar chamado: " . $e->getMessage() . "\n";
    // }
?>