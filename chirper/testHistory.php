<?php

require __DIR__ . '/src/controllers/HistoryController.php';

$history = new HistoryController();

$history->getByTicketId(4);

# teste
?>