<?php

declare(strict_types=1);

namespace C2F\Database\Dialect;

use InvalidArgumentException;

final class DialectFactory
{
    public static function create(string $driver): DatabaseDialect
    {
        return match (strtolower(trim($driver))) {
            'mysqli', 'mysql', 'pdo_mysql' => new MySqlDialect(),
            'pgsql', 'pdo_pgsql', 'postgres', 'postgresql' => new PostgreSqlDialect(),
            default => throw new InvalidArgumentException("Unsupported database driver: {$driver}"),
        };
    }
}
