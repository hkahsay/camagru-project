<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require BASE_PATH . '/config/database.php';

        self::$connection = new PDO(
            self::dsn($config),
            $config['user'],
            $config['password'],
            $config['options']
        );
        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return self::$connection;
    }

    public static function serverConnection(): PDO
    {
        $config = require BASE_PATH . '/config/database.php';

        return new PDO(
            self::dsn($config, false),
            $config['user'],
            $config['password'],
            $config['options']
        );
    }

    private static function dsn(array $config, bool $withDatabase = true): string
    {
        $database = $withDatabase ? ';dbname=' . $config['database'] : '';

        return sprintf(
            'mysql:host=%s;port=%d%s;charset=%s',
            $config['host'],
            $config['port'],
            $database,
            $config['charset']
        );
    }
}
