<?php 
require_once __DIR__ . "/../configs/Database.php";
require_once __DIR__ . "/../models/User.php";
class UserRepository{
    public function EncontrarPorId(int $id): ?User{
        try{
            $sql = 'SELECT * FROM "USUARIO" WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$id]);
            $dados = $stmt->fetch(PDO::FETCH_ASSOC);
            return new User($dados['id'], $dados['uuid'] , $dados['nome']
             , $dados['CPF'] , $dados['telefone'] , $dados['email'],
             $dados['senha'], $dados['nivel'] , $dados['ativo']);
        }catch(PDOException $e){
            throw new RuntimeException("Erro ao buscar usuário no banco",0 , $e);
        }
    }
    public function EncontrarTodosUsuarios():array{
        try{
            $sql = 'SELECT * FROM "USUARIO" WHERE ativo = 1';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute();
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $usuarios = [];
            foreach ($dados as $usuario) {
            $usuarios[] = new User(    
                $usuario['id'],
                $usuario['uuid'],           
                $usuario['nome'],
                $usuario['CPF'],
                $usuario['telefone'],
                $usuario['email'],
                $usuario['senha'],
                $usuario['nivel'],
                $usuario['ativo']
            );
            }
            return $usuarios;


        }catch(PDOException $e){
            throw new RuntimeException("Erro ao buscar usuário no banco",0 , $e);
        }
    }
    public function criarUsuario(User $usuario):bool{
        try{
            $sql = 'INSERT INTO "USUARIO" (nome, "CPF" , telefone , email , senha , nivel , ativo) VALUES ( ? , ? , ?  , ? , ? , ? , ?)';
            $stmt = Database::getConnection()->prepare($sql);
            return $stmt->execute([$usuario->getNome() ,
            $usuario->getCpf() , $usuario->getTelefone() , $usuario->getEmail() , $usuario->getSenha() , $usuario->getNivel() , $usuario->getAtivo()]);
        }catch(PDOException $e){
            throw new RuntimeException("Erro ao criar usuario ",0 , $e);
        }
       
    }
    public function deletarUsuario(int $id): bool{
        try {
            $sql = 'UPDATE "USUARIO" SET ativo = FALSE WHERE id = ? AND ativo = 1';
            $stmt = Database::getConnection()->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e){
            throw new RuntimeException("Erro ao tentar deletar usuario " , 0 , $e);
        }
    }

    public function encontrarPorEmail(string $email): ?User{
        try {
            $sql = 'SELECT * FROM "USUARIO" WHERE email = ? AND ativo = 1';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return new User($user['id'] , $user['uuid'] , $user['nome'] , $user['CPF'] , 
            $user['telefone'] , $user['email'] , $user['senha'] , $user['nivel'] , $user['ativo']);

        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao buscar usuario " , 0 , $e);
        }
    }
    public function encontrarPorCpf(string $cpf): ?User{
        try {
            $sql = 'SELECT * FROM "USUARIO" WHERE "CPF" = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$cpf]);
            $user  = $stmt->fetch(PDO::FETCH_ASSOC);
            return new User($user['id'] , $user['uuid'] , $user['nome'] , $user['CPF'] , 
            $user['telefone'] , $user['email'] , $user['senha'] , $user['nivel'] , $user['ativo']);
        } catch (PDOException $e) {
              throw new RuntimeException("Erro ao buscar usuario " , 0 , $e);
        }
    }
    public function atualizarUsuario(User $user): bool{
        try {
            $sql = 'UPDATE "USUARIO" SET  nome = ? , telefone = ? , email = ? , senha = ? WHERE id = ? AND ativo = 1';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$user->getNome() , $user->getTelefone() , $user->getEmail() , $user->getSenha() , $user->getId()]);
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            throw new RuntimeException("Não foi possivel atualizar os dados do usuario " , 0 , $e);
        }
    }
    public function alterarNivelUsuario(int $id ,string $nivel = 'usuario'): bool{
        try {
            $sql = 'UPDATE "USUARIO" SET nivel = ? WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$nivel , $id]);
            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao atualizar usuario " , 0 , $e);
        }
    }
    public function alterarSenha(string $senha , int $id):bool{
        try {
            
            $sql = 'UPDATE "USUARIO" SET senha = ? WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$senha , $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao atualizar o usuario " , 0 ,$e);
        }
    }
    public function ativarUsuario(int $id): bool{
        try{
            $sql = 'UPDATE "USUARIO" SET ativo = TRUE WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException("Erro ao ativar usuario " , 0 , $e);
        }
    }

}
/* 
Testes da classe 
 */
$usuario = new UserRepository();
// echo $usuario->ativarUsuario(2);
$user = $usuario->encontrarPorCpf('222.222.222-22');
$user->setEmail('carlinhos13@empresa.com');
// $user->setNome('Carlos Campos');
// echo $usuario->atualizarUsuario($user);
echo $user->getNome() . "<br>" . $user->getEmail() . "<br>" . $user->getTelefone();

// $user = new User('JoaozinhoGsss' , '111.222.444-21' , '(15)99222-8890' , 'joaozinho11112@email.com' , '123456');
// echo $usuario->criarUsuario($user);

// $user = new UserRepository();
// $usuario = $user->EncontrarTodosUsuarios();
// foreach($usuario as $user){
//     echo $user->getEmail() . "<br>";
// }
// echo $usuario->getId();  
// echo "<br>";

// echo $usuario->getNome();
// echo "<br>";

// echo $usuario->getEmail();
// echo "<br>";

// echo $usuario->getNivel();
?>