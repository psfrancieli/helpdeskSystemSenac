<?php 
require_once  __DIR__ . "/User.php";

class Technician extends User{
    public function __construct(?int $id = null, ?string $uuid = null, string $nome = '', string $cpf = '', string $telefone = '', string $email = '', string $senha = '', string $nivel = 'usuario', bool $ativo = true)
    {
        parent::__construct($id, $uuid, $nome, $cpf, $telefone, $email, $senha, $nivel, $ativo);
    }
}
?>