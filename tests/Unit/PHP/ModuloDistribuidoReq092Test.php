<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/modulo-distribuido.php';

final class ModuloDistribuidoReq092Test extends TestCase
{
    private function pdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE distributed_exchanges (id TEXT PRIMARY KEY, kind TEXT, payload TEXT, expires_at INTEGER, consumed INTEGER)');
        return $pdo;
    }

    public function testEnvelopeRejectsReplayStaleTimestampsAndWrongModule(): void
    {
        $pdo = $this->pdo();
        $data = ['modulo' => 'products', 'timestamp' => time(), 'nonce' => bin2hex(random_bytes(16))];
        $body = json_encode($data);
        $signature = modulo_distribuido_assinar($body, 'secret');
        self::assertFalse(modulo_distribuido_validar_envelope($body, $signature, 'secret', 'orders', $pdo));
        self::assertSame($data, modulo_distribuido_validar_envelope($body, $signature, 'secret', 'products', $pdo));
        self::assertFalse(modulo_distribuido_validar_envelope($body, $signature, 'secret', 'products', $pdo));
        $data['timestamp'] -= 121;
        $body = json_encode($data);
        self::assertFalse(modulo_distribuido_validar_envelope($body, modulo_distribuido_assinar($body, 'secret'), 'secret', 'products', $pdo));
    }

    public function testExchangesAreEncryptedSingleUseAndExpire(): void
    {
        $pdo = $this->pdo();
        $data = ['access_token' => 'private-access', 'refresh_token' => 'private-refresh'];
        $code = modulo_distribuido_registro_emitir('login', $data, 'secret', 120, $pdo);
        self::assertStringNotContainsString('private-access', $pdo->query('SELECT payload FROM distributed_exchanges')->fetchColumn());
        self::assertFalse(modulo_distribuido_registro_consumir($code, 'iframe', 'secret', $pdo));
        self::assertFalse(modulo_distribuido_registro_consumir($code, 'login', 'wrong-secret', $pdo));
        self::assertSame($data, modulo_distribuido_registro_consumir($code, 'login', 'secret', $pdo));
        self::assertFalse(modulo_distribuido_registro_consumir($code, 'login', 'secret', $pdo));
        $expired = modulo_distribuido_registro_emitir('login', $data, 'secret', -1, $pdo);
        self::assertFalse(modulo_distribuido_registro_consumir($expired, 'login', 'secret', $pdo));
    }

    public function testSqlAllowlistRejectsCrossSchemaAndMixedTables(): void
    {
        self::assertTrue(modulo_distribuido_sql_autorizada('SELECT p.name FROM products p WHERE p.price=1.5', ['products']));
        self::assertFalse(modulo_distribuido_sql_autorizada('SELECT * FROM other.products', ['products']));
        self::assertFalse(modulo_distribuido_sql_autorizada('SELECT * FROM `other`.`products`', ['products']));
        self::assertFalse(modulo_distribuido_sql_autorizada('SELECT * FROM products JOIN usuarios ON products.id=usuarios.id', ['products']));
        self::assertFalse(modulo_distribuido_sql_autorizada('SELECT * FROM products, usuarios', ['products']));
        self::assertFalse(modulo_distribuido_sql_autorizada('UPDATE products, usuarios SET usuarios.nome=products.name', ['products']));
        self::assertFalse(modulo_distribuido_sql_autorizada('DELETE FROM products USING products, usuarios', ['products']));
        self::assertFalse(modulo_distribuido_sql_autorizada('SELECT * FROM products JOIN "usuarios" ON 1=1', ['products']));
        self::assertTrue(modulo_distribuido_sql_autorizada("UPDATE products SET name='FROM usuarios' WHERE id=1", ['products']));
    }
    public function testSqlChannelRejectsExecutableCommentsAndStacking(): void
    {
        foreach (["SELECT 1; SELECT 2", "SELECT 1;;", "SELECT 1 /*! INTO OUTFILE '/tmp/x' */",
            "SELECT LOAD_FILE('/etc/passwd')", "SELECT SLEEP(5)", "SELECT @@version",
            "SELECT * FROM information_schema.tables", "SELECT 'unterminated", "SELECT 1 # comment",
            "DELETE FROM t -- comment", "SELECT 1\0", "DROP TABLE t"] as $sql) {
            self::assertFalse(modulo_distribuido_sql_segura($sql), $sql);
        }
        foreach (["SELECT t.id FROM t WHERE price=1.5;", "INSERT INTO t(a) VALUES ('a; -- b')",
            "UPDATE t SET a='it''s; ok'", "DELETE FROM `t` WHERE id=1"] as $sql) {
            self::assertTrue(modulo_distribuido_sql_segura($sql), $sql);
        }
    }

    public function testTransportExceptionsAndInvalidBodiesFailClosed(): void
    {
        $config = ['endpoint' => 'https://central.test/_api', 'slug' => 'products', 'secret' => 'test-only',
            'transporte' => static function () { throw new RuntimeException('private credentials'); }];
        self::assertSame('error', modulo_distribuido_enviar(['sql' => 'SELECT 1'], $config)['status']);
        $config['transporte'] = static fn() => '<html>private credentials</html>';
        self::assertArrayNotHasKey('raw', modulo_distribuido_enviar(['sql' => 'SELECT 1'], $config));
        $config['secret'] = '';
        self::assertSame('error', modulo_distribuido_enviar(['sql' => 'SELECT 1'], $config)['status']);
    }

    public function testDatabaseFailureDoesNotExposeSqlOrServerDetails(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $result = modulo_distribuido_executar_local(['sql' => 'SELECT * FROM private_table'], $pdo);
        self::assertSame(['status' => 'error', 'code' => 'distributed-db-failed'], $result);
    }
}
