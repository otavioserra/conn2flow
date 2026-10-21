<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-292: a sincronização de recursos que muda o `caminho` de uma página existente grava o caminho
 * antigo em `paginas_301` (como o painel), para o endereço antigo responder 301 e não 404.
 */
final class Req292PaginaCaminho301Test extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('SDD_NO_AUTORUN')) define('SDD_NO_AUTORUN', true);
    }

    public function testCaminhoAlteradoPelaSincronizacaoGeraUm301Idempotente(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . '/controladores/atualizacoes/atualizacoes-banco-de-dados.php';
        $diretorioAnterior = $GLOBALS['DB_DATA_DIR'];
        $opcoesAnteriores = $GLOBALS['CLI_OPTS'] ?? null;
        $diretorio = sys_get_temp_dir() . '/c2f_req292_' . bin2hex(random_bytes(5));
        mkdir($diretorio);
        try {
            file_put_contents($diretorio . '/schema-metadata.json', (string)json_encode([
                'tables' => ['paginas' => [
                    'nome' => 'paginas', 'id' => 'id', 'id_numerico' => 'id_paginas',
                    'strategy' => 'natural_key', 'natural_key_columns' => ['language', 'modulo', 'id'],
                    'preserve_on_user_modified' => [], 'insert_only' => false,
                ]],
                'deletar' => [], 'forcar_atualizacao' => [],
            ], JSON_UNESCAPED_UNICODE));
            $GLOBALS['DB_DATA_DIR'] = $diretorio . '/';
            $GLOBALS['CLI_OPTS'] = ['orphans-mode' => 'ignore'];
            schemaMetadata(true);

            $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('CREATE TABLE paginas (id_paginas INTEGER PRIMARY KEY, id TEXT, language TEXT, modulo TEXT,
                caminho TEXT, nome TEXT, user_modified INTEGER DEFAULT 0, project TEXT)');
            $pdo->exec('CREATE TABLE paginas_301 (id_paginas_301 INTEGER PRIMARY KEY, id_paginas INTEGER,
                caminho TEXT, data_criacao TEXT)');
            $pdo->exec("INSERT INTO paginas (id_paginas,id,language,modulo,caminho,nome)
                VALUES (7,'docs-sdd','pt-br','documentation','docs/sdd/','MDD')");

            $registro = ['id' => 'docs-sdd', 'language' => 'pt-br', 'modulo' => 'documentation', 'caminho' => 'docs/mdd/', 'nome' => 'MDD'];
            ob_start();
            try {
                sincronizarTabela($pdo, 'paginas', [$registro], false);
                // Rodar de novo (caminho igual) e com outro campo alterado não duplica nem cria 301.
                sincronizarTabela($pdo, 'paginas', [$registro], false);
                sincronizarTabela($pdo, 'paginas', [['nome' => 'MDD do Core'] + $registro], false);
            } finally {
                ob_end_clean();
            }

            self::assertSame('docs/mdd/', $pdo->query('SELECT caminho FROM paginas WHERE id_paginas=7')->fetchColumn());
            $redirecionamentos = $pdo->query('SELECT id_paginas, caminho FROM paginas_301')->fetchAll(PDO::FETCH_ASSOC);
            self::assertSame([['id_paginas' => 7, 'caminho' => 'docs/sdd/']], $redirecionamentos);
        } finally {
            $GLOBALS['DB_DATA_DIR'] = is_file($diretorioAnterior . '/schema-metadata.json')
                ? $diretorioAnterior : CONN2FLOW_GESTOR_ROOT . '/db/data/';
            if ($opcoesAnteriores === null) unset($GLOBALS['CLI_OPTS']);
            else $GLOBALS['CLI_OPTS'] = $opcoesAnteriores;
            schemaMetadata(true);
            @unlink($diretorio . '/schema-metadata.json');
            @rmdir($diretorio);
        }
    }

    public function testSemTabela301ASincronizacaoSegue(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . '/controladores/atualizacoes/atualizacoes-banco-de-dados.php';
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        ob_start();
        try {
            // Sem `paginas_301` o erro vira aviso no log; a exceção não pode escapar para a sincronização.
            sincronizarRegistrarPagina301($pdo, 'paginas', ['id_paginas' => 1, 'caminho' => 'a/'], ['caminho' => 'b/']);
        } finally {
            ob_end_clean();
        }
        self::assertTrue(true);
    }
}
