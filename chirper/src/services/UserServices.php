<?php 
require_once __DIR__ . '/../services/PasswordServices.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
class UserServices{
    public function encontrarPorId(User $usuarioLogado , int $id): ?User{
        if($usuarioLogado->getNivel() !== 'adm' && $usuarioLogado->getNivel() !== 'analista'){
            throw new Exception("Acesso negado.");
        }
        $user = new UserRepository();
        return $user->encontrarPorId($id);
    }

    public function encontrarTodosUsuarios(User $usuarioLogado): array{
        if($usuarioLogado->getNivel() !== 'adm' && $usuarioLogado->getNivel() !== 'analista'){
            throw new Exception("Acesso negado.");
        }
        $user = new UserRepository();
        return $user->encontrarTodosUsuarios();
    }

    public function encontrarPorCpf(User $usuarioLogado , string $cpf): ?User{
        if($usuarioLogado->getNivel() !== 'adm' && $usuarioLogado->getNivel() !== 'analista'){
            throw new Exception("Acesso negado.");
        }
        $user = new UserRepository();
        return $user->encontrarPorCpf($cpf);

    }

    public function cadastrarUsuario(User $usuarioLogado , array $dados):bool{
        $userRepository = new UserRepository();
        if($usuarioLogado !== 'adm'){
            throw new Exception("Acesso negado.");
        }
        if($userRepository->encontrarPorEmail($dados['email'])){
            throw new Exception("Esse email ja existe!");
        }
        $dados['senha'] = PasswordServices::hash($dados['senha']);
        $newUser = new User($dados['id'] , $dados['uuid'],$dados['nome'] , $dados['cpf'] , $dados['telefone'] , $dados['email'] , $dados['senha']);
        return $userRepository->criarUsuario($newUser);
    }

    public function deletarUsuario(User $usuarioLogado , int $id):bool{
        $userRepository = new UserRepository();
        if($usuarioLogado !== 'adm'){
            throw new Exception("Acesso negado.");
        }
        if(!$userRepository->encontrarPorId($id)){
            throw new Exception("Não é possivel deletar o usuario! ");
        }
        return $userRepository->deletarUsuario($id);

    }

}

?>