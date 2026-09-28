<?php

declare(strict_types=1);

use Conn2Flow\Cli\Support\Docs\SddSource;
use Conn2Flow\Cli\Support\Docs\DocsBuilder;
use Conn2Flow\Cli\Support\Docs\DocsTree;
use PHPUnit\Framework\TestCase;

foreach (['Frontmatter', 'DocsAuditor', 'LibraryReference', 'DocsTheme', 'MarkdownRenderer', 'DocsTree', 'SddSource', 'DocsBuilder'] as $class) {
    require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/' . $class . '.php';
}

/** req-188 / BATCH-192: arquivo do SDD publicado, resumo sem Markdown cru e rota com ponto. */
final class DocsReq188Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = str_replace('\\', '/', sys_get_temp_dir()) . '/c2f-req188-' . bin2hex(random_bytes(4));
        mkdir($this->root . '/sdd/implementation/archive/fundo', 0777, true);
        mkdir($this->root . '/sdd/backlog', 0777, true);
    }

    protected function tearDown(): void
    {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    public function testResumoSaiEmTextoPuroECortadoNaPalavra(): void
    {
        self::assertSame(
            'auth:cookie por SSH não funcionava (cli/src/X.php). Veja o guia.',
            SddSource::resumo('1. **`auth:cookie` por SSH não funcionava** (`cli/src/X.php`). Veja o [guia](../g.md).')
        );
        self::assertSame('Origem: Humano, 2026-09-26.', SddSource::resumo('**Origem**: Humano, 2026-09-26.'));
        self::assertSame('snake_case e 2 * 3 ficam.', SddSource::resumo('snake_case e 2 * 3 ficam.'));

        $longo = SddSource::resumo(str_repeat('palavra ', 40), 50);
        self::assertStringEndsWith('palavra…', $longo);
        self::assertLessThanOrEqual(51, mb_strlen($longo));
    }

    public function testArquivoDeCadaPastaEntraNaColetaComResumoLimpo(): void
    {
        file_put_contents($this->root . '/sdd/implementation/BATCH-2.md', "# Lote 2\n\n**Status**: `complete`.\n");
        file_put_contents($this->root . '/sdd/implementation/archive/BATCH-1.md', "# Lote 1\n\n---\n\n- item\n\n1. **Feito** em `x`.\n");
        file_put_contents($this->root . '/sdd/implementation/archive/fundo/BATCH-0.md', '# Fundo demais');
        file_put_contents($this->root . '/sdd/backlog/BL-1.md', '# Rascunho');

        $docs = (new SddSource())->collect($this->root)['docs'];

        self::assertSame(['sdd/implementation/BATCH-2.md', 'sdd/implementation/archive/BATCH-1.md'], array_keys($docs));
        self::assertSame('Status: complete.', $docs['sdd/implementation/BATCH-2.md']['meta']['description']);
        self::assertSame('Feito em x.', $docs['sdd/implementation/archive/BATCH-1.md']['meta']['description']);
    }

    public function testMenuAgrupaOArquivoNoFimDaPastaELinkParaEleResolve(): void
    {
        file_put_contents($this->root . '/sdd/implementation/BATCH-2.md', "# Lote 2\n\nContinua o [lote 1](archive/BATCH-1.md).\n");
        file_put_contents($this->root . '/sdd/implementation/archive/BATCH-1.md', "# Lote 1\n\nHistórico.\n");
        foreach (['pt-br', 'en'] as $lang) {
            foreach (['article' => '@[[publisher#html#conteudo]]@', 'side' => '<nav>Menu</nav>'] as $id => $html) {
                mkdir($this->root . "/site/resources/{$lang}/templates/{$id}", 0777, true);
                file_put_contents($this->root . "/site/resources/{$lang}/templates/{$id}/{$id}.html", $html);
            }
        }
        $config = [
            'languages' => ['pt-br', 'en'], 'base_path' => 'docs/', 'layout' => 'layout',
            'article_template' => 'article', 'menu' => ['id' => 'docs-sidebar', 'template' => 'side'],
            'sdd' => ['enabled' => true],
            'publishers' => ['docs-sdd' => ['sections' => ['sdd'], 'index_path' => 'sdd/', 'index_widget' => 'docs-sdd-index']],
            'labels' => ['pt-br' => ['sections' => ['sdd' => 'SDD'], 'subsections' => ['archive' => 'Arquivo']]],
        ];

        $plan = (new DocsBuilder(new DocsTree($this->root), $this->root . '/site', $config))->plan();

        self::assertSame([], $plan['errors']);
        $menus = json_decode($plan['write'][$this->root . '/site/resources/pt-br/menus.json'], true);
        $sdd = $menus[0]['fields_schema']['menus']['visible_to_all'][0];
        $pasta = $sdd['children'][0];
        self::assertSame('Implementation', $pasta['label']);
        self::assertSame(['Lote 2', 'Arquivo'], array_column($pasta['children'], 'label'));
        self::assertSame('cabecalho', $pasta['children'][1]['type']);
        self::assertSame(['Lote 1'], array_column($pasta['children'][1]['children'], 'label'));

        $html = $plan['write'][$this->root . '/site/resources/pt-br/pages/docs-sdd-implementation-BATCH-2/docs-sdd-implementation-BATCH-2.html'];
        self::assertStringContainsString('docs/sdd/implementation/archive/BATCH-1/', $html);
    }

    public function testDeployPelaApiRegeneraOSitemapDepoisDoBancoEDosHooks(): void
    {
        $api = (string) file_get_contents(CONN2FLOW_ROOT . '/gestor/controladores/api/api.php');
        $inicio = (int) strpos($api, 'function api_project_update(');
        $corpo = substr($api, $inicio, (int) strpos($api, "\n}\n", $inicio) - $inicio);

        $banco = strpos($corpo, 'api_executar_atualizacao_banco(');
        $hooks = strpos($corpo, 'atualizacoes_hooks_sincronizar();');
        $sitemap = strpos($corpo, 'api_project_sitemap_regenerar()');
        self::assertNotFalse($sitemap);
        self::assertGreaterThan($banco, $hooks);
        self::assertGreaterThan($hooks, $sitemap);
        self::assertStringContainsString("'sitemap' => \$sitemap", $corpo);

        // Falha no sitemap não pode derrubar o deploy.
        $funcao = substr($api, (int) strpos($api, 'function api_project_sitemap_regenerar('), 700);
        self::assertStringContainsString('catch (Throwable $e)', $funcao);
        self::assertStringContainsString('sitemap_gerar_completo()', $funcao);
    }

    public function testCaminhoTerminadoEmBarraNaoViraArquivoEstatico(): void
    {
        $gestor = (string) file_get_contents(CONN2FLOW_ROOT . '/gestor/gestor.php');

        // `docs/whats-new/2.10/` tinha a "extensão" `10` e caía no servidor de estáticos (404).
        self::assertStringContainsString(
            "\$_GESTOR['caminho-extensao'] = substr(\$_GESTOR['caminho-total'], -1) === '/'",
            $gestor
        );
        self::assertSame('10', pathinfo('docs/whats-new/2.10/', PATHINFO_EXTENSION));
    }
}
