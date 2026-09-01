<?php

require_once __DIR__ . '/../models/History.php';
require_once __DIR__ . '/../repositories/HistoryRepository.php';

class HistoryService
{
    public static function create(History $history): bool{
        if (trim($history->getDescricao()) === '') {
            return false;
        }        
        $result = HistoryRepository::create($history);
        return $result;
    }

    public static function getById(int $id): ?History
    {
        $result = HistoryRepository::getById($id);
        if (!$result){
            throw new Exception("Historico não encontrado!");
        }
        return $result;
    }

    public static function getByTicketId(int $id): array
{
    return HistoryRepository::getByTicketId($id);
}

    

}