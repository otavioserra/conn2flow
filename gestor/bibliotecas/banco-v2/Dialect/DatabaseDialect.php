<?php

declare(strict_types=1);

namespace C2F\Database\Dialect;

use PDO;

/**
 * SQL and connection contract implemented by every database supported by banco v2.
 *
 * Application services must depend on this contract instead of branching on the
 * PDO driver throughout the codebase.
 */
interface DatabaseDialect
{
    public function driverName(): string;

    public function defaultPort(): int;

    public function defaultCharset(): string;

    /**
     * @param array<string, scalar|null> $options
     */
    public function buildDsn(
        string $host,
        int $port,
        string $database,
        string $charset,
        array $options = [],
    ): string;

    public function configureConnection(
        PDO $connection,
        string $charset,
        string $schema,
    ): void;

    public function quoteIdentifier(string $identifier): string;

    /**
     * @return array{sql: string, params: array<int, mixed>}
     */
    public function listTablesQuery(string $schema): array;

    /**
     * @return array{sql: string, params: array<int, mixed>}
     */
    public function columnsQuery(string $table, string $schema): array;

    /**
     * @param list<string> $quotedColumns
     * @param list<string> $valueExpressions
     * @param list<string> $quotedConflictColumns
     * @param list<string> $quotedUpdateColumns
     */
    public function buildUpsert(
        string $quotedTable,
        array $quotedColumns,
        array $valueExpressions,
        array $quotedConflictColumns,
        array $quotedUpdateColumns,
    ): string;

    public function lastInsertedId(PDO $connection, ?string $sequence = null): int|string;
}
