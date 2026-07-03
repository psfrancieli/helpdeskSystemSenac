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

            $env = parse_ini_file(__DIR__ . '/../../../.env');

            $host = "ep-green-night-acel3qx9-pooler.sa-east-1.aws.neon.tech";
            $dbname = "neondb";
            $port = 5432;

            $user = $env['PGUSER'];
            $password = $env['PGPASSWORD'];

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require;options='endpoint=ep-green-night-acel3qx9-pooler'";

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