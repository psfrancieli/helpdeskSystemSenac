<?php
require_once __DIR__ . "/../configs/Database.php";
require_once __DIR__ . "/../models/Ticket.php";

class TicketRepository{
    public function EncontrarTicketPorId(int $id):Ticket{
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

    public function EncontrarTodosTickets(): string {
        try {
            $sql = 'SELECT * FROM "CHAMADO" ORDER BY data_abertura DESC';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute();
            $dados = $stmt->fetchAll();
            $tickets = [];
            foreach($dados as $linha){
                $dataAberturaObj = !empty($linha['data_abertura']) ? new DateTime($linha['data_abertura']) : null;
                $dataEncerramentoObj = !empty($linha['data_encerramento']) ? new DateTime($linha['data_encerramento']) : null;
                
                $ticket = new Ticket(
                    $linha['id'],               
                    $linha['uuid'],           
                    $linha['titulo'],          
                    $linha['descricao'],        
                    $linha['prioridade'],       
                    $linha['patrimonio'],      
                    $linha['status'],           
                    $linha['id_categoria'], 
                    $linha['id_usuario'],      
                    $linha['id_responsavel'],  
                    $dataAberturaObj,          
                    $dataEncerramentoObj
                );
                $tickets[] = $ticket->getAll(); 
            }
            return json_encode($tickets, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao buscar chamados no banco", 0, $e);
        }
    }

    public function CriarTicket(Ticket $ticket):void{
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

    public function atualizarPrioridadeTicket(int $id, string $prioridade):void{
        try {
            $sql = 'UPDATE "CHAMADO" SET prioridade = ? WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$prioridade, $id]);
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao atualizar prioridade do chamado no banco",0 , $e);
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
}

    // BUSCA DE UM CHAMADO POR ID
    // $ticket = new TicketRepository();
    // echo $ticket->EncontrarTicketPorId(317)->getTitulo();

    // BUSCA DE TODOS OS CHAMADOS
    // $repositorio = new TicketRepository();
    // $json = $repositorio->EncontrarTodosTickets();
    // header('Content-Type: application/json; charset=utf-8');
    // echo $json;

    // BLOCO DE TESTE: CRIAR UM NOVO CHAMADO
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
    //     $repository->CriarTicket($novoTicket);
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