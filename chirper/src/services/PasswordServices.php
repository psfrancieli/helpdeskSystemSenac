<?php 
class PasswordServices {
    public static function hash(string $senha){
        return password_hash($senha, PASSWORD_DEFAULT);
    }
    public static function verificar(string $senha , string $hash){
        return password_verify($senha , $hash);
    }
}

?>