<?php

declare(strict_types=1);

use Conn2Flow\Cli\Support\Docs\SddSource;
use Conn2Flow\Cli\Support\Docs\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/SddSource.php';
require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/DocsTheme.php';
require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/MarkdownRenderer.php';

/**
 * REQ-303 — `<link rel="canonical">` e largura/altura/tipo da imagem OpenGraph.
 *
 * Cobre as funções PURAS de `gestor/bibliotecas/gestor.php` usadas pelo montador do `<head>`.
 */
final class CanonicalOpenGraphImageReq303Test extends TestCase
{
    private const BASE = 'https://conn2flow.local/';

    private function canonica(string $caminho, string $idioma = 'pt-br'): string
    {
        return gestor_canonical_url([
            'base' => self::BASE,
            'path' => $caminho,
            'language' => $idioma,
            'default_language' => 'pt-br',
        ]);
    }

    // ===== Canonical

    public function testHomeDoIdiomaPadraoNaoGanhaBarraDupla(): void
    {
        self::assertSame('https://conn2flow.local/', $this->canonica(''));
        self::assertSame('https://conn2flow.local/', $this->canonica('/'));
    }

    public function testIdiomaPadraoPerdeOPrefixoEOsOutrosMantem(): void
    {
        // `/pt-br/docs/` e `/docs/` são a mesma página; o roteador já tirou o prefixo do caminho.
        self::assertSame('https://conn2flow.local/docs/', $this->canonica('docs/'));
        self::assertSame('https://conn2flow.local/en/', $this->canonica('', 'en'));
        self::assertSame('https://conn2flow.local/en/docs/', $this->canonica('docs/', 'en'));
    }

    public function testRemoveQuerystringFragmentoEBarrasRepetidas(): void
    {
        self::assertSame('https://conn2flow.local/docs/', $this->canonica('docs/?utm_source=x&page=2'));
        self::assertSame('https://conn2flow.local/docs/mdd/', $this->canonica('docs//mdd#topo'));
    }

    public function testCanonicalUsaAGrafiaDoRegistroEmVezDaRequisicao(): void
    {
        global $_GESTOR;
        $anterior = $_GESTOR;
        try {
            $_GESTOR['url-full-http-sem-lang'] = self::BASE;
            $_GESTOR['linguagem-codigo'] = $_GESTOR['linguagem-padrao'] = 'pt-br';
            $_GESTOR['pagina#caminho'] = 'docs/';
            foreach (['docs/', 'Docs/', 'DOCS/'] as $caminho) {
                $_GESTOR['caminho-total'] = $caminho;
                self::assertSame(self::BASE . 'docs/', gestor_canonical_dados());
            }

            $_GESTOR['linguagem-codigo'] = 'en';
            self::assertSame(self::BASE . 'en/docs/', gestor_canonical_dados());
            $_GESTOR['linguagem-codigo'] = 'pt-br';
            $_GESTOR['caminho-total'] = 'docs/mdd/implementation/batch-311/';
            $_GESTOR['pagina#caminho'] = 'docs/mdd/implementation/BATCH-311/';
            self::assertSame(self::BASE . 'docs/mdd/implementation/BATCH-311/', gestor_canonical_dados());
            $_GESTOR['pagina#caminho'] = '';
            self::assertSame(self::BASE, gestor_canonical_dados());
        } finally {
            $_GESTOR = $anterior;
        }
    }

    public function testBarraFinalConsistenteMenosEmArquivo(): void
    {
        self::assertSame('https://conn2flow.local/apresentacoes/conn2flow-widget/', $this->canonica('apresentacoes/conn2flow-widget'));
        self::assertSame('https://conn2flow.local/sitemap.xml', $this->canonica('sitemap.xml'));
        // req-188: com barra é página, mesmo parecendo extensão.
        self::assertSame('https://conn2flow.local/docs/whats-new/2.10/', $this->canonica('docs/whats-new/2.10/'));
    }

    public function testSemBaseValidaNaoEmiteNada(): void
    {
        self::assertSame('', gestor_canonical_url(['base' => '', 'path' => 'docs/']));
        self::assertSame('', gestor_canonical_url(['base' => '/', 'path' => 'docs/']));
        self::assertSame('', gestor_canonical_url(false));
    }

