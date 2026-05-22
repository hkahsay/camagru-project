<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

$config = require __DIR__ . '/database.php';
$database = str_replace('`', '``', $config['database']);

$pdo = Database::serverConnection();
$pdo->exec(
    "CREATE DATABASE IF NOT EXISTS `{$database}`
    CHARACTER SET {$config['charset']}
    COLLATE {$config['charset']}_unicode_ci"
);

$schema = file_get_contents(__DIR__ . '/schema.sql');

if ($schema === false) {
    throw new RuntimeException('Could not read database schema.');
}

Database::connection()->exec($schema);

echo "Database setup completed.\n";
