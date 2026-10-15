<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-275 (BATCH-284) — visibilidade e link direto privado das páginas.
 *
 * As decisões (visível? o link confere? já liberada?) são funções PURAS da biblioteca `gestor`. Roteador,
 * formulários e listagens são conferidos pelo contrato do código-fonte, como na req-163/274.
 */
final class PaginasVisibilidadeReq275Test extends TestCase
{
    private const HASH = '0123456789abcdef0123456789abcdef';

    private static function fonte(string $relativo): string
    {
        return (string)file_get_contents(dirname(__DIR__, 3) . '/' . $relativo);
    }

    // ===== Funções puras

    public function testPadraoEhVisivel(): void
    {
        foreach ([null, '', '1', 1, true, ' 1 ', 'x'] as $valor) {
            self::assertTrue(gestor_pagina_visivel($valor), var_export($valor, true));
        }
        foreach (['0', 0, false, ' 0 '] as $valor) {
            self::assertFalse(gestor_pagina_visivel($valor), var_export($valor, true));
        }
    }

    public function testHashGeradoEhValidoEUnico(): void
    {
        $a = gestor_pagina_hash_gerar();
        self::assertTrue(gestor_pagina_hash_valido($a));
        self::assertNotSame($a, gestor_pagina_hash_gerar());
        foreach (['', 'abc', strtoupper(self::HASH), self::HASH . 'a', null, 123] as $ruim) {
            self::assertFalse(gestor_pagina_hash_valido($ruim));
        }
    }

    public function testLinkPrivadoAbsoluto(): void
    {
        self::assertSame('https://site.com/sobre/?acesso=' . self::HASH, gestor_pagina_link_privado('https://site.com/', 'sobre/', self::HASH));
        self::assertSame('https://site.com/en/sobre/?acesso=' . self::HASH, gestor_pagina_link_privado('https://site.com', '/sobre', self::HASH, 'en/'));
        self::assertSame('https://site.com/?acesso=' . self::HASH, gestor_pagina_link_privado('https://site.com/', '/', self::HASH));
        self::assertSame('', gestor_pagina_link_privado('https://site.com/', 'sobre/', ''));
    }

    public function testPaginaVisivelSempreAbre(): void
    {
        self::assertSame(['permitido' => true, 'liberar' => false],
            gestor_pagina_acesso_privado(['visivel' => '1', 'hash_privado' => self::HASH, 'id' => 'sobre'], null, [], false));
    }

    public function testNaoVisivelSoAbreComOLinkCorreto(): void
    {
        $pagina = ['visivel' => '0', 'hash_privado' => self::HASH, 'id' => 'sobre'];

        self::assertSame(['permitido' => false, 'liberar' => false], gestor_pagina_acesso_privado($pagina, null, [], false));
        self::assertSame(['permitido' => false, 'liberar' => false], gestor_pagina_acesso_privado($pagina, 'ffffffffffffffffffffffffffffffff', [], false));
        self::assertSame(['permitido' => true, 'liberar' => true], gestor_pagina_acesso_privado($pagina, self::HASH, [], false));
        self::assertSame(['permitido' => true, 'liberar' => true], gestor_pagina_acesso_privado($pagina, ' ' . strtoupper(self::HASH) . ' ', [], false));
    }

    public function testNaoVisivelLiberadaNaSessaoOuParaQuemGerencia(): void
    {
        $pagina = ['visivel' => '0', 'hash_privado' => self::HASH, 'id' => 'sobre'];

        self::assertSame(['permitido' => true, 'liberar' => false], gestor_pagina_acesso_privado($pagina, null, ['outra', 'sobre'], false));
        self::assertSame(['permitido' => false, 'liberar' => false], gestor_pagina_acesso_privado($pagina, null, ['outra'], false));
        self::assertSame(['permitido' => true, 'liberar' => false], gestor_pagina_acesso_privado($pagina, null, [], true));
    }

    public function testSemHashGravadoNenhumLinkAbre(): void
    {
        self::assertFalse(gestor_pagina_acesso_privado(['visivel' => '0', 'hash_privado' => '', 'id' => 'sobre'], '', [], false)['permitido']);
    }

    // ===== Migração

    public function testMigracaoCriaColunasComPadraoVisivelEPreencheHash(): void
    {
        $m = self::fonte('gestor/db/migrations/20261015160000_add_visibility_and_private_hash_to_paginas.php');

        self::assertStringContainsString("addColumn('visivel', 'boolean', ['default' => true, 'null' => false])", $m);
        self::assertStringContainsString("addColumn('hash_privado', 'string'", $m);
        self::assertStringContainsString('bin2hex(random_bytes(16))', $m);
        self::assertStringContainsString("'unique' => true", $m);
    }

    // ===== Roteador

    public function testRoteadorAplicaVisibilidadeAntesDaPermissao(): void
    {
        $fonte = self::fonte('gestor/gestor.php');
        $chamada = strpos($fonte, 'gestor_roteador_pagina_visibilidade($paginas);');
        $permissao = strpos($fonte, "if(!gestor_pagina_sem_permissao(\$paginas[0]['sem_permissao'] ?? null)){");

        self::assertNotFalse($chamada);
        self::assertNotFalse($permissao);
        self::assertLessThan($permissao, $chamada, 'Página escondida responde 404 antes de pedir login.');
        self::assertStringContainsString("\$campos[] = 'id_paginas';", $fonte);

        $inicio = strpos($fonte, 'function gestor_roteador_pagina_visibilidade(&$paginas){');
        $corpo = substr($fonte, $inicio, 2500);
        self::assertStringContainsString("\$_GET['acesso']", $corpo);
        self::assertStringContainsString('GESTOR_PAGINAS_LIBERADAS_SESSAO', $corpo);
        self::assertStringContainsString("gestor_acesso(false, 'admin-paginas') || gestor_acesso(false, 'publisher-pages')", $corpo);
        self::assertStringContainsString('$paginas = null;', $corpo);
        self::assertStringContainsString('X-Robots-Tag: noindex, nofollow', $corpo);
    }

