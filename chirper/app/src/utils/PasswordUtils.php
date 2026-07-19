<?php
class PasswordUtils{
    public static function hash(string $senha){
        return password_hash($senha, PASSWORD_DEFAULT);
    }
    public static function verificar(string $senha , string $hash){
        return password_verify($senha , $hash);
    }
    public static function validar(string $senha): bool
{
    return preg_match(
        '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=\[\]{};:\'"\\\\|,.<>\/?`~]).{8,64}$/',
        $senha
    ) === 1;
}
}