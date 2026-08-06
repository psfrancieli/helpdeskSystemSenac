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
        $usuario = $user->encontrarPorId($id);
        if(!$usuario){
            throw new Exception('Usuario não encontrador');
        }
        $usuario->alterarSenha("");
        $usuario->setCpf("");
        return $usuario;
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
        $usuario =  $user->encontrarPorCpf($cpfFormatado);
        if(!$usuario){
            throw new Exception('Usuario não encontrador');
        }
        $usuario->alterarSenha("");
        $usuario->setCpf("");
        return $usuario;

    }

    public function cadastrarUsuario(User $usuarioLogado,array $dados):bool{
        $userRepository = new UserRepository(); 
        if($usuarioLogado->getNivel() !== 'analista' &&  $usuarioLogado->getNivel() !== 'adm'){
            throw new Exception("Acesso negado.");
        }

        if (!isset($dados['nivel'])) {
            $dados['nivel'] = 'usuario';
        }

        $niveisPermitidos = ['usuario', 'tecnico', 'analista', 'adm'];
        if (!in_array($dados['nivel'], $niveisPermitidos, true)) {
            throw new InvalidArgumentException("Cargo inválido");
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
        $newUser = new User($dados['id'] , $dados['uuid'],$dados['nome'] , $dados['cpf'] , $dados['telefone'] , $dados['email'] , $dados['senha'], $dados['nivel'], true);
        return $userRepository->criarUsuario($newUser);
    }

    public function deletarUsuario(User $usuarioLogado , int $id):bool{
        $userRepository = new UserRepository();
        $usuario = $userRepository->encontrarPorId($id);
        if($usuarioLogado->getNivel() !== 'adm'){
            throw new Exception("Acesso negado.");
        }
        if(!$usuario){
            throw new Exception("Usuario não encontrado!");
        }
        if($usuario->getNivel() === 'adm'){
            throw new DomainException('Acesso negado!');
        }
        return $userRepository->deletarUsuario($id);

    }
    public function resetarSenha(User $usuarioLogado, string $senha, int $id): bool
    {
    $userRepository = new UserRepository();
    $usuario = $userRepository->encontrarPorId($id);
    if($usuarioLogado->getId() === $id){
        throw new Exception("Não pode alterar sua propria senha!");
    }
    if (!$usuario) {
        throw new Exception("Usuário não encontrado.");
    }

    if ($usuario->getNivel() === 'adm') {
        if ($usuarioLogado->getNivel() !== 'adm') {
            throw new DomainException("Permissão negada.");
        }
    } elseif ($usuarioLogado->getNivel() !== 'analista') {
        throw new DomainException("Acesso negado.");
    }

    if (!PasswordUtils::validar($senha)) {
        throw new InvalidArgumentException("Senha inválida.");
    }
    $novaSenha = PasswordUtils::hash($senha);

    return $userRepository->alterarSenha($novaSenha, $id);
    }
   
    public function atualizarTelefone(User $usuarioLogado, string $telefone):bool{
        $userRepository = new UserRepository();
        if(!PhoneUtils::validar($telefone)){
            throw new InvalidArgumentException("Telefone inválido");
        }
        $novoTelefone = PhoneUtils::formatar($telefone);
        return $userRepository->atualizarTelefone($novoTelefone , $usuarioLogado->getId());
        

    }
    public function ativarUsuario(User $usuarioLogado , int $id):bool{
        $userRepository = new UserRepository();
        $user = $userRepository->encontrarPorId($id);
        if(!$user){
            throw new Exception("Usuario não encontrado!");
        }
        if($usuarioLogado->getNivel() !== 'adm'){
            throw new Exception("Acesso negado.");  
        }
        if($user->getAtivo()){
            throw new Exception("O usuario ja esta ativo no sistema! ");
        }
        return $userRepository->ativarUsuario($id);
    }

    public function alterarNivel(User $usuarioLogado, int $id, string $nivel):bool
    {
    $userRepository = new UserRepository();
    //Verficação padrão do nivel do usuario
    if ($usuarioLogado->getNivel() !== 'adm' &&
    $usuarioLogado->getNivel() !== 'analista') {
        throw new Exception("Acesso negado.");
    }

    $usuario = $userRepository->encontrarPorId($id);
    //Se não encontrar o id do usuario, ele não existe.
    if (!$usuario) {
        throw new Exception("Usuário não encontrado.");
    }
    //Array de niveis permitidos para passar como parametro
    $niveisPermitidos = ['usuario','tecnico','analista', 'adm'];

    //Verifica se o nivel existe dentro do array niveisPermitidos , caso nao exista e um nivel invalido
    if (!in_array($nivel, $niveisPermitidos, true)) {
        throw new DomainException("Nível inválido.");
    }

    //Verifica se o usuario possui esse nivel
    if ($usuario->getNivel() === $nivel) {
        throw new DomainException("O usuário já possui esse nível.");
    }
    //Somente ADM altera o nivel de um usuario para ADM
    if ($nivel === 'adm' && $usuarioLogado->getNivel() !== 'adm') {
        throw new DomainException("Permissão negada.");
    }
    
    return $userRepository->alterarNivelUsuario($id , $nivel);
}
public function encontrarPorEmail(string $email): ?User{
    $userRepository = new UserRepository();
    $emailNormalizado = EmailUtils::normalizar($email);
    if(!EmailUtils::validar($emailNormalizado)){
        throw new InvalidArgumentException("Email inválido.");
    }
    $usuario =  $userRepository->encontrarPorEmail($emailNormalizado);
    return $usuario;


}



}
