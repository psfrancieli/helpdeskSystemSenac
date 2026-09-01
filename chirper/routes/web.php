<?php

use src\repositories\TicketRepository;
use src\services\TicketServices;

require_once __DIR__ . '/../app/src/controllers/TicketController.php';
require_once __DIR__ . '/../app/src/repositories/UserRepository.php';
require_once __DIR__ . '/../app/src/utils/PasswordUtils.php';
require_once __DIR__ . '/../app/src/services/UserServices.php';
require_once __DIR__ . '/../app/src/models/User.php';
require_once __DIR__ . '/../app/src/utils/CpfUtils.php';
require_once __DIR__ . '/../app/src/utils/PhoneUtils.php';
require_once __DIR__ . '/../app/Http/Support/ChamadoActions.php';
require_once __DIR__ . '/../app/src/controllers/HistoryController.php';
 
if (!function_exists('apiJsonResponse')) {
    function apiJsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
 
if (!function_exists('apiReadJsonBody')) {
    function apiReadJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }
 
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
 
if (!function_exists('apiCurrentAuthUser')) {
    function apiCurrentAuthUser(): ?array
    {
        $sessionUser = $_SESSION['auth_user'] ?? null;
        if (!is_array($sessionUser)) {
            return null;
        }
 
        $requiredFields = ['id', 'nome', 'email', 'nivel', 'ativo'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $sessionUser)) {
                return null;
            }
        }
 
        return [
            'id' => (int) $sessionUser['id'],
            'nome' => (string) $sessionUser['nome'],
            'email' => (string) $sessionUser['email'],
            'nivel' => (string) $sessionUser['nivel'],
            'ativo' => (bool) $sessionUser['ativo'],
            'telefone' => (string) ($sessionUser['telefone'] ?? ''),
        ];
    }
}
 
if (!function_exists('apiRequireAuthUser')) {
    function apiRequireAuthUser(): array
    {
        $currentUser = apiCurrentAuthUser();
 
        if ($currentUser === null) {
            apiJsonResponse([
                'success' => false,
                'message' => 'Não autenticado.',
            ], 401);
        }
 
        return $currentUser;
    }
}
 
if (!function_exists('apiUserCan')) {
    function apiUserCan(array $user, array $allowedRoles): bool
    {
        return in_array($user['nivel'] ?? '', $allowedRoles, true);
    }
}
 
if (!function_exists('apiMapUsuarioCreationError')) {
    function apiMapUsuarioCreationError(array $dados, string $fallbackMessage): string
    {
        try {
            $repository = new UserRepository();
 
            $email = isset($dados['email']) ? trim(strtolower((string) $dados['email'])) : '';
            if ($email !== '' && $repository->encontrarPorEmail($email) !== null) {
                return 'Email já cadastrado.';
            }
 
            $cpf = isset($dados['cpf']) ? preg_replace('/\D/', '', (string) $dados['cpf']) : '';
            if (is_string($cpf) && strlen($cpf) === 11) {
                $cpfFormatado = CpfUtils::formatar($cpf);
                if ($repository->encontrarPorCpf($cpfFormatado) !== null) {
                    return 'CPF já cadastrado.';
                }
            }
 
            $telefoneRaw = isset($dados['telefone']) ? (string) $dados['telefone'] : '';
            if ($telefoneRaw !== '' && PhoneUtils::validar($telefoneRaw)) {
                $telefoneFormatado = PhoneUtils::formatar($telefoneRaw);
                $stmt = Database::getConnection()->prepare('SELECT id FROM "USUARIO" WHERE telefone = ? LIMIT 1');
                $stmt->execute([$telefoneFormatado]);
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    return 'Telefone já cadastrado.';
                }
            }
        } catch (Throwable $ignored) {
            return $fallbackMessage;
        }
 
        return $fallbackMessage;
    }
}
 
if (!function_exists('apiSerializeUsuario')) {
    function apiSerializeUsuario(User $usuario): array
    {
        return [
            'id' => $usuario->getId(),
            'nome' => $usuario->getNome(),
            'email' => $usuario->getEmail(),
            'nivel' => $usuario->getNivel(),
            'ativo' => $usuario->getAtivo(),
            'telefone' => $usuario->getTelefone(),
        ];
    }
}
 
