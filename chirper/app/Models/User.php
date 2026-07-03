<?php 
class User{
    protected int $id;
    protected string $uuid;
    protected string $nome;
    protected string $cpf;
    protected string $telefone;
    protected string $email;
    protected string $senha;
    protected string $nivel;
    protected bool $ativo;

    public function __construct(int $id , string $uuid , string $nome ,string $cpf , string $telefone , 
    string $email , string $senha, string $nivel = 'usuario', bool $ativo = true)
    {
        if (empty($nome)) {
        throw new InvalidArgumentException('Nome inválido');
        }

        if (empty($telefone)) {
        throw new InvalidArgumentException('Telefone inválido');
        }

        if (empty($email)) {
            throw new InvalidArgumentException('Email inválido');
        }

        $this->id = $id;
        $this->uuid = $uuid;
        $this->nome = $nome;
        $this->cpf = $cpf;
        $this->telefone = $telefone;
        $this->email = $email;
        $this->senha = $senha;
        $this->nivel = $nivel;
        $this->ativo = $ativo;

    }

    public function getId():int{
        return $this->id;
    }
    public function getUuid():string{
        return $this->uuid;
    }
    public function getNome():string{
        return $this->nome;
    }
    public function getCpf():string{
        return $this->cpf;
    }
    public function getTelefone():string{
        return $this->telefone;
    }
    public function getEmail():string{
        return $this->email;
    }
    public function getNivel():string{
        return $this->nivel;
    }
    public function getAtivo():bool{
        return $this->ativo;
    }
    public function setNome(string $nome):void{
        if (empty($nome)) {
        throw new InvalidArgumentException('Nome inválido');
        }
        $this->nome = $nome;
    }
    public function setTelefone(string $telefone):void{
        if (empty($telefone)) {
        throw new InvalidArgumentException('Telefone inválido');
        }
        $this->telefone = $telefone;
    }
    public function setEmail(string $email):void{
        if (empty($email)) {
        throw new InvalidArgumentException('Email inválido');
        }
        $this->email = $email;
    }
    public function alterarSenha(string $senha):void{
        $this->senha = $senha;
    }
}

?>