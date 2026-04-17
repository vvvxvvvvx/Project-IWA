<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use RuntimeException;

final class PdoConnectionFactory
{
    private static ?PDO $connection = null;

    public static function make(array $config): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $db = $config['db'] ?? [];
        $driver = (string) ($db['driver'] ?? 'mysql');

        if ($driver !== 'mysql') {
            throw new RuntimeException('Deze configuratie verwacht een MySQL PDO-driver. Zet DB_DRIVER=mysql en controleer je MySQL-instellingen.');
        }

        $host = (string) ($db['host'] ?? '127.0.0.1');
        $port = (int) ($db['port'] ?? 3306);
        $database = (string) ($db['database'] ?? 'iwa_dashboard');
        $username = (string) ($db['username'] ?? 'root');
        $password = (string) ($db['password'] ?? '');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);

        try {
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\Throwable $throwable) {
            throw new RuntimeException('MySQL-verbinding mislukt: ' . $throwable->getMessage(), previous: $throwable);
        }

        return self::$connection;
    }
}
