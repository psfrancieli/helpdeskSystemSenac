<?php 
require_once __DIR__ . '/../utils/PasswordUtils.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../utils/CpfUtils.php';
require_once __DIR__ . '/../utils/EmailUtils.php';
require_once __DIR__ . '/../utils/PhoneUtils.php';
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
        if(!CpfUtils::validar($cpf)){
            throw new InvalidArgumentException("CPF inválido");
        }
        $cpfFormatado = CpfUtils::formatar($cpf);
        $user = new UserRepository();
        return $user->encontrarPorCpf($cpfFormatado);

    }

    public function cadastrarUsuario(User $usuarioLogado,array $dados):bool{
        $userRepository = new UserRepository(); 
        if($usuarioLogado->getNivel() !== 'analista'){
            throw new Exception("Acesso negado.");
        }
        if($userRepository->encontrarPorEmail($dados['email'])){
            throw new Exception("Esse email ja existe!");
        }
        if(!EmailUtils::validar($dados['email'])){
            throw new InvalidArgumentException("Email inválido");
        }
        if(!CpfUtils::validar($dados['cpf'])){
            throw new InvalidArgumentException("CPF inválido");
        }
        if(!PasswordUtils::validar($dados['senha'])){
            throw new InvalidArgumentException("Senha inválida");
        }
        if(!PhoneUtils::validar($dados['telefone'])){
            throw new InvalidArgumentException("Telefone inválido");
        }
        $dados['telefone'] = PhoneUtils::formatar($dados['telefone']);
        $dados['email'] = EmailUtils::normalizar($dados['email']);
        $dados['cpf'] = CpfUtils::formatar($dados['cpf']);
        $dados['senha'] = PasswordUtils::hash($dados['senha']);
        $newUser = new User($dados['id'] , $dados['uuid'],$dados['nome'] , $dados['cpf'] , $dados['telefone'] , $dados['email'] , $dados['senha']);
        return $userRepository->criarUsuario($newUser);
    }

    public function deletarUsuario(User $usuarioLogado , int $id):bool{
        $userRepository = new UserRepository();
        if($usuarioLogado !== 'adm'){
            throw new Exception("Acesso negado.");
        }
        if(!$userRepository->encontrarPorId($id)){
            throw new Exception("Erro ao deletar usuario!");
        }
        return $userRepository->deletarUsuario($id);

    }
    public function alterarSenha(User $usuarioLogado , string $senha):bool{
        $userRepository = new UserRepository();
        if($usuarioLogado->getNivel() !== 'analista'){
            throw new Exception("Acesso negado.");
        }
        if(!PasswordUtils::validar($senha)){
            throw new InvalidArgumentException("Senha inválida");
        }
        $novaSenha = PasswordUtils::hash($senha);
        return $userRepository->atualizarSenha($novaSenha);

    }
    public function alterarTelefone(User $usuarioLogado , string $telefone):bool{
        $userRepository = new UserRepository();
        if($usuarioLogado->getNivel() !== 'usuario'){
            throw new Exception("Acesso negado.");
        }
        if(!PhoneUtils::validar($telefone)){
            throw new InvalidArgumentException("Telefone inválido");
        }
        $novoTelefone = PhoneUtils::formatar($telefone);
        return $userRepository->atualizarTelefone($novoTelefone);
        

    }
    public function ativarUsuario(User $usuarioLogado , int $id){
        $userRepository = new UserRepository();
        $user = $userRepository->encontrarPorId($id);
        if($usuarioLogado->getNivel() !== 'adm'){
            throw new Exception("Acesso negado.");
        }
        if($user['ativo']){
            throw new Exception("O usuario ja esta ativo no sistema! ");
        }
        return $userRepository->ativarUsuario($id);
    }
    


}

?>