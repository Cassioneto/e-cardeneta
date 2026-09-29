<?php
namespace Config;

class Database {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            $host = '127.0.0.1';
            $db   = 'ecardeneta';
            $user = 'root'; // Ajustar conforme o ambiente do usuário
            $pass = '';     // Ajustar conforme o ambiente do usuário
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION, // OWASP: Log secure errors carefully, turn off display in prod
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false, // OWASP A03:2021-Injection - Strict prepared statements
            ];

            try {
                self::$connection = new \PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                // Em produção, isso deve ser registrado em um log seguro de erros para não expor dados internos
                error_log("Database connection error: " . $e->getMessage());
                die("Erro de conexão com o banco de dados.");
            }
        }
        return self::$connection;
    }
}
