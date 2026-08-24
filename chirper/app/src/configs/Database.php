<?php

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        try {

            // Local dev fallback: php -S doesn't read .env, so load it manually
            // when the PG* vars aren't already present in the OS environment.
            if (getenv('PGUSER') === false || getenv('PGPASSWORD') === false) {
                $envPath = __DIR__ . '/../../../../.env';
                if (is_file($envPath)) {
                    $envVars = parse_ini_file($envPath);
                    if (is_array($envVars)) {
                        foreach ($envVars as $key => $value) {
                            if (getenv($key) === false) {
                                putenv("$key=$value");
                            }
                        }
                    }
                }
            }

            $host = "ep-green-night-acel3qx9-pooler.sa-east-1.aws.neon.tech";
            $dbname = "neondb";
            $port = 5432;

            $user = getenv('PGUSER');
            $password = getenv('PGPASSWORD');
 
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require;options=endpoint=ep-green-night-acel3qx9-pooler";
 
            self::$pdo = new PDO(
                $dsn,
                $user,
                $password
            );

            self::$pdo->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            self::$pdo->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

            return self::$pdo;

        } catch (PDOException $e) {

            throw new Exception(
                "Erro ao conectar com banco: " . $e->getMessage()
            );
        }
    }

}
?>