$router->get('/api/chamados', function (): void {
    try {
        $actions = new ChamadoActions();
        $chamados = $actions->listarComTecnicoId();
 
        apiJsonResponse([
            'success' => true,
            'data' => $chamados,
        ]);
    } catch (Throwable $e) {
        apiJsonResponse([
            'success' => false,
            'message' => 'Erro ao buscar chamados.',
        ], 500);
    }
});
$router->get('/api/usuarios', function (): void {
    try {
        $currentUser = apiRequireAuthUser();
 
        if (!apiUserCan($currentUser, ['analista', 'adm'])) {
            apiJsonResponse([
                'success' => false,
                'message' => 'Acesso negado para listar usuários.',
            ], 403);
        }
 
        $service = new UserServices();
        $usuarios = $service->encontrarTodosUsuarios(new User(
            $currentUser['id'],
            null,
            $currentUser['nome'],
            '111.444.777-35',
            '(11) 99999-9999',
            $currentUser['email'],
            'nao_utilizado',
            $currentUser['nivel'],
            (bool) $currentUser['ativo']
        ));
 
        $nivelFiltro = isset($_GET['nivel']) ? trim((string) $_GET['nivel']) : '';
        $somenteTecnicos = isset($_GET['somente_tecnicos']) && in_array(strtolower((string) $_GET['somente_tecnicos']), ['1', 'true', 'sim'], true);
 
        $lista = array_map(static fn (User $usuario): array => apiSerializeUsuario($usuario), $usuarios ?? []);
 
        if ($somenteTecnicos || $nivelFiltro !== '') {
            $filtroNormalizado = $somenteTecnicos ? 'tecnico' : strtolower($nivelFiltro);
            $lista = array_values(array_filter($lista, static fn (array $usuario) => ($usuario['nivel'] ?? '') === $filtroNormalizado));
        }
 
        apiJsonResponse([
            'success' => true,
            'data' => $lista,
        ]);
    } catch (Throwable $e) {
        apiJsonResponse([
            'success' => false,
            'message' => 'Erro ao listar usuários.',
        ], 500);
    }
});
$router->get('/api/tecnicos', function (): void {
    try {
        $currentUser = apiRequireAuthUser();
 
        if (!apiUserCan($currentUser, ['analista', 'adm'])) {
            apiJsonResponse([
                'success' => false,
                'message' => 'Acesso negado para listar técnicos.',
                ], 403);
        }
                
 
        $service = new UserServices();
        $usuarios = $service->encontrarTodosUsuarios(new User(
            $currentUser['id'],
            null,
            $currentUser['nome'],
            '111.444.777-35',
            '(11) 99999-9999',
            $currentUser['email'],
            'nao_utilizado',
            $currentUser['nivel'],
            (bool) $currentUser['ativo']
        ));
 
        $lista = array_values(array_filter(
            array_map(static fn (User $usuario): array => apiSerializeUsuario($usuario), $usuarios ?? []),
            static fn (array $usuario) => ($usuario['nivel'] ?? '') === 'tecnico'
        ));
 
        apiJsonResponse([
            'success' => true,
            'data' => $lista,
        ]);
    } catch (Throwable $e) {
        apiJsonResponse([
            'success' => false,
            'message' => 'Erro ao listar técnicos.',
        ], 500);
    }
});
$router->get('/api/historico', function (): void {

    try {

        apiRequireAuthUser();

        $idChamado = isset($_GET['id_chamado'])
            ? (int) $_GET['id_chamado']
            : 0;

        if ($idChamado <= 0) {
            apiJsonResponse([
                'success' => false,
                'message' => 'id_chamado é obrigatório.'
            ], 400);
        }

        $controller = new HistoryController();

        $controller->getByTicketId($idChamado);

    } catch (Throwable $e) {

        apiJsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});
$router->post('/api/historico', function (): void {

    try {

        $currentUser = apiRequireAuthUser();

        $payload = apiReadJsonBody();

        $descricao = isset($payload['descricao'])
            ? trim((string) $payload['descricao'])
            : '';

        $idChamado = isset($payload['id_chamado'])
            ? (int) $payload['id_chamado']
            : 0;

        if ($descricao === '') {

            apiJsonResponse([
                'success' => false,
                'message' => 'O comentário é obrigatório.'
            ], 400);
        }

        if ($idChamado <= 0) {

            apiJsonResponse([
                'success' => false,
                'message' => 'Chamado inválido.'
            ], 400);
        }

        $controller = new HistoryController();

        $controller->create([
            'descricao' => $descricao,

            'id_chamado' => $idChamado,

            'id_usuario' => (int) $currentUser['id']
        ]);

    } catch (Throwable $e) {

        apiJsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});
$router->post('/api/chamados', [CalledController::class, 'store']);
$router->post('/api/usuarios', function (): void {
    try {
        $dados = apiReadJsonBody();
 
        foreach (['nome', 'cpf', 'telefone', 'email', 'senha'] as $campo) {
            if (!array_key_exists($campo, $dados) || $dados[$campo] === '' || $dados[$campo] === null) {
                apiJsonResponse([
                    'success' => false,
                    'message' => sprintf('Campo obrigatório ausente: %s', $campo),
                ], 400);
            }
        }
 
        $dados['id'] = null;
        $dados['uuid'] = null;
 
        $usuarioLogado = new User(
            0,
            null,
            'Sistema',
            '111.444.777-35',
            '(11) 99999-9999',
            'sistema@helpdesk.local',
            'nao_utilizado',
            'analista',
            true
        );
 
        $service = new UserServices();
        $service->cadastrarUsuario($usuarioLogado, $dados);
 
        apiJsonResponse([
            'success' => true,
            'message' => 'Usuário cadastrado com sucesso.',
        ], 201);
    } catch (Throwable $e) {
        $message = trim((string) $e->getMessage());
 
        if ($message === 'Erro ao criar usuario') {
            $message = apiMapUsuarioCreationError($dados ?? [], 'Não foi possível criar usuário. Verifique email, CPF e telefone.');
        }
 
        apiJsonResponse([
            'success' => false,
            'message' => $message,
        ], 400);
    }
});
 
$router->post('/api/chamados/atribuir-tecnico', function (): void {
    try {
        $currentUser = apiRequireAuthUser();
 
        if (!apiUserCan($currentUser, ['analista', 'adm'])) {
            apiJsonResponse([
                'success' => false,
                'message' => 'Acesso negado para atribuição de técnico.',
            ], 403);
        }
 
        $payload = apiReadJsonBody();
        $chamadoId = isset($payload['id_chamado']) ? (int) $payload['id_chamado'] : 0;
        $tecnicoId = isset($payload['tecnico_id']) ? (int) $payload['tecnico_id'] : (isset($payload['id_responsavel']) ? (int) $payload['id_responsavel'] : 0);
 
        if ($chamadoId <= 0 || $tecnicoId <= 0) {
            apiJsonResponse([
                'success' => false,
                'message' => 'id_chamado e tecnico_id são obrigatórios.',
            ], 400);
        }
 
        $actions = new ChamadoActions();
        $chamado = $actions->atribuirTecnico($chamadoId, $tecnicoId);
 
        apiJsonResponse([
            'success' => true,
            'data' => $chamado,
        ]);
    } catch (InvalidArgumentException $e) {
        apiJsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);
    } catch (RuntimeException $e) {
        apiJsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
        ], 404);
    } catch (Throwable $e) {
        apiJsonResponse([
            'success' => false,
            'message' => 'Erro ao atribuir técnico.',
        ], 500);
    }
});

