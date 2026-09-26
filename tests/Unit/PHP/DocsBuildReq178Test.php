<?php

declare(strict_types=1);

use Conn2Flow\Cli\Support\Docs\DocsBuilder;
use Conn2Flow\Cli\Support\Docs\DocsTree;
use Conn2Flow\Cli\Support\Docs\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

foreach (['Frontmatter', 'PhpFunctionExtractor', 'LibraryReference', 'DocsTree', 'DocsAuditor', 'DocsTheme', 'MarkdownRenderer', 'DocsBuilder'] as $__c2fDocsClass) {
    require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/' . $__c2fDocsClass . '.php';
}

/**
 * req-178 / BATCH-183: parser docs:build (Markdown → recursos com Tailwind).
 */
final class DocsBuildReq178Test extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = str_replace('\\', '/', sys_get_temp_dir()) . '/c2f-docsbuild-' . bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);
    }

    protected function tearDown(): void
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmp, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($this->tmp);
    }

    public function testRendererAplicaTemaAncorasCalloutsECodigoSemClasseInline(): void
    {
        $r = new MarkdownRenderer(['NOTE' => 'Nota'], static fn (string $h): string => $h);
        $out = $r->render("## Título `x`\n\n## Título `x`\n\n### Ação\n\nTexto com `code`.\n\n> [!NOTE]\n> Corpo **forte**.\n\n```php\n\$a = 1;\n```\n");

        self::assertStringContainsString('<h2 id="titulo-x" class="scroll-mt-24', $out['html']);
        self::assertStringContainsString('id="titulo-x-2"', $out['html'], 'ids repetidos ganham sufixo');
        self::assertSame(['titulo-x', 'titulo-x-2', 'acao'], array_column($out['toc'], 'id'));
        self::assertStringContainsString('<aside class="my-6 rounded-xl border border-sky-400/30', $out['html']);
        self::assertStringContainsString('>Nota</div>', $out['html']);
        self::assertStringNotContainsString('[!NOTE]', $out['html']);
        self::assertStringContainsString('<code class="font-mono text-gray-200">', $out['html'], 'código em bloco não recebe a classe de código inline');
        self::assertStringContainsString('data-lang="php"', $out['html']);
        self::assertStringContainsString('data-docs-copy', $out['html']);
        self::assertStringNotContainsString('&amp;_', $out['html'], 'variantes com & não chegariam ao compilador do Tailwind');
    }

    public function testRendererNeutralizaMarcadoresDoGestorEEscapaHtml(): void
    {
        $r = new MarkdownRenderer([], static fn (string $h): string => MarkdownRenderer::URL_ROOT_TOKEN . 'docs/x/');
        $html = $r->render("Use `@[[item#url]]@` e @[[pagina#titulo]]@.\n\n<script>alert(1)</script>\n\n[link](a.md)\n")['html'];

        self::assertStringNotContainsString('@[[item#url]]@', $html);
        self::assertStringContainsString('&#64;[[item#url]]&#64;', $html);
        self::assertStringContainsString('&#64;[[pagina#titulo]]&#64;', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('href="@[[pagina#url-raiz]]@docs/x/"', $html, 'só o link gerado pelo build fica ativo');
    }

    public function testCaminhoDoCodigoENomesDosModulos(): void
    {
        self::assertSame('bibliotecas/cron.php', DocsBuilder::codePath('reference/libraries/cron.md', []));
        self::assertSame('', DocsBuilder::codePath('reference/libraries/index.md', []));
        self::assertSame('modulos/menus/', DocsBuilder::codePath('reference/modules/menus.md', ['module' => 'menus']));
        self::assertSame('', DocsBuilder::codePath('concepts/hooks.md', ['module' => 'menus']));

        $json = $this->tmp . '/ModulosData.json';
        file_put_contents($json, json_encode([
            ['language' => 'pt-br', 'id' => 'usuarios', 'nome' => 'Usuários'],
            ['language' => 'en', 'id' => 'usuarios', 'nome' => 'Users'],
            ['language' => 'en', 'id' => 'vazio', 'nome' => ' '],
        ]));
        self::assertSame(['pt-br' => ['usuarios' => 'Usuários'], 'en' => ['usuarios' => 'Users']], DocsBuilder::loadModuleNames($json));
        self::assertSame([], DocsBuilder::loadModuleNames($this->tmp . '/nao-existe.json'));
    }

    public function testBuilderGeraPublicacoesPaginasMenuLandingELlms(): void
    {
        $this->project();
        $plan = (new DocsBuilder(new DocsTree($this->tmp . '/core'), $this->tmp . '/site', $this->config()))->plan();

        self::assertSame([], $plan['errors']);
        self::assertSame(['pages' => 4, 'publications' => 2, 'landings' => 1], $plan['stats']);

        $pages = json_decode($plan['write'][$this->tmp . '/site/resources/pt-br/pages.json'], true);
        $byId = array_column($pages, null, 'id');
        self::assertSame('outra-pagina', $pages[0]['id'], 'recursos de outros ids são preservados');
        self::assertSame('9.9', $byId['docs-reference-libraries-lib']['version'], 'version/checksum do pipeline são mantidos');
        self::assertSame('docs-ref', $byId['docs-reference-libraries-lib']['publisher_id']);
        self::assertSame('docs/reference/libraries/lib/', $byId['docs-reference-libraries-lib']['path']);
        self::assertArrayNotHasKey('publisher_id', $byId['docs'], 'index.md é página comum');
        self::assertArrayNotHasKey('docs-velha', $byId, 'doc removida sai do JSON');
        self::assertContains($this->tmp . '/site/resources/pt-br/pages/docs-velha', $plan['delete']);
        self::assertArrayHasKey('docs-reference', $byId, 'landing do publisher com index_widget');

        $html = $plan['write'][$this->tmp . '/site/resources/pt-br/pages/docs-reference-libraries-lib/docs-reference-libraries-lib.html'];
        self::assertStringContainsString('<h1>Lib</h1>', $html);
        self::assertStringContainsString('href="@[[pagina#url-raiz]]@docs/guides/a/"', $html);
        self::assertStringContainsString('https://github.com/o/r/blob/main/gestor/bibliotecas/lib.php', $html);
        self::assertStringNotContainsString('c2f:extract', $html);
        self::assertStringContainsString('bibliotecas/lib.php</code></p>', $html, 'selo com o caminho a partir da raiz do Gestor');
        self::assertStringNotContainsString('@[[publisher#', $html);

        $landing = $plan['write'][$this->tmp . '/site/resources/pt-br/pages/docs-reference/docs-reference.html'];
        self::assertStringContainsString('<!-- widgets#publisher-index->render({"grupo_slug": "docs-ref-index"}) < -->', $landing);

        $pubPages = json_decode($plan['write'][$this->tmp . '/site/resources/pt-br/publisher-pages.json'], true);
        self::assertSame(['docs-guides-a', 'docs-reference-libraries-lib'], array_column($pubPages, 'page_id'));
        self::assertSame(
            ['titulo', 'descricao', 'secao', 'conteudo', 'sumario', 'navegacao', 'verificacao', 'markdown'],
            array_column($pubPages[0]['fields_values'], 'id')
        );

        $menu = json_decode($plan['write'][$this->tmp . '/site/resources/pt-br/menus.json'], true)[0]['fields_schema']['menus']['visible_to_all'];
        self::assertSame(['docs', 'cabecalho', 'docs-reference'], [$menu[0]['page_id'], $menu[1]['type'], $menu[2]['page_id']]);
        self::assertSame('Bibliotecas', $menu[2]['children'][0]['label']);

        self::assertStringContainsString('- [Lib](https://site.test/docs/reference/libraries/lib/): D', $plan['write'][$this->tmp . '/site/assets/docs/llms-pt-br.txt']);
        self::assertContains('pt-br:guides/secreta.md: visibility restricted — não publicado.', $plan['warnings']);
    }

    public function testBuilderEIdempotenteELinkQuebradoAbortaOBuild(): void
    {
        $this->project();
        $builder = new DocsBuilder(new DocsTree($this->tmp . '/core'), $this->tmp . '/site', $this->config());
        foreach ($builder->plan()['write'] as $path => $content) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, $content);
        }
        foreach ($builder->plan()['write'] as $path => $content) {
            self::assertSame(file_get_contents($path), $content, "segunda execução não muda {$path}");
        }

        $this->file('core/ai-workspace/pt-br/docs/guides/a.md', "---\ntitle: A\ndescription: D\nsection: guides\nverified_at: abc\n---\n[x](nada.md)\n");
        $plan = (new DocsBuilder(new DocsTree($this->tmp . '/core'), $this->tmp . '/site', $this->config()))->plan();
        self::assertContains('pt-br:guides/a.md: link quebrado para nada.md.', $plan['errors']);
    }

    private function project(): void
    {
        $doc = static fn (string $title, string $section, string $body, string $extra = ''): string => "---\ntitle: {$title}\ndescription: D\nsection: {$section}\nverified_at: abc\n{$extra}---\n# {$title}\n\n{$body}\n";
        $this->file('core/ai-workspace/pt-br/docs/index.md', $doc('Início', 'home', 'Bem-vindo.'));
        $this->file('core/ai-workspace/pt-br/docs/guides/a.md', $doc('A', 'guides', "## Um\n\n## Dois\n\n[lib](../reference/libraries/lib.md)"));
        $this->file('core/ai-workspace/pt-br/docs/guides/secreta.md', $doc('S', 'guides', 'x', "visibility: restricted\n"));
        $this->file('core/ai-workspace/pt-br/docs/reference/libraries/lib.md', $doc('Lib', 'reference', "Veja [A](../../guides/a.md) e [fonte](../../../../../gestor/bibliotecas/lib.php).\n\n<!-- c2f:extract:start -->\n- x\n<!-- c2f:extract:end -->", "sources:\n  - gestor/bibliotecas/lib.php\n"));
        $this->file('core/gestor/bibliotecas/lib.php', "<?php\n");

        $this->file('site/resources/pt-br/templates/art/art.html', "<h1>@[[publisher#text#titulo]]@</h1>@[[publisher#html#conteudo]]@@[[publisher#html#verificacao]]@");
        $this->file('site/resources/pt-br/templates/side/side.html', '<!-- item < -->x<!-- item > -->');
        $this->file('site/resources/pt-br/pages.json', json_encode([
            ['name' => 'Outra', 'id' => 'outra-pagina', 'path' => 'outra/'],
            ['name' => 'Lib', 'id' => 'docs-reference-libraries-lib', 'version' => '9.9', 'checksum' => ['html' => 'x']],
            ['name' => 'Velha', 'id' => 'docs-velha'],
        ]));
        mkdir($this->tmp . '/site/resources/pt-br/pages/docs-velha', 0777, true);
    }

    /** @return array<string, mixed> */
    private function config(): array
    {
        return [
            'languages' => ['pt-br'],
            'base_path' => 'docs/',
            'layout' => 'lay',
            'article_template' => 'art',
            'menu' => ['id' => 'docs-side', 'template' => 'side'],
            'publishers' => [
                'docs-g' => ['sections' => ['guides'], 'index_path' => '', 'index_widget' => ''],
                'docs-ref' => ['sections' => ['reference'], 'index_path' => 'reference/', 'index_widget' => 'docs-ref-index'],
            ],
            'repository' => 'https://github.com/o/r',
            'site_url' => 'https://site.test/',
            'llms_default_language' => 'en',
            'labels' => ['pt-br' => ['subsections' => ['libraries' => 'Bibliotecas'], 'sections' => ['reference' => 'Referência']]],
        ];
    }

    private function file(string $rel, string $content): void
    {
        $path = $this->tmp . '/' . $rel;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);
    }
}
