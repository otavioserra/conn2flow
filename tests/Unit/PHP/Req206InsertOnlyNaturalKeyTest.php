<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-206: `insert_only` vale também para tabela de chave natural.
 *
 * A req-202 passou `usuarios` de `pk` para `natural_key`. O sincronizador só respeitava
 * `insert_only` no ramo de PK, então a semente do core regravava login, e-mail e senha do
 * administrador de cada instalação a cada deploy.
 */
final class Req206InsertOnlyNaturalKeyTest extends TestCase
{
    private string $diretorio = '';
    private string $diretorioAnterior = '';
    private ?array $opcoesAnteriores = null;

    protected function setUp(): void
    {
        if (!defined('SDD_NO_AUTORUN')) define('SDD_NO_AUTORUN', true);
        require_once CONN2FLOW_GESTOR_ROOT . '/controladores/atualizacoes/atualizacoes-banco-de-dados.php';

        $this->diretorioAnterior = (string)($GLOBALS['DB_DATA_DIR'] ?? '');
        $this->opcoesAnteriores = $GLOBALS['CLI_OPTS'] ?? null;
        $this->diretorio = sys_get_temp_dir() . '/c2f_req206_' . bin2hex(random_bytes(5));
        mkdir($this->diretorio);
        $GLOBALS['DB_DATA_DIR'] = $this->diretorio . '/';
        $GLOBALS['CLI_OPTS'] = ['orphans-mode' => 'ignore'];
    }

    protected function tearDown(): void
    {
        $GLOBALS['DB_DATA_DIR'] = is_file($this->diretorioAnterior . '/schema-metadata.json')
            ? $this->diretorioAnterior : CONN2FLOW_GESTOR_ROOT . '/db/data/';
        if ($this->opcoesAnteriores === null) unset($GLOBALS['CLI_OPTS']);
        else $GLOBALS['CLI_OPTS'] = $this->opcoesAnteriores;
        schemaMetadata(true);
        @unlink($this->diretorio . '/schema-metadata.json');
        @rmdir($this->diretorio);
    }

    private function contrato(bool $insertOnly): void
    {
        // O mesmo contrato que o compilador gera para `usuarios`, variando só `insert_only`.
        file_put_contents($this->diretorio . '/schema-metadata.json', (string)json_encode([
            'tables' => ['usuarios' => [
                'nome' => 'usuarios', 'id' => 'id', 'id_numerico' => 'id_usuarios',
                'strategy' => 'natural_key', 'natural_key_columns' => ['id'],
                'preserve_on_user_modified' => ['senha', 'email', 'nome', 'usuario', 'status'],
                'insert_only' => $insertOnly,
            ]],
            'deletar' => [], 'forcar_atualizacao' => [],
        ]));
        schemaMetadata(true);
    }

    private function banco(): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE usuarios (id_usuarios INTEGER PRIMARY KEY, id TEXT, usuario TEXT, email TEXT,
            senha TEXT, versao INTEGER, user_modified INTEGER DEFAULT 0, project TEXT)');
        $pdo->exec("INSERT INTO usuarios (id_usuarios,id,usuario,email,senha,versao)
            VALUES (1,'administrador','dona@instalacao.test','dona@instalacao.test','hash-da-instalacao',2)");
        return $pdo;
    }

    private function sincronizar(PDO $pdo, array $registros): void
    {
        ob_start();
        try {
            sincronizarTabela($pdo, 'usuarios', $registros, false);
        } finally {
            ob_end_clean();
        }
    }

    public function testSementeNaoRegravaOAdministradorQueJaExiste(): void
    {
        $this->contrato(true);
        $pdo = $this->banco();

        $this->sincronizar($pdo, [[
            'id' => 'administrador', 'usuario' => 'admin', 'email' => 'admin@admin',
            'senha' => 'hash-da-semente', 'versao' => 3, 'user_modified' => 0,
        ]]);

        self::assertSame(
            ['usuario' => 'dona@instalacao.test', 'email' => 'dona@instalacao.test', 'senha' => 'hash-da-instalacao', 'versao' => 2],
            $pdo->query('SELECT usuario,email,senha,versao FROM usuarios WHERE id_usuarios=1')->fetch(PDO::FETCH_ASSOC)
        );
    }

    public function testSementeAindaInsereOQueFalta(): void
    {
        $this->contrato(true);
        $pdo = $this->banco();

        $this->sincronizar($pdo, [
            ['id' => 'administrador', 'usuario' => 'admin', 'email' => 'admin@admin', 'senha' => 'hash-da-semente', 'versao' => 3],
            ['id' => 'suporte', 'usuario' => 'suporte', 'email' => 'suporte@admin', 'senha' => 'hash-novo', 'versao' => 1],
        ]);

        self::assertSame(
            ['administrador' => 'dona@instalacao.test', 'suporte' => 'suporte'],
            $pdo->query('SELECT id,usuario FROM usuarios ORDER BY id_usuarios')->fetchAll(PDO::FETCH_KEY_PAIR)
        );
    }

    public function testSemInsertOnlyAChaveNaturalContinuaAtualizando(): void
    {
        $this->contrato(false);
        $pdo = $this->banco();

        $this->sincronizar($pdo, [[
            'id' => 'administrador', 'usuario' => 'admin', 'email' => 'admin@admin',
            'senha' => 'hash-da-semente', 'versao' => 3,
        ]]);

        self::assertSame('admin', $pdo->query('SELECT usuario FROM usuarios WHERE id_usuarios=1')->fetchColumn());
    }
}