$router->post('/api/usuarios/alterar-nivel', function (): void {
	try {
		$currentUser = apiRequireAuthUser();

		if (!apiUserCan($currentUser, ['analista', 'adm'])) {
			apiJsonResponse([
				'success' => false,
				'message' => 'Acesso negado para alterar nível de usuário.',
			], 403);
		}

		$payload = apiReadJsonBody();
		$id = isset($payload['id']) ? (int) $payload['id'] : 0;
		$nivel = isset($payload['nivel']) ? (string) $payload['nivel'] : '';

		if ($id <= 0 || $nivel === '') {
			apiJsonResponse([
				'success' => false,
				'message' => 'id e nivel são obrigatórios.',
			], 400);
		}

		$usuarioLogado = new User(
			$currentUser['id'],
			'',
			$currentUser['nome'],
			'111.444.777-35',
			$currentUser['telefone'] !== '' ? $currentUser['telefone'] : '(11) 99999-9999',
			$currentUser['email'],
			'nao_utilizado',
			$currentUser['nivel'],
			(bool) $currentUser['ativo']
		);

		$service = new UserServices();
		$service->alterarNivel($usuarioLogado, $id, $nivel);

		apiJsonResponse([
			'success' => true,
			'message' => 'Nível atualizado com sucesso.',
		]);
	} catch (Throwable $e) {
		apiJsonResponse([
			'success' => false,
			'message' => $e->getMessage(),
		], 400);
	}
});

