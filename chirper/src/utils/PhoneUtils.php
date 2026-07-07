<?php



class PhoneUtils
{
    /**
     * Remove tudo que não for número
     */
    public static function normalizar(string $telefone): string
    {
        return preg_replace('/\D/', '', $telefone);
    }

    /**
     * Valida apenas celular brasileiro (11 dígitos e começa com 9)
     */
    public static function validar(string $telefone): bool
    {
        $telefone = self::normalizar($telefone);

        if (strlen($telefone) !== 11) {
            return false;
        }

        // depois do DDD, o primeiro dígito deve ser 9
        if ($telefone[2] !== '9') {
            return false;
        }

        return true;
    }

    /**
     * Formata para (DD) 99999-9999
     */
    public static function formatar(string $telefone): string
    {
        $telefone = self::normalizar($telefone);

        if (!self::validar($telefone)) {
            throw new InvalidArgumentException("Telefone celular inválido");
        }

        return preg_replace(
            '/(\d{2})(\d{5})(\d{4})/',
            '($1) $2-$3',
            $telefone
        );
    }
}
?>