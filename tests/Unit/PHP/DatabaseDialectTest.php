<?php

declare(strict_types=1);

use C2F\Database\Dialect\DialectFactory;
use C2F\Database\Dialect\MySqlDialect;
use C2F\Database\Dialect\PostgreSqlDialect;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/banco-v2/Dialect/DatabaseDialect.php';
require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/banco-v2/Dialect/AbstractDatabaseDialect.php';
require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/banco-v2/Dialect/MySqlDialect.php';
require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/banco-v2/Dialect/PostgreSqlDialect.php';
require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/banco-v2/Dialect/DialectFactory.php';

final class DatabaseDialectTest extends TestCase
{
    public function testFactoryMapsLegacyAndPostgreSqlNames(): void
    {
        self::assertInstanceOf(MySqlDialect::class, DialectFactory::create('mysqli'));
        self::assertInstanceOf(MySqlDialect::class, DialectFactory::create('pdo_mysql'));
        self::assertInstanceOf(PostgreSqlDialect::class, DialectFactory::create('pgsql'));
        self::assertInstanceOf(PostgreSqlDialect::class, DialectFactory::create('postgresql'));
    }

    public function testPostgreSqlDsnIncludesSecurityAndApplicationOptions(): void
    {
        $dialect = new PostgreSqlDialect();

        self::assertSame(
            'pgsql:host=postgres;port=5432;dbname=conn2flow;sslmode=require;application_name=conn2flow-tests',
            $dialect->buildDsn('postgres', 5432, 'conn2flow', 'UTF8', [
                'sslmode' => 'require',
                'application_name' => 'conn2flow-tests',
            ]),
        );
    }

    public function testIdentifiersAreQuotedPerDialectIncludingQualifiedNames(): void
    {
        self::assertSame('`public`.`users`', (new MySqlDialect())->quoteIdentifier('public.users'));
        self::assertSame('"public"."users"', (new PostgreSqlDialect())->quoteIdentifier('public.users'));
        self::assertSame('"users".*', (new PostgreSqlDialect())->quoteIdentifier('users.*'));
    }

    public function testIdentifierInjectionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PostgreSqlDialect())->quoteIdentifier('users; DROP TABLE users');
    }

    public function testPostgreSqlUpsertUsesOnConflictAndExcluded(): void
    {
        $sql = (new PostgreSqlDialect())->buildUpsert(
            '"users"',
            ['"external_id"', '"name"'],
            ['?', '?'],
            ['"external_id"'],
            ['"name"'],
        );

        self::assertSame(
            'INSERT INTO "users" ("external_id", "name") VALUES (?, ?) ON CONFLICT ("external_id") DO UPDATE SET "name" = EXCLUDED."name"',
            $sql,
        );
    }

    public function testPostgreSqlMetadataQueriesAreSchemaAware(): void
    {
        $dialect = new PostgreSqlDialect();

        $tables = $dialect->listTablesQuery('tenant_a');
        $columns = $dialect->columnsQuery('users', 'tenant_a');

        self::assertSame(['tenant_a'], $tables['params']);
        self::assertSame(['tenant_a', 'users'], $columns['params']);
        self::assertStringContainsString('information_schema.columns', $columns['sql']);
        self::assertStringContainsString('PRIMARY KEY', $columns['sql']);
    }
}