$router->post('/api/usuarios/resetar-senha', function (): void {
	try {
		$currentUser = apiRequireAuthUser();

		if (!apiUserCan($currentUser, ['analista', 'adm'])) {
			apiJsonResponse([
				'success' => false,
				'message' => 'Acesso negado para redefinir senha.',
			], 403);
		}

		$payload = apiReadJsonBody();
		$id = isset($payload['id']) ? (int) $payload['id'] : 0;

		if ($id <= 0) {
			apiJsonResponse([
				'success' => false,
				'message' => 'id é obrigatório.',
			], 400);
		}

		$usuarioLogado = new User(
			$currentUser['id'],
			'',
			$currentUser['nome'],
			'111.444.777-35',
			$currentUser['telefone'] !== '' ? $currentUser['telefone'] : '(11) 99999-9999',
			$currentUser['email'],
			'nao_utilizado',
			$currentUser['nivel'],
			(bool) $currentUser['ativo']
		);

		$service = new UserServices();
		$service->resetarSenha($usuarioLogado, 'Help123@', $id);

		apiJsonResponse([
			'success' => true,
			'message' => 'Senha redefinida para a senha padrão.',
		]);
	} catch (Throwable $e) {
		apiJsonResponse([
			'success' => false,
			'message' => $e->getMessage(),
		], 400);
	}
});

$router->post('/api/chamados/atualizar-status', function (): void {
    try {
        $currentUser = apiRequireAuthUser();
 
        if (!apiUserCan($currentUser, ['tecnico', 'analista', 'adm'])) {
            apiJsonResponse([
                'success' => false,
                'message' => 'Acesso negado para atualização de status.',
            ], 403);
        }
 
        $payload = apiReadJsonBody();
        $chamadoId = isset($payload['id_chamado']) ? (int) $payload['id_chamado'] : 0;
        $status = isset($payload['status']) ? (string) $payload['status'] : '';
 
        if ($chamadoId <= 0 || $status === '') {
            apiJsonResponse([
                'success' => false,
                'message' => 'id_chamado e status são obrigatórios.',
            ], 400);
        }
 
        $actions = new ChamadoActions();
        $currentChamado = $actions->detalhar($chamadoId);
 
        if (($currentUser['nivel'] ?? '') === 'tecnico' && (int) ($currentChamado['tecnico_id'] ?? 0) !== (int) ($currentUser['id'] ?? 0)) {
            apiJsonResponse([
                'success' => false,
                'message' => 'Técnico só pode alterar status de chamados atribuídos a si.',
            ], 403);
        }
 
        $chamadoAtualizado = $actions->atualizarStatus($chamadoId, $status);
 
        apiJsonResponse([
            'success' => true,
            'data' => $chamadoAtualizado,
        ]);
    } catch (InvalidArgumentException $e) {
        apiJsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);
    } catch (RuntimeException $e) {
        apiJsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
        ], 404);
    } catch (Throwable $e) {
        apiJsonResponse([
            'success' => false,
            'message' => 'Erro ao atualizar status do chamado.',
        ], 500);
    }
});
$router->get('/api/categorias', function (): void {
    try {
        apiRequireAuthUser();
 
        $stmt = Database::getConnection()->query('SELECT id, nome FROM "CATEGORIA" ORDER BY nome ASC');
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
        apiJsonResponse([
            'success' => true,
            'data' => $categorias,
        ]);
    } catch (Throwable $e) {
        apiJsonResponse([
            'success' => false,
            'message' => 'Erro ao listar categorias.',
        ], 500);
    }
});
$router->get('/api/categorias', function (): void {
	try {
		apiRequireAuthUser();

		$stmt = Database::getConnection()->query('SELECT id, nome FROM "CATEGORIA" ORDER BY nome ASC');
		$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

		apiJsonResponse([
			'success' => true,
			'data' => $categorias,
		]);
	} catch (Throwable $e) {
		apiJsonResponse([
			'success' => false,
			'message' => 'Erro ao listar categorias.',
		], 500);
	}
});
$router->post('/api/login', function (): void {
    try {
        $payload = apiReadJsonBody();
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $senha = isset($payload['senha']) ? (string) $payload['senha'] : '';
 
        if ($email === '' || $senha === '') {
            apiJsonResponse([
                'success' => false,
                'message' => 'Email e senha são obrigatórios.',
            ], 400);
        }
 
        $repository = new UserRepository();
        $user = $repository->encontrarPorEmail($email);
 
        if ($user === null || !$user->getAtivo() || !PasswordUtils::verificar($senha, $user->getSenha())) {
            apiJsonResponse([
                'success' => false,
                'message' => 'Credenciais inválidas.',
            ], 401);
        }
 
        session_regenerate_id(true);
 
        $_SESSION['auth_user'] = [
            'id' => $user->getId(),
            'nome' => $user->getNome(),
            'email' => $user->getEmail(),
            'nivel' => $user->getNivel(),
            'ativo' => $user->getAtivo(),
            'telefone' => $user->getTelefone(),
        ];
 
        apiJsonResponse([
            'success' => true,
            'data' => apiCurrentAuthUser(),
        ]);
    } catch (Throwable $e) {
        apiJsonResponse([
            'success' => false,
            // 'message' => 'Erro ao autenticar usuário.',
            'message' => $e->getMessage(),
        ], 500);
    }
});
 
