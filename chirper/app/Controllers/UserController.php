<?php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

class UserController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function getById(int $id): User
    {
        return $this->userRepository->listarUsuarioPorId($id);
    }

    public function createUser(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');

        try {
            $payload = $this->getRequestData();

            $nome = trim((string)($payload['nome'] ?? ''));
            $cpf = trim((string)($payload['cpf'] ?? ''));
            $telefone = trim((string)($payload['telefone'] ?? ''));
            $email = trim((string)($payload['email'] ?? ''));
            $senha = trim((string)($payload['senha'] ?? ''));
            $nivel = trim((string)($payload['nivel'] ?? 'usuario'));
            $ativo = (bool)($payload['ativo'] ?? true);

            if (strlen($nome) < 3) {
                throw new InvalidArgumentException('Informe um nome valido');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Informe um email valido');
            }

            if ($cpf === '') {
                throw new InvalidArgumentException('Informe um CPF valido');
            }

            if ($telefone === '') {
                throw new InvalidArgumentException('Informe um telefone valido');
            }

            if (strlen($senha) < 6) {
                throw new InvalidArgumentException('Informe uma senha com pelo menos 6 caracteres');
            }

            $niveisValidos = ['adm', 'analista', 'tecnico', 'usuario'];
            if (!in_array($nivel, $niveisValidos, true)) {
                throw new InvalidArgumentException('Nivel de acesso invalido');
            }

            $usuario = new User(
                0,
                null,
                $nome,
                $cpf,
                $telefone,
                $email,
                password_hash($senha, PASSWORD_DEFAULT),
                $nivel,
                $ativo
            );

            $created = $this->userRepository->criarUsuario($usuario);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Usuario criado com sucesso',
                'data' => $created,
            ], JSON_UNESCAPED_UNICODE);
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro interno ao criar usuario',
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro inesperado ao processar a requisicao',
            ], JSON_UNESCAPED_UNICODE);
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
}
