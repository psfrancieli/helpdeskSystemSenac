<?php 
require_once  __DIR__ . "/User.php";

class Admin extends User{
    public function __construct(int $id, string $uuid, string $nome, string $cpf, string $telefone, string $email, string $senha, string $nivel = 'adm', bool $ativo = true)
    {
        parent::__construct($id, $uuid, $nome, $cpf, $telefone, $email, $senha, $nivel, $ativo);
    }

}


?>