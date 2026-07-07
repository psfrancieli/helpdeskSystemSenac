<?php

require_once __DIR__ . '/../configs/Database.php';
require_once __DIR__ . '/../Models/Ticket.php';

class TicketRepository
{
    public function listAll(): array
    {
        try {
            $sql = '
                SELECT
                    c.id,
                    c.uuid,
                    c.titulo,
                    c.patrimonio,
                    c.prioridade,
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

    public function createTicket(Ticket $ticket): array
    {
        try {
            $sql = 'INSERT INTO "CHAMADO" (uuid, titulo, descricao, prioridade, data_abertura, data_encerramento, patrimonio, id_categoria, id_usuario, id_responsavel, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id, uuid, titulo, descricao, prioridade, data_abertura, data_encerramento, patrimonio, id_categoria, id_usuario, id_responsavel, status';
            $stmt = Database::getConnection()->prepare($sql);

            $stmt->execute([
                $ticket->getUuid(),
                $ticket->getTitulo(),
                $ticket->getDescricao(),
                $ticket->getPrioridade(),
                $ticket->getDataAbertura(),
                $ticket->getDataEncerramento(),
                $ticket->getPatrimonio(),
                $ticket->getIdCategoria(),
                $ticket->getIdUsuario(),
                $ticket->getIdResponsavel(),
                $ticket->getStatus(),
            ]);

            $created = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$created) {
                throw new RuntimeException('Erro ao recuperar chamado criado');
            }

            return $created;
        } catch (PDOException $e) {
            throw new RuntimeException('Erro ao criar chamado no banco', 0, $e);
        }
    }

    // Backward compatibility aliases during migration.
    public function listarTodos(): array
    {
        return $this->listAll();
    }

    public function CriarTicket(Ticket $ticket): array
    {
        return $this->createTicket($ticket);
    }
}
