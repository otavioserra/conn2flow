<?php

declare(strict_types=1);

namespace C2F\Database\Dialect;

use PDO;

final class MySqlDialect extends AbstractDatabaseDialect
{
    public function driverName(): string
    {
        return 'mysql';
    }

    public function defaultPort(): int
    {
        return 3306;
    }

    public function defaultCharset(): string
    {
        return 'utf8mb4';
    }

    public function buildDsn(
        string $host,
        int $port,
        string $database,
        string $charset,
        array $options = [],
    ): string {
        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    public function configureConnection(PDO $connection, string $charset, string $schema): void
    {
        $connection->exec('SET NAMES ' . $connection->quote($charset));
    }

    public function listTablesQuery(string $schema): array
    {
        return [
            'sql' => "SELECT TABLE_NAME
                FROM information_schema.tables
                WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
                ORDER BY TABLE_NAME",
            'params' => [],
        ];
    }

    public function columnsQuery(string $table, string $schema): array
    {
        return [
            'sql' => "SELECT
                    COLUMN_NAME AS Field,
                    COLUMN_TYPE AS Type,
                    IS_NULLABLE AS `Null`,
                    COLUMN_KEY AS `Key`,
                    COLUMN_DEFAULT AS `Default`,
                    EXTRA AS Extra
                FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = ?
                ORDER BY ORDINAL_POSITION",
            'params' => [$table],
        ];
    }

    public function buildUpsert(
        string $quotedTable,
        array $quotedColumns,
        array $valueExpressions,
        array $quotedConflictColumns,
        array $quotedUpdateColumns,
    ): string {
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $quotedTable,
            implode(', ', $quotedColumns),
            implode(', ', $valueExpressions),
        );

        if ($quotedUpdateColumns === []) {
            // MySQL has no generic DO NOTHING. A no-op assignment keeps the
            // operation atomic while preserving the existing row.
            $column = $quotedConflictColumns[0] ?? $quotedColumns[0];
            return $sql . " AS c2f_new ON DUPLICATE KEY UPDATE {$column} = {$column}";
        }

        $updates = array_map(
            static fn (string $column): string => "{$column} = c2f_new.{$column}",
            $quotedUpdateColumns,
        );

        return $sql . ' AS c2f_new ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    }

    public function lastInsertedId(PDO $connection, ?string $sequence = null): int|string
    {
        return $connection->lastInsertId() ?: 0;
    }

    protected function quotePart(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    protected function isAlreadyQuoted(string $identifier): bool
    {
        return str_starts_with($identifier, '`') && str_ends_with($identifier, '`');
    }
}
