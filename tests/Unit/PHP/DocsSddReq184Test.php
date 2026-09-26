<?php

declare(strict_types=1);

use Conn2Flow\Cli\Support\Docs\SddSource;
use Conn2Flow\Cli\Support\Docs\DocsBuilder;
use Conn2Flow\Cli\Support\Docs\DocsTree;
use PHPUnit\Framework\TestCase;

foreach (['Frontmatter', 'DocsAuditor', 'LibraryReference', 'DocsTheme', 'MarkdownRenderer', 'DocsTree', 'SddSource', 'DocsBuilder'] as $class) {
    require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/' . $class . '.php';
}

final class DocsSddReq184Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = str_replace('\\', '/', sys_get_temp_dir()) . '/c2f-sdd-' . bin2hex(random_bytes(4));
        mkdir($this->root . '/sdd/process', 0777, true);
        mkdir($this->root . '/sdd/archive', 0777, true);
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

    public function testColetaTituloDescricaoOrdemEExclusoes(): void
    {
        file_put_contents($this->root . '/sdd/README.md', "# SDD\n\nVisão geral pública.\n");
        file_put_contents($this->root . '/sdd/process/01-WORKFLOW.md', "# Fluxo\n\nPrimeiro parágrafo.\n");
        file_put_contents($this->root . '/sdd/archive/secret.md', '# Segredo');
        file_put_contents($this->root . '/sdd/backlog/secret.md', '# Segredo');
        file_put_contents($this->root . '/sdd/MEMORIA-ENGENHARIA-EXECUCAO.md', '# Segredo');
        $result = (new SddSource())->collect($this->root);
        self::assertSame(['sdd/README.md', 'sdd/process/01-WORKFLOW.md'], array_keys($result['docs']));
        self::assertSame('Fluxo', $result['docs']['sdd/process/01-WORKFLOW.md']['meta']['title']);
        self::assertSame('Primeiro parágrafo.', $result['docs']['sdd/process/01-WORKFLOW.md']['meta']['description']);
        self::assertSame(1, $result['docs']['sdd/process/01-WORKFLOW.md']['meta']['order']);
    }

    public function testFiltroRedigeDadosEExcluiCredenciais(): void
    {
        $source = new SddSource();
        self::assertNull($source->filter("password: valorSuperSecreto123\n"));
        self::assertNull($source->filter("-----BEGIN OPENSSH PRIVATE KEY-----\n"));
        self::assertNull($source->filter("Authorization: Bearer abcdefghijklmnopqrstuvwxyz1234\n"));
        $clean = $source->filter("C:\\Users\\ana\\repo C:/Users/ana/repo /home/ana/app 192.168.1.10 pessoa@example.com ssh servidor-privado snapphoton host.exemplo.local\n");
        self::assertNotNull($clean);
        foreach (['C:\\Users', 'C:/Users', '/home/ana', '192.168.1.10', 'pessoa@example.com', 'servidor-privado', 'snapphoton', 'host.exemplo.local'] as $private) {
            self::assertStringNotContainsString($private, $clean);
        }
        self::assertSame(8, substr_count($clean, '[REDACTED]'));
    }

    public function testArquivoComCredencialSaiDaColetaComAviso(): void
    {
        file_put_contents($this->root . '/sdd/process/01-WORKFLOW.md', "# Fluxo\n\napi_key=segredoMuitoLongo123\n");
        $result = (new SddSource())->collect($this->root);
        self::assertSame([], $result['docs']);
        self::assertCount(1, $result['warnings']);
        self::assertStringContainsString('excluído', $result['warnings'][0]);
    }

    public function testBuilderPublicaSddComLandingMenuLinksELlmsFiltrados(): void
    {
        file_put_contents($this->root . '/sdd/README.md', "# SDD\n\nVisão geral.\n\n[Fluxo](process/01-WORKFLOW.md)\n");
        file_put_contents($this->root . '/sdd/process/01-WORKFLOW.md', "# Fluxo\n\nAcesso 192.168.1.10.\n");
        $templates = $this->root . '/site/resources/pt-br/templates';
        mkdir($templates . '/article', 0777, true);
        mkdir($templates . '/side', 0777, true);
        file_put_contents($templates . '/article/article.html', '<h1>@[[publisher#text#titulo]]@</h1>@[[publisher#html#conteudo]]@');
        file_put_contents($templates . '/side/side.html', '<nav>Menu</nav>');
        $config = [
            'languages' => ['pt-br'], 'base_path' => 'docs/', 'layout' => 'layout',
            'article_template' => 'article', 'menu' => ['id' => 'docs-sidebar', 'template' => 'side'],
            'sdd' => ['enabled' => true],
            'publishers' => ['docs-sdd' => ['sections' => ['sdd'], 'index_path' => 'sdd/', 'index_widget' => 'docs-sdd-index']],
            'labels' => ['pt-br' => ['sections' => ['sdd' => 'SDD']]],
            'site_url' => 'https://conn2flow.com/',
        ];
        $plan = (new DocsBuilder(new DocsTree($this->root), $this->root . '/site', $config))->plan();
        self::assertSame([], $plan['errors']);
        self::assertSame(['pages' => 3, 'publications' => 2, 'landings' => 1], $plan['stats']);
        $pages = json_decode($plan['write'][$this->root . '/site/resources/pt-br/pages.json'], true);
        self::assertContains('docs/sdd/', array_column($pages, 'path'));
        $html = $plan['write'][$this->root . '/site/resources/pt-br/pages/docs-sdd-README/docs-sdd-README.html'];
        self::assertStringContainsString('docs/sdd/process/01-WORKFLOW/', $html);
        $flow = $plan['write'][$this->root . '/site/resources/pt-br/pages/docs-sdd-process-01-WORKFLOW/docs-sdd-process-01-WORKFLOW.html'];
        self::assertStringNotContainsString('192.168.1.10', $flow);
        self::assertStringContainsString('[REDACTED]', $plan['write'][$this->root . '/site/assets/docs/llms-full-pt-br.txt']);
        self::assertStringContainsString('docs/sdd/', $plan['write'][$this->root . '/site/assets/docs/llms-pt-br.txt']);
        $menus = json_decode($plan['write'][$this->root . '/site/resources/pt-br/menus.json'], true);
        self::assertSame('SDD', $menus[0]['fields_schema']['menus']['visible_to_all'][0]['label']);
    }

    public function testCorpusRealNaoContemIdentificadoresPrivadosAposFiltro(): void
    {
        $result = (new SddSource())->collect(CONN2FLOW_ROOT);
        self::assertNotEmpty($result['docs']);
        foreach ($result['docs'] as $rel => $doc) {
            $patterns = [
                'windows path' => '~[A-Z]:[/\\\\]Users[/\\\\]~i',
                'server path' => '~/(?:home|root)/[A-Za-z0-9._-]+~i',
                'IPv4' => '~\b(?:\d{1,3}\.){3}\d{1,3}\b~',
                'email' => '~\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b~i',
                'private project' => '~\b(?:transformamp|snapphoton|lumix|conn2flow-nexus|conn2flow-app)\b~i',
            ];
            foreach ($patterns as $name => $pattern) {
                self::assertSame(0, preg_match($pattern, $doc['body']), "{$rel}: {$name}");
            }
        }
    }
}
