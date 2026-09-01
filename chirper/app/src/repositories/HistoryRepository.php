<?php

require_once __DIR__ . "/../configs/Database.php";
require_once __DIR__ . "/../models/History.php";

class HistoryRepository
{
    public static function create(History $history): bool
    {
        try {
            $db = new Database();

            $sql = '
                INSERT INTO public."HISTORICO"
                (
                    descricao,
                    data,
                    id_chamado,
                    id_usuario_tecnico
                )
                VALUES (?, ?, ?, ?)
            ';

            $stmt = $db->getConnection()->prepare($sql);

            return $stmt->execute([
                $history->getDescricao(),
                $history->getData()->format('Y-m-d H:i:s'),
                $history->getChamado(),
                $history->getUsuario()
            ]);

        } catch (PDOException $e) {
            throw new RuntimeException(
                "Erro ao criar histórico no banco",
                0,
                $e
            );
        }
    }

    public static function getById(int $id): ?History
    {
        try {
            $db = new Database();

            $sql = 'SELECT * FROM "HISTORICO" WHERE id = ?';

            $stmt = $db->getConnection()->prepare($sql);

            $stmt->execute([$id]);

            $history = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$history) {
                return null;
            }

            return new History(
                $history['descricao'],
                (int) $history['id_chamado'],
                (int) $history['id_usuario_tecnico'],
                new DateTime($history['data'])
            );

        } catch (PDOException $e) {
            throw new RuntimeException(
                "Erro ao buscar o histórico no banco",
                0,
                $e
            );
        }
    }

    public static function getByTicketId(int $id): array
    {
        try {
            $db = new Database();

            $sql = '
                SELECT *
                FROM "HISTORICO"
                WHERE id_chamado = ?
                ORDER BY data ASC
            ';

            $stmt = $db->getConnection()->prepare($sql);

            $stmt->execute([$id]);

            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $historicos = [];

            foreach ($dados as $dado) {
                $historicos[] = new History(
                    $dado['descricao'],
                    (int) $dado['id_chamado'],
                    (int) $dado['id_usuario_tecnico'],
                    new DateTime($dado['data'])
                );
            }

            return $historicos;

        } catch (PDOException $e) {
            throw new RuntimeException(
                "Erro ao buscar o histórico no banco",
                0,
                $e
            );
        }
    }
}