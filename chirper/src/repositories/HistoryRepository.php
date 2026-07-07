<?php
require_once __DIR__ . "/../configs/Database.php";
require_once __DIR__ . "/../models/History.php";

class HistoryRepository {

    public static function create(History $history)
    {
        try {
            $db = new Database();

            $sql = 'INSERT INTO public."HISTORICO"
                    (descricao, data, id_chamado, id_usuario_tecnico)
                    VALUES (?, ?, ?, ?)';

            $stmt = $db->getConnection()->prepare($sql);

            $stmt->execute([
                $history->getDescricao(),
                $history->getData()->format('Y-m-d H:i:s'),
                $history->getChamado(),
                $history->getTecnico()
            ]);

            echo "Inserido!";

        } catch (PDOException $e) {
            echo "Erro: " . $e->getMessage();
        }
    }

    public static function getById(int $id) {

        try {

        $db = new Database();
        $sql = 'SELECT * FROM "HISTORICO" WHERE id = ?';
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute([$id]);
        $history = $stmt->fetch(PDO::FETCH_ASSOC);
        return json_encode($history);

    } catch (PDOException $e) {

        return json_encode([
            "erro" => $e->getMessage()
        ]);
    }
    }
}

?>