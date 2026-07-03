<?php

require_once __DIR__ . '/../configs/Database.php';

class ChamadoRepository
{
    public function listarTodos(): array
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
}
