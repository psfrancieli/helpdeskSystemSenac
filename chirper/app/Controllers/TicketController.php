<?php

require_once __DIR__ . '/../Models/Ticket.php';
require_once __DIR__ . '/../repositories/TicketRepository.php';

class TicketController
{
    private TicketRepository $repository;

    public function __construct()
    {
        $this->repository = new TicketRepository();
    }

    public function index(): void
    {
        $this->sendJsonHeaders();

        try {
            $tickets = $this->repository->listAll();
            $this->respondJson([
                'success' => true,
                'data' => $tickets,
            ]);
        } catch (Throwable $e) {
            $this->respondJson([
                'success' => false,
                'error' => 'Erro ao buscar chamados',
            ], 500);
        }
    }

    public function store(): void
    {
        $this->sendJsonHeaders();

        try {
            $payload = $this->getRequestData();
            $validated = $this->validateTicketPayload($payload);

            $ticket = new Ticket(
                0,
                $this->generateUuid(),
                $validated['titulo'],
                $validated['descricao'],
                $validated['prioridade'],
                date('Y-m-d H:i:s'),
                null,
                $validated['patrimonio'],
                $validated['id_categoria'],
                $validated['id_usuario'],
                $validated['id_responsavel'],
                $validated['status']
            );

            $created = $this->repository->createTicket($ticket);

            $this->respondJson([
                'success' => true,
                'message' => 'Chamado criado com sucesso',
                'data' => $created,
            ], 201);
        } catch (InvalidArgumentException $e) {
            $this->respondJson([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (RuntimeException $e) {
            $this->respondJson([
                'success' => false,
                'error' => 'Erro interno ao criar chamado',
            ], 500);
        } catch (Throwable $e) {
            $this->respondJson([
                'success' => false,
                'error' => 'Erro inesperado ao processar a requisicao',
            ], 500);
        }
    }

    private function getRequestData(): array
    {
        $rawBody = file_get_contents('php://input') ?: '';

        if ($rawBody === '') {
            return $_POST;
        }

        $decoded = json_decode($rawBody, true);

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Corpo da requisicao invalido');
        }

        return $decoded;
    }

    private function validateTicketPayload(array $payload): array
    {
        $titulo = trim((string) ($payload['titulo'] ?? ''));
        $descricao = trim((string) ($payload['descricao'] ?? ''));
        $patrimonio = trim((string) ($payload['patrimonio'] ?? ''));
        $prioridade = trim((string) ($payload['prioridade'] ?? ''));
        $status = trim((string) ($payload['status'] ?? 'pendente'));
        $idCategoria = filter_var($payload['id_categoria'] ?? null, FILTER_VALIDATE_INT);
        $idUsuario = filter_var($payload['id_usuario'] ?? null, FILTER_VALIDATE_INT);
        $idResponsavel = filter_var($payload['id_responsavel'] ?? null, FILTER_VALIDATE_INT);

        $prioridadesValidas = ['baixa', 'media', 'alta', 'muito alta'];
        $statusValidos = ['pendente', 'cancelado', 'concluido'];

        if (strlen($titulo) < 3) {
            throw new InvalidArgumentException('Informe um titulo com pelo menos 3 caracteres');
        }

        if (strlen($descricao) < 5) {
            throw new InvalidArgumentException('Informe uma descricao com pelo menos 5 caracteres');
        }

        if ($patrimonio === '') {
            throw new InvalidArgumentException('Informe o patrimonio do equipamento');
        }

        if (!in_array($prioridade, $prioridadesValidas, true)) {
            throw new InvalidArgumentException('Prioridade invalida');
        }

        if ($idCategoria === false || $idCategoria < 1) {
            throw new InvalidArgumentException('Categoria invalida');
        }

        if ($idUsuario === false || $idUsuario < 1) {
            throw new InvalidArgumentException('Solicitante invalido');
        }

        if (!in_array($status, $statusValidos, true)) {
            throw new InvalidArgumentException('Status invalido');
        }

        return [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'patrimonio' => $patrimonio,
            'prioridade' => $prioridade,
            'status' => $status,
            'id_categoria' => $idCategoria,
            'id_usuario' => $idUsuario,
            'id_responsavel' => $idResponsavel === false ? null : $idResponsavel,
        ];
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function sendJsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
    }

    private function respondJson(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
