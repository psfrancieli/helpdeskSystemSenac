    <?php 
class EmailUtils
{
    public static function normalizar(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function validar(string $email): bool
    {
        $email = self::normalizar($email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
    ?>