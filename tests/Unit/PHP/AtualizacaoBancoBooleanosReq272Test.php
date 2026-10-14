<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-272 — booleanos das sementes (`"sem_permissao": false`) gravados como 0/1.
 *
 * O PDO envia `false` como string vazia; numa coluna inteira em modo estrito (MariaDB de produção)
 * o INSERT de página nova falhava com 1366 e o deploy do pré-lançamento parava na etapa 6.
 */
final class AtualizacaoBancoBooleanosReq272Test extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }

        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . 'controladores' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR
            . 'atualizacoes-banco-de-dados.php';
    }

    public function testBooleanosViramInteirosEORestoFicaIgual(): void
    {
        self::assertSame(
            [':a' => 0, ':b' => 1, ':c' => null, ':d' => '', ':e' => 'x', ':f' => 7],
            paramsBanco([':a' => false, ':b' => true, ':c' => null, ':d' => '', ':e' => 'x', ':f' => 7])
        );
    }

    /**
     * Na atualização pelo painel o processo já carregou o `gestor.php` da versão anterior: o sincronizador
     * novo precisa carregar o `db-data.php` ele mesmo (a 3.0.0 vinda da 2.10.x parava em
     * "Call to undefined function db_data_list_tables()" e o banco não recebia as sementes).
     */
    public function testSincronizadoresCarregamODbDataDiretamente(): void
    {
        foreach (['controladores/atualizacoes/atualizacoes-banco-de-dados.php', 'controladores/plugins/atualizacao-plugin-banco-de-dados.php'] as $arquivo) {
            $fonte = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/' . $arquivo);
            self::assertStringContainsString("require_once \$BASE_PATH_DB . 'bibliotecas/db-data.php';", $fonte, $arquivo);
        }
    }

    /**
     * Na atualização do sistema o `schema-metadata.json` é o do core, mas os `*Data.json` do projeto
     * continuam em `db/data/`: sem o contrato do projeto, `publisher` ficava sem chave natural e o
     * INSERT batia em chave duplicada (transformamp.local, 3.0.0 ➔ 3.0.1).
     */
    public function testContratoDoProjetoCompletaOCoreSemSobrescrever(): void
    {
        $raiz = sys_get_temp_dir() . '/c2f-req272-' . bin2hex(random_bytes(4)) . '/';
        mkdir($raiz . 'db/data', 0777, true);
        file_put_contents($raiz . 'db/data/schema-metadata.json', json_encode(['tables' => [
            'paginas' => ['strategy' => 'natural_key', 'natural_key_columns' => ['language', 'modulo', 'id']],
        ]]));
        file_put_contents($raiz . 'project-schema-metadata.json', json_encode(['tabelas' => [
            'publisher' => ['strategy' => 'natural_key', 'natural_key_columns' => ['language', 'id']],
            'paginas' => ['strategy' => 'pk', 'natural_key_columns' => []],
        ]]));
        $antes = [$GLOBALS['DB_DATA_DIR'] ?? null, $GLOBALS['BASE_PATH_DB'] ?? null];
        try {
            $GLOBALS['DB_DATA_DIR'] = $raiz . 'db/data/';
            $GLOBALS['BASE_PATH_DB'] = $raiz;
            $meta = schemaMetadata(true);
            self::assertSame(['language', 'id'], $meta['tables']['publisher']['natural_key_columns']);
            self::assertSame('natural_key', $meta['tables']['paginas']['strategy'], 'o core tem precedência');
        } finally {
            [$GLOBALS['DB_DATA_DIR'], $GLOBALS['BASE_PATH_DB']] = $antes;
            schemaMetadata(true);
            array_map('unlink', [$raiz . 'db/data/schema-metadata.json', $raiz . 'project-schema-metadata.json']);
            rmdir($raiz . 'db/data'); rmdir($raiz . 'db'); rmdir($raiz);
        }
    }

    /** A atualização do sistema não pode terminar "success" quando a rotina do banco devolveu 1. */
    public function testAtualizacaoDoSistemaConfereORetornoDoBanco(): void
    {
        $fonte = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/atualizacoes/atualizacoes-sistema.php');
        self::assertStringContainsString('$rcBanco = require $script;', $fonte);
        self::assertStringContainsString("if (\$rcBanco === 1) throw new DatabaseUpdateException(", $fonte);
    }

    public function testInsercaoEmLoteGravaFalseComoZero(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('pdo_sqlite indisponível');
        }
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE paginas (id TEXT, sem_permissao INTEGER)');

        $cnt = ['inserted' => 0, 'same' => 0];
        inserirEmLote($pdo, 'paginas', [
            ['id' => 'aberta', 'sem_permissao' => true],
            ['id' => 'restrita', 'sem_permissao' => false],
        ], 1 << 20, false, $cnt);

        self::assertSame(2, $cnt['inserted']);
        $linhas = $pdo->query("SELECT id, typeof(sem_permissao) AS tipo, sem_permissao FROM paginas ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        self::assertSame([
            ['id' => 'aberta', 'tipo' => 'integer', 'sem_permissao' => 1],
            ['id' => 'restrita', 'tipo' => 'integer', 'sem_permissao' => 0],
        ], $linhas);
    }
}
