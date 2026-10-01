<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class Req196LayoutPorPerfilTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $source = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/gestor.php');
        self::assertSame(1, preg_match('/function gestor_roteador_layout_perfil\(.*?\r?\n}\r?\n/s', $source, $match));
        eval($match[0]);
        if (!defined('SDD_NO_AUTORUN')) define('SDD_NO_AUTORUN', true);
        $GLOBALS['RDR_SILENT'] = true;
        require_once CONN2FLOW_GESTOR_ROOT . '/controladores/agents/arquitetura/recuperacao-dados-recursos.php';
    }

    public function testLayoutDoPerfilSubstituiOPadrao(): void
    {
        $mapa = '{"cliente":"layout-cliente","equipe":"layout-equipe"}';
        self::assertSame('layout-cliente', gestor_roteador_layout_perfil('layout-base', $mapa, 'cliente'));
        self::assertSame('layout-equipe', gestor_roteador_layout_perfil('layout-base', $mapa, 'equipe'));
    }

    public function testVisitantePerfilSemMapeamentoEJsonInvalidoUsamFallback(): void
    {
        $mapa = '{"cliente":"layout-cliente"}';
        self::assertSame('layout-base', gestor_roteador_layout_perfil('layout-base', $mapa, null));
        self::assertSame('layout-base', gestor_roteador_layout_perfil('layout-base', $mapa, 'admin'));
        self::assertSame('layout-base', gestor_roteador_layout_perfil('layout-base', '{invalid', 'cliente'));
    }

    public function testCampoEstaNoCompiladorENaMigracao(): void
    {
        $compilador = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/agents/arquitetura/atualizacao-dados-recursos.php');
        $migracao = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/db/migrations/20260930130000_add_profile_home_and_page_layout_mapping.php');
        self::assertStringContainsString("array_key_exists('layouts_users_profiles', \$p)", $compilador);
        self::assertStringContainsString("->addColumn('layouts_users_profiles', 'text'", $migracao);
        self::assertStringContainsString("->addColumn('pagina_inicial', 'string'", $migracao);
    }

    public function testRecuperacaoRecompoeObjetoEOmiteCampoNulo(): void
    {
        $cfg = [
            'nome' => 'paginas', 'id' => 'id', 'id_numerico' => 'id_paginas',
            'field_types' => [], 'scope' => 'global', 'modulo' => null,
            'natural_key_columns' => ['language', 'id'],
            'resources_dir' => null, 'base_dir' => sys_get_temp_dir(),
        ];
        $rec = ['id_paginas' => 1, 'id' => 'perfil', 'language' => 'pt-br',
            'layouts_users_profiles' => '{"layout-cliente":"cliente"}'];
        $resultado = rdr_descompilar_registro($rec, $cfg, 'pt-br');
        self::assertSame('cliente', $resultado['meta']['layouts_users_profiles']->{'layout-cliente'});
        $rec['layouts_users_profiles'] = null;
        $resultado = rdr_descompilar_registro($rec, $cfg, 'pt-br');
        self::assertArrayNotHasKey('layouts_users_profiles', $resultado['meta']);
    }

    public function testSincronizacaoPreservaManifestLegadoEAtualizaMapeamentoDeclarado(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . '/controladores/atualizacoes/atualizacoes-banco-de-dados.php';
        $diretorioAnterior = $GLOBALS['DB_DATA_DIR'];
        $opcoesAnteriores = $GLOBALS['CLI_OPTS'] ?? null;
        $diretorio = sys_get_temp_dir() . '/c2f_req196_' . bin2hex(random_bytes(5));
        mkdir($diretorio);
        try {
            file_put_contents($diretorio . '/schema-metadata.json', (string)json_encode([
                'tables' => ['paginas' => [
                    'nome' => 'paginas', 'id' => 'id', 'id_numerico' => 'id_paginas',
                    'strategy' => 'natural_key', 'natural_key_columns' => ['language', 'modulo', 'id'],
                    'preserve_on_user_modified' => ['layout_id', 'layouts_users_profiles'],
                    'insert_only' => false,
                ]],
                'deletar' => [], 'forcar_atualizacao' => [],
            ], JSON_UNESCAPED_UNICODE));
            $GLOBALS['DB_DATA_DIR'] = $diretorio . '/';
            $GLOBALS['CLI_OPTS'] = ['orphans-mode' => 'ignore'];
            schemaMetadata(true);

            $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('CREATE TABLE paginas (id_paginas INTEGER PRIMARY KEY, id TEXT, language TEXT, modulo TEXT,
                layout_id TEXT, layouts_users_profiles TEXT, user_modified INTEGER DEFAULT 0, project TEXT)');
            $pdo->exec("INSERT INTO paginas (id_paginas,id,language,modulo,layout_id,layouts_users_profiles)
                VALUES (1,'perfil','pt-br','core','layout-base','{\"portal\":\"cliente\"}')");

            ob_start();
            try {
                sincronizarTabela($pdo, 'paginas', [[
                    'id' => 'perfil', 'language' => 'pt-br', 'modulo' => 'core', 'layout_id' => 'layout-novo',
                ]], false);
                $primeiro = $pdo->query('SELECT layout_id,layouts_users_profiles FROM paginas')->fetch(PDO::FETCH_ASSOC);
                self::assertSame('layout-novo', $primeiro['layout_id']);
                self::assertSame('{"portal":"cliente"}', $primeiro['layouts_users_profiles']);

                sincronizarTabela($pdo, 'paginas', [[
                    'id' => 'perfil', 'language' => 'pt-br', 'modulo' => 'core',
                    'layouts_users_profiles' => ['admin' => 'equipe'],
                ]], false);
                self::assertSame('{"admin":"equipe"}', $pdo->query('SELECT layouts_users_profiles FROM paginas')->fetchColumn());

                sincronizarTabela($pdo, 'paginas', [[
                    'id' => 'perfil', 'language' => 'pt-br', 'modulo' => 'core',
                    'layouts_users_profiles' => null,
                ]], false);
                self::assertNull($pdo->query('SELECT layouts_users_profiles FROM paginas')->fetchColumn());
            } finally {
                ob_end_clean();
            }
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

    public function testComponentesBilinguesUsamSelectsDaInterface(): void
    {
        $arquivo = CONN2FLOW_ROOT . '/tests/Fixtures/Req196ComponentRender.php';
        $processo = proc_open([PHP_BINARY, $arquivo], [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes, CONN2FLOW_ROOT);
        self::assertIsResource($processo);
        fclose($pipes[0]);
        $saida = stream_get_contents($pipes[1]);
        $erros = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($processo), $saida . $erros);
    }
}
