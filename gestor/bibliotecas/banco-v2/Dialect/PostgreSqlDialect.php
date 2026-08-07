<?php

declare(strict_types=1);

namespace C2F\Database\Dialect;

use PDO;
use PDOException;

final class PostgreSqlDialect extends AbstractDatabaseDialect
{
    public function driverName(): string
    {
        return 'pgsql';
    }

    public function defaultPort(): int
    {
        return 5432;
    }

    public function defaultCharset(): string
    {
        return 'UTF8';
    }

    public function buildDsn(
        string $host,
        int $port,
        string $database,
        string $charset,
        array $options = [],
    ): string {
        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";

        $sslMode = trim((string) ($options['sslmode'] ?? 'prefer'));
        if ($sslMode !== '') {
            $allowedSslModes = ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'];
            if (!in_array($sslMode, $allowedSslModes, true)) {
                throw new \InvalidArgumentException("Invalid PostgreSQL sslmode: {$sslMode}");
            }
            $dsn .= ";sslmode={$sslMode}";
        }

        $applicationName = trim((string) ($options['application_name'] ?? 'conn2flow'));
        if ($applicationName !== '') {
            if (!preg_match('/^[A-Za-z0-9_.-]{1,63}$/D', $applicationName)) {
                throw new \InvalidArgumentException('Invalid PostgreSQL application_name.');
            }
            $dsn .= ";application_name={$applicationName}";
        }

        return $dsn;
    }

    public function configureConnection(PDO $connection, string $charset, string $schema): void
    {
        $connection->exec('SET client_encoding TO ' . $connection->quote($charset));
        $connection->exec('SET search_path TO ' . $this->quoteIdentifier($schema));
        $connection->exec("SET TIME ZONE 'UTC'");
    }

    public function listTablesQuery(string $schema): array
    {
        return [
            'sql' => "SELECT tablename
                FROM pg_catalog.pg_tables
                WHERE schemaname = ?
                ORDER BY tablename",
            'params' => [$schema],
        ];
    }

    public function columnsQuery(string $table, string $schema): array
    {
        return [
            'sql' => <<<'SQL'
                SELECT
                    c.column_name AS "Field",
                    c.data_type AS "Type",
                    c.is_nullable AS "Null",
                    CASE WHEN EXISTS (
                        SELECT 1
                        FROM information_schema.table_constraints tc
                        JOIN information_schema.key_column_usage kcu
                          ON tc.constraint_name = kcu.constraint_name
                         AND tc.constraint_schema = kcu.constraint_schema
                        WHERE tc.constraint_type = 'PRIMARY KEY'
                          AND tc.table_schema = c.table_schema
                          AND tc.table_name = c.table_name
                          AND kcu.column_name = c.column_name
                    ) THEN 'PRI' ELSE '' END AS "Key",
                    c.column_default AS "Default",
                    CASE WHEN c.is_identity = 'YES' THEN 'auto_increment' ELSE '' END AS "Extra"
                FROM information_schema.columns c
                WHERE c.table_schema = ? AND c.table_name = ?
                ORDER BY c.ordinal_position
                SQL,
            'params' => [$schema, $table],
        ];
    }

    public function buildUpsert(
        string $quotedTable,
        array $quotedColumns,
        array $valueExpressions,
        array $quotedConflictColumns,
        array $quotedUpdateColumns,
    ): string {
        if ($quotedConflictColumns === []) {
            throw new \InvalidArgumentException('PostgreSQL upsert requires at least one conflict column.');
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (%s)',
            $quotedTable,
            implode(', ', $quotedColumns),
            implode(', ', $valueExpressions),
            implode(', ', $quotedConflictColumns),
        );

        if ($quotedUpdateColumns === []) {
            return $sql . ' DO NOTHING';
        }

        $updates = array_map(
            static fn (string $column): string => "{$column} = EXCLUDED.{$column}",
            $quotedUpdateColumns,
        );

        return $sql . ' DO UPDATE SET ' . implode(', ', $updates);
    }

    public function lastInsertedId(PDO $connection, ?string $sequence = null): int|string
    {
        if ($sequence !== null && $sequence !== '') {
            return $connection->lastInsertId($sequence) ?: 0;
        }

        try {
            $statement = $connection->query('SELECT lastval()');
            return (int) $statement->fetchColumn();
        } catch (PDOException) {
            return 0;
        }
    }

    protected function quotePart(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    protected function isAlreadyQuoted(string $identifier): bool
    {
        return str_starts_with($identifier, '"') && str_ends_with($identifier, '"');
    }
}