    public function testDetectaCanonicalJaPresente(): void
    {
        self::assertTrue(gestor_canonical_existe('<link rel="canonical" href="https://x/">'));
        self::assertTrue(gestor_canonical_existe(["<meta charset=\"utf-8\">", "<LINK href='https://x/' REL='canonical'>"]));
        self::assertTrue(gestor_canonical_existe('<link rel=canonical href=https://x/>'));
        self::assertFalse(gestor_canonical_existe('<link rel="icon" href="/favicon.png">'));
        self::assertFalse(gestor_canonical_existe('<a rel="nofollow" href="/canonical/">x</a>'));
        self::assertFalse(gestor_canonical_existe(''));
    }

    // ===== Dimensões da imagem OpenGraph

    public function testTagsDaImagemVemLogoDepoisDeOgImage(): void
    {
        $tags = gestor_open_graph_tags([
            'title' => 'Conn2Flow',
            'image' => 'https://conn2flow.local/assets/site/conn2flow-social-cover.webp',
            'image_width' => '1200',
            'image_height' => '630',
            'image_type' => 'image/webp',
            'url' => 'https://conn2flow.local/',
        ]);

        $props = array_map(static fn (string $t): string => preg_match('/(?:property|name)="([^"]+)"/', $t, $m) ? $m[1] : '', $tags);
        $i = array_search('og:image', $props, true);

        self::assertNotFalse($i);
        self::assertSame(['og:image:width', 'og:image:height', 'og:image:type'], array_slice($props, $i + 1, 3));
        self::assertContains('<meta property="og:image:width" content="1200">', $tags);
        self::assertContains('<meta property="og:image:height" content="630">', $tags);
        self::assertContains('<meta property="og:image:type" content="image/webp">', $tags);
        self::assertContains('<meta name="twitter:card" content="summary_large_image">', $tags);
        self::assertSame(count($props), count(array_unique($props)), 'Nenhuma tag duplicada');
    }

    public function testSemImagemNaoEmiteLarguraAlturaNemTipo(): void
    {
        $tags = implode("\n", gestor_open_graph_tags([
            'title' => 'Conn2Flow',
            'image_width' => '1200',
            'image_height' => '630',
            'image_type' => 'image/webp',
        ]));

        self::assertStringNotContainsString('og:image', $tags);
        self::assertStringContainsString('summary"', $tags);
    }

    public function testResolveArquivoLocalComOuSemPrefixoDeIdioma(): void
    {
        $raizes = ['assets/' => '/srv/gestor/assets/', 'files/' => '/srv/gestor/contents/files/'];
        $idiomas = ['pt-br', 'en'];

        foreach ([
            'https://conn2flow.local/assets/site/capa.webp',
            'https://conn2flow.local/en/assets/site/capa.webp',
            '/pt-br/assets/site/capa.webp',
            'assets/site/capa.webp?v=3',
        ] as $url) {
            self::assertSame('/srv/gestor/assets/site/capa.webp', gestor_open_graph_imagem_arquivo($url, self::BASE, $raizes, $idiomas), $url);
        }

        self::assertSame('/srv/gestor/contents/files/2026/11/x.png', gestor_open_graph_imagem_arquivo('https://conn2flow.local/files/2026/11/x.png', self::BASE, $raizes, $idiomas));
        self::assertSame('/srv/gestor/assets/a.webp', gestor_open_graph_imagem_arquivo('https://h.test/sub/assets/a.webp', 'https://h.test/sub/', $raizes, $idiomas));
    }

    public function testRecusaImagemDeOutroHostForaDasRaizesOuComTravessia(): void
    {
        $raizes = ['assets/' => '/srv/gestor/assets/'];

        self::assertSame('', gestor_open_graph_imagem_arquivo('https://cdn.exemplo.com/assets/x.webp', self::BASE, $raizes));
        self::assertSame('', gestor_open_graph_imagem_arquivo('https://conn2flow.local/outra/x.webp', self::BASE, $raizes));
        self::assertSame('', gestor_open_graph_imagem_arquivo('https://conn2flow.local/assets/../config.php', self::BASE, $raizes));
        self::assertSame('', gestor_open_graph_imagem_arquivo('/assets/%2e%2e/config.php', self::BASE, $raizes));
        self::assertSame('', gestor_open_graph_imagem_arquivo('', self::BASE, $raizes));
    }

