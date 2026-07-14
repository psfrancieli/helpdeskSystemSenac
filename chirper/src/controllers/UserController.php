<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../services/UserServices.php';

/* 
Caso vcs estejam usando essa classe de exemplo repare que as Resposta ou Reponse sempre tem o "success" , isso sera muito util para
verificar no front end entao usem de modelo 
no react ele receberia mais ou menos assim 
{
    "success": true,
    "data": {
        "id": 1,
        "nome": "Carlos",
        "email": "carlos@email.com",
        "telefone": "(15)99999-9999"
    }
}
    o success vai servir justamente para verificações

*/
class UserController extends Controller
{
    private UserServices $service;

    public function __construct()
    {
        $this->service = new UserServices();
    }

    // ============================
    // Buscar usuário por ID
    // ============================
    public function encontrarPorId(User $usuarioLogado, int $id): void
    {
        try {

            $usuario = $this->service->encontrarPorId($usuarioLogado, $id);

            $this->response([
                "success" => true,
                "data" => $usuario
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }

    // ============================
    // Buscar todos os usuários
    // ============================
    public function encontrarTodos(User $usuarioLogado): void
    {
        try {

            $usuarios = $this->service->encontrarTodosUsuarios($usuarioLogado);

            $this->response([
                "success" => true,
                "data" => $usuarios
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }

    // ============================
    // Buscar por CPF
    // ============================
    public function encontrarPorCpf(User $usuarioLogado): void
    {
        try {

            $dados = $this->getBody();

            $usuario = $this->service->encontrarPorCpf(
                $usuarioLogado,
                $dados['cpf']
            );

            $this->response([
                "success" => true,
                "data" => $usuario
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }

    // ============================
    // Cadastrar usuário
    // ============================
    public function cadastrarUsuario(User $usuarioLogado): void
    {
        try {

            $dados = $this->getBody();

            $this->service->cadastrarUsuario($usuarioLogado, $dados);

            $this->response([
                "success" => true,
                "message" => "Usuário cadastrado com sucesso."
            ], 201);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }

    // ============================
    // Deletar usuário
    // ============================
    public function deletarUsuario(User $usuarioLogado, int $id): void
    {
        try {

            $this->service->deletarUsuario($usuarioLogado, $id);

            $this->response([
                "success" => true,
                "message" => "Usuário desativado."
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }

    // ============================
    // Resetar senha
    // ============================
    public function resetarSenha(User $usuarioLogado, int $id): void
    {
        try {

            $dados = $this->getBody();

            $this->service->resetarSenha(
                $usuarioLogado,
                $dados['senha'],
                $id
            );

            $this->response([
                "success" => true,
                "message" => "Senha alterada com sucesso."
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }

    // ============================
    // Atualizar telefone
    // ============================
    public function atualizarTelefone(int $id): void
    {
        try {

            $dados = $this->getBody();

            $this->service->atualizarTelefone(
                $dados['telefone'],
                $id
            );

            $this->response([
                "success" => true,
                "message" => "Telefone atualizado."
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }

    // ============================
    // Ativar usuário
    // ============================
    public function ativarUsuario(User $usuarioLogado, int $id): void
    {
        try {

            $this->service->ativarUsuario(
                $usuarioLogado,
                $id
            );

            $this->response([
                "success" => true,
                "message" => "Usuário ativado."
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }

    // ============================
    // Alterar nível
    // ============================
    public function alterarNivel(User $usuarioLogado, int $id): void
    {
        try {

            $dados = $this->getBody();

            $this->service->alterarNivel(
                $usuarioLogado,
                $id,
                $dados['nivel']
            );

            $this->response([
                "success" => true,
                "message" => "Nível alterado com sucesso."
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }
}   