$router->get('/api/me', function (): void {
    $currentUser = apiCurrentAuthUser();
 
    if ($currentUser === null) {
        apiJsonResponse([
            'success' => false,
            'message' => 'Não autenticado.',
        ], 401);
    }
 
    apiJsonResponse([
        'success' => true,
        'data' => $currentUser,
    ]);
});
 
$router->post('/api/me', function (): void {
    try {
        $currentUser = apiRequireAuthUser();
 
        $payload = apiReadJsonBody();
        $telefoneRaw = isset($payload['telefone']) ? (string) $payload['telefone'] : '';
 
        if ($telefoneRaw === '') {
            apiJsonResponse([
                'success' => false,
                'message' => 'Telefone é obrigatório.',
            ], 400);
        }
 
        $usuarioLogado = new User(
            $currentUser['id'],
            '',
            $currentUser['nome'],
            '111.444.777-35',
            $currentUser['telefone'] !== '' ? $currentUser['telefone'] : '(11) 99999-9999',
            $currentUser['email'],
            'nao_utilizado',
            $currentUser['nivel'],
            (bool) $currentUser['ativo']
        );
 
        $service = new UserServices();
        $service->atualizarTelefone($usuarioLogado, $telefoneRaw);
 
        $telefoneFormatado = PhoneUtils::formatar($telefoneRaw);
        $_SESSION['auth_user']['telefone'] = $telefoneFormatado;
 
        apiJsonResponse([
            'success' => true,
            'data' => apiCurrentAuthUser(),
        ]);
    } catch (InvalidArgumentException $e) {
        apiJsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);
    } catch (Throwable $e) {
        apiJsonResponse([
            'success' => false,
            'message' => 'Erro ao atualizar telefone.',
        ], 500);
    }
});
 
$router->post('/api/logout', function (): void {
    $_SESSION = [];
 
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
 
    session_destroy();
 
    apiJsonResponse([
        'success' => true,
        'data' => null,
    ]);
});
 
 
// ---------------------Gerar relatorios ------------------------
$router->post('/api/gerarRelatorio/metricasPeriodo', function(): void {
    $ticketController = new TicketController();
    $ticketController->metricasPorPeriodo();
});

$router->post('/api/gerarRelatorio/dashboardMetrica', function(): void {
    $ticketController = new TicketController();
    $ticketController->dashboardPorPeriodo();
});

$router->post('/api/gerarRelatorio/metricaCategoria', function(): void {
    $ticketController = new TicketController();
    $ticketController->relatorioCategoriaPorPeriodo();
});