    public function testLeDimensoesETipoDeUmaImagemReal(): void
    {
        // Cabeçalho WebP estendido (VP8X) com a tela 1200×630: basta para o `getimagesize`,
        // sem depender do GD com WebP (ausente no PHP do Windows).
        $vp8x = 'VP8X' . pack('V', 10) . "\x00\x00\x00\x00"
            . substr(pack('V', 1200 - 1), 0, 3) . substr(pack('V', 630 - 1), 0, 3);
        $corpo = 'WEBP' . $vp8x;
        $arquivo = tempnam(sys_get_temp_dir(), 'og303');
        file_put_contents($arquivo, 'RIFF' . pack('V', strlen($corpo)) . $corpo);

        try {
            self::assertSame(['width' => 1200, 'height' => 630, 'type' => 'image/webp'], gestor_open_graph_imagem_info($arquivo));
        } finally {
            @unlink($arquivo);
        }

        self::assertSame([], gestor_open_graph_imagem_info(__FILE__), 'Arquivo que não é imagem');
        self::assertSame([], gestor_open_graph_imagem_info('/caminho/que/nao/existe.webp'));
    }

    // ===== Docs da memória: link para caminho privado vira texto

    public function testLinkParaArquivoLocalViraSoOTexto(): void
    {
        $md = "Veja [gestor.php](file:///C:/Users/fulano/GIT/conn2flow/gestor/gestor.php#L10) e "
            . "[MODIFY] [interface.php](file:///c:/Users/fulano/x/interface.php).";
        $saida = (new SddSource())->filter($md);

        self::assertSame('Veja gestor.php e [MODIFY] interface.php.', $saida);
    }

    public function testLinkComDestinoOcultadoViraTextoEOTextoOcultoContinua(): void
    {
        self::assertSame('ver [REDACTED] agora', SddSource::unlinkPrivateTargets('ver [[REDACTED]](file:///[REDACTED]) agora'));
        self::assertSame('painel do Lab', SddSource::unlinkPrivateTargets('[painel do Lab](https://[REDACTED]/admin/)'));
        self::assertSame('capa', SddSource::unlinkPrivateTargets('![capa](<file:///[REDACTED]> "t")'));
        self::assertSame('código `[REDACTED]` fica', SddSource::unlinkPrivateTargets('código `[REDACTED]` fica'));
        self::assertSame('(ex.: [REDACTED]).', SddSource::unlinkPrivateTargets('(ex.: https://[REDACTED].local/).'));
    }

    public function testLinksPublicosERelativosNaoMudam(): void
    {
        $md = '[GitHub](https://github.com/conn2flow/x) [doc](../modules/menus.md) [ancora](#topo)';
        self::assertSame($md, SddSource::unlinkPrivateTargets($md));
        self::assertSame($md, (new SddSource())->filter($md));
    }

    public function testRenderizaReferenciasPrivadasComoTextoEPreservaAsPublicas(): void
    {
        $markdown = <<<'MD'
[**Guia interno**][PRIVATE], [private][], [private] e ![Imagem][private].

[Guia público][public]

[private]: <https://lab.exemplo.local/guia> 'Interno'
[public]: https://github.com/conn2flow/conn2flow 'Público'
MD;
        $html = $this->renderDocs($markdown);
        self::assertMatchesRegularExpression('~<strong[^>]*>Guia interno</strong>, private, private e Imagem\.~', $html);
        self::assertStringContainsString('href="https://github.com/conn2flow/conn2flow"', $html);
        self::assertSame(1, substr_count($html, '<a '));
        self::assertStringNotContainsString('<img ', $html);
        self::assertStringNotContainsString('[REDACTED]', $html);
    }

    public function testRenderizaLinkInlinePrivadoComTituloSimplesEParentesesComoTexto(): void
    {
        $markdown = "Veja [guia](file:///C:/Users/fulano/guia(1).md 'Interno') e "
            . "[GitHub](https://github.com/conn2flow/conn2flow 'Público').";
        $html = $this->renderDocs($markdown);
        self::assertStringContainsString('Veja guia e <a ', $html);
        self::assertStringContainsString('title="Público"', $html);
        self::assertSame(1, substr_count($html, '<a '));
        self::assertStringNotContainsString('[REDACTED]', $html);
    }

    private function renderDocs(string $markdown): string
    {
        $renderer = new MarkdownRenderer([], static fn (string $href): string => $href);

        return $renderer->render((new SddSource())->filter($markdown))['html'];
    }
}
