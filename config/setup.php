<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

$schema = file_get_contents(__DIR__ . '/schema.sql');

if ($schema === false) {
    throw new RuntimeException('Could not read database schema.');
}

Database::connection()->exec($schema);

echo "Database setup completed.\n";
