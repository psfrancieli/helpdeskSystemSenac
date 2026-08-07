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
    o success vai servir justamente para verificações no react

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
    public function encontrarPorId(User $usuarioLogado, int $id):void
    {
        try {

            $usuario = $this->service->encontrarPorId($usuarioLogado, $id);

            $user = [
                "id" => $usuario->getId(),
                "nome" => $usuario->getNome(),
                "email" => $usuario->getEmail(),
                "telefone" => $usuario->getTelefone(),
                "cpf" => $usuario->getCpf()
            ];


            $this->response([
                "success" => true,
                "data" => $user
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
            $resultado = [];
            foreach($usuarios as $usuario){
               $resultado[] = [
                "id" => $usuario->getId(),
                "nome" => $usuario->getNome(),
                "email" => $usuario->getEmail(),
                "telefone" => $usuario->getTelefone(),
                "cpf" => $usuario->getCpf()
            ];
            }

            $this->response([
                "success" => true,
                "data" => $resultado
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }


    public function encontrarPorCpf(User $usuarioLogado): void
    {
        try {

            $dados = $this->getBody();

            $usuario = $this->service->encontrarPorCpf(
                $usuarioLogado,
                $dados['cpf']
            );

            $user = [
                "id" => $usuario->getId(),
                "nome" => $usuario->getNome(),
                "email" => $usuario->getEmail(),
                "telefone" => $usuario->getTelefone(),
                "cpf" => $usuario->getCpf(),
                "nivel" => $usuario->getNivel()
            ];

            $this->response([
                "success" => true,
                "data" => $user
        
            ]);

        } catch (Throwable $e) {

            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);

        }
    }


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


    public function atualizarTelefone(User $usuarioLogado): void
    {
        try {

            $dados = $this->getBody();

            $this->service->atualizarTelefone(
                $usuarioLogado,
                $dados['telefone']
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
    public function login():void{
        try{
            $dados = $this->getBody();
            if (!isset($dados['email'], $dados['senha'])){
                $this->response([
                    "success" => false,
                    "message" => "Todos os campos devem ser preenchidos."
                ], 400);
            }
            $usuario = $this->service->login($dados['email'], $dados['senha']);
 
            $user = [
                "id" => $usuario->getId(),
                "nome" => $usuario->getNome(),
                "email" => $usuario->getEmail(),
                "telefone" => $usuario->getTelefone(),
                "cpf" => $usuario->getCpf(),
                "senha" => $usuario->getSenha(),
                "nivel" => $usuario->getNivel()
            ];
            $this->response([
                    "success" => true,
                    "data" => criarToken($user),
                    "nivel" => $usuario->getNivel()
                ]);
        }
        catch (Throwable $e) {
            $this->response([
                "success" => false,
                "message" => $e->getMessage()
            ], 400);
        }
    }
      
}
//
// {
//     "cpf":"12345678900"
// }
//
// ======================================
// $controller->encontrarPorCpf($usuarioLogado);


// ======================================
// Cadastrar usuário
//
// Corpo esperado:
//
// {
//     "nome":"João",
//     "cpf":"12345678900",
//     "email":"joao@email.com",
//     "telefone":"15999999999",
//     "senha":"123456",
//     "nivel":"USER"
// }
//
// ======================================
// $dados = [
//     "id" => null,
//     "uuid" => null,
//     'nome' => "Caiquinho",
//     "cpf" => "036.840.960-08",
//     "telefone" => "12998713679",
//     "email" => "caique@gmail.com",
//     "senha" => "Sapo12345@"

// ];
// $controller->cadastrarUsuario($usuarioLogado);


// ======================================
// Deletar usuário
// ======================================
// $controller->deletarUsuario($usuarioLogado, 10);


// ======================================
// Resetar senha
//
//
// ======================================
    // $controller->resetarSenha($usuarioLogado, 1 , 'Caique@123456');


// ======================================
// Atualizar telefone
//

// ======================================
// $controller->atualizarTelefone($usuarioLogado, '13998738777');


// ======================================
// Ativar usuário
// // ======================================
// $controller->ativarUsuario($usuarioLogado, 4);


// ======================================
// Alterar nível

// ======================================
// $controller->alterarNivel($usuarioLogado, 3 , 'tecnico');