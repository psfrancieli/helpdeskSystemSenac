<?php 
require_once __DIR__ . "/../configs/Database.php";
require_once __DIR__ . "/../Models/User.php";
class UserRepository{
    public function EncontrarPorId(int $id): ?User{
        try{
            $sql = 'SELECT * FROM "USUARIO" WHERE id = ?';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$id]);
            $dados = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dados) {
                return null;
            }

            return new User($dados['id'], $dados['uuid'] , $dados['nome']
             , $dados['CPF'] , $dados['telefone'] , $dados['email'],
             $dados['senha'], $dados['nivel'] , (bool) $dados['ativo']);
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
                (bool) $usuario['ativo']
            );
            }
            return $usuarios;


        }catch(PDOException $e){
            throw new RuntimeException("Erro ao buscar usuário no banco",0 , $e);
        }
    }
    public function criarUsuario(User $usuario):array{
        try{
            $sql = 'INSERT INTO "USUARIO" (nome, "CPF" , telefone , email , senha , nivel , ativo) VALUES ( ? , ? , ?  , ? , ? , ? , ?) RETURNING id, nome, email, nivel, ativo';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([
                $usuario->getNome(),
                $usuario->getCpf(),
                $usuario->getTelefone(),
                $usuario->getEmail(),
                $usuario->getSenha(),
                $usuario->getNivel(),
                $usuario->getAtivo(),
            ]);

            $created = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$created) {
                throw new RuntimeException('Erro ao recuperar usuario criado');
            }

            return $created;
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

            if (!$user) {
                return null;
            }

            return new User($user['id'] , $user['uuid'] , $user['nome'] , $user['CPF'] ,
            $user['telefone'] , $user['email'] , $user['senha'] , $user['nivel'] , (bool) $user['ativo']);

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

            if (!$user) {
                return null;
            }

            return new User($user['id'] , $user['uuid'] , $user['nome'] , $user['CPF'] ,
            $user['telefone'] , $user['email'] , $user['senha'] , $user['nivel'] , (bool) $user['ativo']);
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

    // Alias de compatibilidade com o controller/app atual.
    public function listarUsuarioPorId(int $id): User
    {
        $user = $this->EncontrarPorId($id);

        if ($user === null) {
            throw new RuntimeException('Usuario nao encontrado');
        }

        return $user;
    }
}
?>