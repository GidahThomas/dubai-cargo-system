<?php

class Database
{
    private static ?PDO $connection = null;

    public static function connect(): PDO
    {
        if (self::$connection === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $name = $_ENV['DB_NAME'] ?? 'dubai_computer_fast_cargo';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';
            $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $name, $charset);

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // Cloud databases (TiDB Cloud, Aiven, PlanetScale) require an encrypted connection:
            // set DB_SSL_CA to the server's CA bundle, e.g. /etc/pki/tls/certs/ca-bundle.crt on Vercel.
            $sslCa = trim((string) ($_ENV['DB_SSL_CA'] ?? ''));
            if ($sslCa !== '') {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            }

            $port = trim((string) ($_ENV['DB_PORT'] ?? ''));
            if ($port !== '') {
                $dsn .= ';port=' . (int) $port;
            }

            self::$connection = new PDO($dsn, $user, $pass, $options);
        }

        return self::$connection;
    }
}
