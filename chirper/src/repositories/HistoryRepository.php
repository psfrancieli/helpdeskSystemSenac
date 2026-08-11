<?php
require_once __DIR__ . "/../configs/Database.php";
require_once __DIR__ . "/../models/History.php";

class HistoryRepository
{

    public static function create(History $history): bool
    {
        try {
            $db = new Database();

            $sql = 'INSERT INTO public."HISTORICO"
                    (descricao, data, id_chamado, id_usuario_tecnico)
                    VALUES (?, ?, ?, ?)';

            $stmt = $db->getConnection()->prepare($sql);

            return $stmt->execute([
                $history->getDescricao(),
                $history->getData()->format('Y-m-d H:i:s'),
                $history->getChamado(),
                $history->getTecnico()
            ]);
        } catch (PDOException $e) {
            echo "Erro: " . $e->getMessage();
            return false;
        }
    }

    public static function getById(int $id)
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
                new DateTime($history['data']),
                $history['descricao'],
                $history['id_chamado'],
                $history['id_usuario_tecnico']
            );
        } catch (PDOException $e) {

            throw new RuntimeException("Erro ao buscar o historico no banco", 0, $e);
        }
    }

    public static function getByTicketId(int $id): array
    {
        try {
            $db = new Database();

            $sql = 'SELECT * FROM "HISTORICO" WHERE id_chamado = ?';

            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([$id]);

            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $historicos = [];

            foreach ($dados as $dado) {
                $historicos[] = new History(
                    new DateTime($dado["data"]),
                    $dado["descricao"],
                    $dado["id_chamado"],
                    $dado["id_usuario_tecnico"]
                );
            }

            return $historicos;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Erro ao buscar o historico no banco",
                0,
                $e
            );
        }
    }
}
