<?php

require_once __DIR__ . '/../../src/configs/Database.php';

class ChamadoActions
{
    public function listarComTecnicoId(): array
    {
        $sql = '
            SELECT
                c.id,
                c.uuid,
                c.titulo,
                c.descricao,
                c.patrimonio,
                c.prioridade,
                c.data_abertura,
                c.data_encerramento,
                c.status,
                cat.nome  AS categoria,
                c.id_usuario AS id_usuario,
                us.nome   AS solicitante,
                c.id_responsavel AS tecnico_id,
                resp.nome AS responsavel
            FROM "CHAMADO" c
            LEFT JOIN "CATEGORIA" cat  ON c.id_categoria   = cat.id
            LEFT JOIN "USUARIO"   us   ON c.id_usuario      = us.id
            LEFT JOIN "USUARIO"   resp ON c.id_responsavel  = resp.id
            ORDER BY c.data_abertura DESC
        ';

        $stmt = Database::getConnection()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atribuirTecnico(int $chamadoId, int $tecnicoId): array
    {
        $this->garantirChamadoExiste($chamadoId);
        $this->garantirTecnicoValido($tecnicoId);

        $sql = 'UPDATE "CHAMADO" SET id_responsavel = ? WHERE id = ?';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$tecnicoId, $chamadoId]);

        return $this->detalhar($chamadoId);
    }

    public function atualizarStatus(int $chamadoId, string $status): array
    {
        $statusNormalizado = trim(strtolower($status));
        $statusPermitidos = ['pendente', 'concluido', 'cancelado', 'não resolvido'];

        if (!in_array($statusNormalizado, $statusPermitidos, true)) {
            throw new InvalidArgumentException('Status inválido. Use pendente, concluido, cancelado, não resolvido.');
        }

        $this->garantirChamadoExiste($chamadoId);

        if ($statusNormalizado === 'concluido' || $statusNormalizado === 'cancelado') {
            $sql = 'UPDATE "CHAMADO" SET status = ?, data_encerramento = NOW() WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$statusNormalizado, $chamadoId]);
        } else {
            $sql = 'UPDATE "CHAMADO" SET status = ?, data_encerramento = NULL WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$statusNormalizado, $chamadoId]);
        }

        return $this->detalhar($chamadoId);
    }

    public function detalhar(int $chamadoId): array
    {
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
                c.id_responsavel AS tecnico_id,
                resp.nome AS responsavel
            FROM "CHAMADO" c
            LEFT JOIN "CATEGORIA" cat  ON c.id_categoria   = cat.id
            LEFT JOIN "USUARIO"   us   ON c.id_usuario      = us.id
            LEFT JOIN "USUARIO"   resp ON c.id_responsavel  = resp.id
            WHERE c.id = ?
            LIMIT 1
        ';

        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$chamadoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RuntimeException('Chamado não encontrado.');
        }

        return $row;
    }

    private function garantirChamadoExiste(int $chamadoId): void
    {
        $sql = 'SELECT id FROM "CHAMADO" WHERE id = ? LIMIT 1';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$chamadoId]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Chamado não encontrado.');
        }
    }

    private function garantirTecnicoValido(int $tecnicoId): void
    {
        $sql = 'SELECT id FROM "USUARIO" WHERE id = ? AND nivel = ? AND ativo = TRUE LIMIT 1';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([$tecnicoId, 'tecnico']);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Técnico inválido ou inativo.');
        }
    }
}
