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

    /** req-211: formas que nomeiam uma tabela sem passar por `FROM nome` não podem furar a allowlist. */
    public function testSqlAllowlistRejectsTableReferencesThatAreNotPlainNames(): void
    {
        $permitidas = ['coupons', 'products'];
        foreach ([
            'SELECT * FROM coupons WHERE id IN (SELECT id FROM (usuarios))',
            'SELECT * FROM coupons WHERE id IN (SELECT id FROM(usuarios))',
            'SELECT * FROM coupons WHERE EXISTS (SELECT 1 FROM ((usuarios)))',
            'SELECT * FROM coupons JOIN (usuarios) ON 1=1',
            'SELECT * FROM coupons LEFT JOIN ( usuarios ) ON 1',
            'SELECT * FROM coupons STRAIGHT_JOIN usuarios',
            'SELECT * FROM coupons WHERE (id,code) = (TABLE usuarios)',
            'SELECT * FROM {OJ usuarios LEFT OUTER JOIN coupons ON 1=1}',
            'SELECT * FROM coupons WHERE id = ANY (SELECT id FROM { OJ usuarios })',
            'UPDATE coupons SET a=(SELECT senha FROM (usuarios) LIMIT 1)',
            'SELECT * FROM coupons JOIN products ON 1=1, usuarios',
            'SELECT * FROM coupons c, (SELECT * FROM usuarios) u',
            'SELECT * FROM (SELECT a, b FROM usuarios) x',
            'INSERT INTO coupons (a) VALUES (1) ON DUPLICATE KEY UPDATE a=(SELECT senha FROM usuarios LIMIT 1)',
            'SELECT * FROM coupons c JOIN LATERAL (SELECT * FROM usuarios) u',
            'SELECT * FROM coupons PARTITION (p0), usuarios',
        ] as $sql) {
            self::assertFalse(modulo_distribuido_sql_autorizada($sql, $permitidas), $sql);
        }
        foreach ([
            "SELECT * FROM coupons c LEFT JOIN products p ON p.id=c.id AND p.x IN (1,2) WHERE c.a IN ('a','b') ORDER BY c.a, c.b LIMIT 0, 10",
            'SELECT (SELECT COUNT(*) FROM products), c.a FROM coupons c GROUP BY c.a, c.b',
            'SELECT * FROM (SELECT a, b FROM coupons) x WHERE 1',
            'INSERT INTO coupons (a,b) VALUES (1,2),(3,4) ON DUPLICATE KEY UPDATE a=VALUES(a), b=2',
            'UPDATE coupons SET a=1, b=2 WHERE id=3',
            'DELETE c FROM coupons c JOIN products p ON p.id=c.id',
            'SELECT * FROM coupons FOR UPDATE',
            "SELECT * FROM `coupons` WHERE `status`='A'",
        ] as $sql) {
            self::assertTrue(modulo_distribuido_sql_autorizada($sql, $permitidas), $sql);
        }
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

    public function testColumnMetadataUsesTheReadChannelAndPreservesTheTableBoundary(): void
    {
        self::assertSame('select', modulo_distribuido_detectar_operacao('SHOW COLUMNS FROM coupons'));
        self::assertTrue(modulo_distribuido_sql_autorizada('SHOW COLUMNS FROM `coupons`;', ['coupons']));
        self::assertFalse(modulo_distribuido_sql_autorizada('SHOW COLUMNS FROM usuarios', ['coupons']));
        self::assertFalse(modulo_distribuido_sql_segura('SHOW COLUMNS FROM other.coupons'));
        self::assertFalse(modulo_distribuido_sql_segura('SHOW TABLES'));
        self::assertFalse(modulo_distribuido_sql_segura('SHOW COLUMNS FROM coupons; SELECT * FROM usuarios'));
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