    // ===== Módulos

    public static function modulos(): array
    {
        return [['admin-paginas'], ['publisher-pages']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('modulos')]
    public function testModuloGravaEExibeAVisibilidade(string $modulo): void
    {
        $php = self::fonte("gestor/modulos/$modulo/$modulo.php");

        self::assertStringContainsString("gestor_incluir_biblioteca('paginas-visibilidade');", $php);
        self::assertSame(2, substr_count($php, 'paginas_visibilidade_campos_adicionar($_REQUEST)'), 'adicionar e clonar');
        self::assertSame(1, substr_count($php, 'paginas_visibilidade_campos_editar($_REQUEST'), 'editar');
        self::assertStringContainsString("paginas_visibilidade_formulario(\$_GESTOR['pagina'], null,", $php);
        self::assertStringContainsString("paginas_visibilidade_formulario(\$_GESTOR['pagina'], \$retorno_bd,", $php);
        self::assertStringContainsString("Array('visivel' => \$retorno_bd['visivel'] ?? null)", $php, 'clonar não leva o hash da original');

        $js = self::fonte("gestor/modulos/$modulo/$modulo.js");
        self::assertStringContainsString("'#page-visibility-copy'", $js);
        self::assertStringContainsString('regenerar_hash_privado', $js);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('modulos')]
    public function testFormulariosEVariaveisNosDoisIdiomas(string $modulo): void
    {
        $json = json_decode(self::fonte("gestor/modulos/$modulo/$modulo.json"), true);

        foreach (['pt-br', 'en'] as $idioma) {
            foreach (['adicionar', 'editar', 'clonar'] as $op) {
                $html = self::fonte("gestor/modulos/$modulo/resources/$idioma/pages/$modulo-$op/$modulo-$op.html");
                self::assertSame(1, substr_count($html, '#page-visibility#'), "$idioma/$op");
                self::assertLessThan(strpos($html, '<!-- agendamento-datas < -->') ?: PHP_INT_MAX, strpos($html, '#page-visibility#'), "$idioma/$op: entre Tipo e Agendamento");
                self::assertGreaterThan(strpos($html, '<!-- permissao-pagina > -->'), strpos($html, '#page-visibility#'), "$idioma/$op");
            }
            $adicionar = self::fonte("gestor/modulos/$modulo/resources/$idioma/pages/$modulo-adicionar/$modulo-adicionar.html");
            self::assertMatchesRegularExpression('/name="sem_permissao"[^>]*checked/', $adicionar, "$idioma: acesso público marcado por padrão");

            $componente = self::fonte("gestor/modulos/$modulo/resources/$idioma/components/page-visibility-tailwind/page-visibility-tailwind.html");
            foreach (['name="visivel"', 'name="regenerar_hash_privado"', 'id="page-visibility-link"', 'id="page-visibility-copy"', 'id="page-visibility-regenerate"'] as $marca) {
                self::assertStringContainsString($marca, $componente);
            }
            self::assertContains('page-visibility-tailwind', array_column($json['resources'][$idioma]['components'], 'id'));

            $variaveis = array_column($json['resources'][$idioma]['variables'], 'value', 'id');
            preg_match_all('/#(visibility-[a-z-]+)#/', $componente, $usadas);
            foreach (array_diff(array_unique($usadas[1]), ['visibility-checked', 'visibility-link']) as $id) {
                self::assertNotSame('', trim((string)($variaveis[$id] ?? '')), "$modulo/$idioma: $id");
            }
        }
    }

    // ===== Listagens públicas

    public function testListagensPublicasDeixamDeForaAsNaoVisiveis(): void
    {
        self::assertStringContainsString('.gestor_paginas_sql_visiveis()', self::fonte('gestor/modulos/pages-index/pages-index.widget.php'));
        self::assertSame(3, substr_count(self::fonte('gestor/modulos/publisher-index/publisher-index.widget.php'), ".gestor_paginas_sql_visiveis('p')"));

        $sitemap = self::fonte('gestor/bibliotecas/sitemap.php');
        self::assertStringContainsString('.gestor_paginas_sql_visiveis()', $sitemap);
        self::assertStringContainsString("!gestor_pagina_visivel(\$pagina['visivel'])", $sitemap);
    }

    public function testSitemapRecusaPaginaNaoVisivel(): void
    {
        require_once dirname(__DIR__, 3) . '/gestor/bibliotecas/sitemap.php';
        $base = ['status' => 'A', 'sem_permissao' => '1', 'caminho' => 'sobre/', 'tipo' => 'pagina', 'language' => 'pt-br'];

        self::assertFalse(sitemap_pagina_elegivel($base + ['visivel' => '0']));
        self::assertSame(sitemap_pagina_elegivel($base), sitemap_pagina_elegivel($base + ['visivel' => '1']));
    }
}
