<?php

require_once __DIR__ . '/../models/History.php';
require_once __DIR__ . '/../repositories/HistoryRepository.php';

class HistoryService
{
    public static function create(History $history): bool
    {
        
        if (trim($history->getDescricao()) === '') {
            return false;
        }

        
        $result = HistoryRepository::create($history);

        if ($result) {
            self::LogRegister($history);
        }

        return $result;
    }

    public static function getById(int $id)
    {
        return HistoryRepository::getById($id);
    }

    private static function LogRegister(History $history): void
{
    $mensagem =
        "[" . date("d/m/Y H:i:s") . "] " .
        "Chamado: #" . $history->getChamado() .
        " | Técnico: " . $history->getTecnico() .
        " | " . $history->getDescricao() .
        PHP_EOL;

    file_put_contents(
        __DIR__ . "/../../../history.log",
        $mensagem,
        FILE_APPEND
    );
}

}