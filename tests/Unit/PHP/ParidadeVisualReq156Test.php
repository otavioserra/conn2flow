<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * BATCH-158 (req-156) — paridade visual entre página pública, pré-visualizador e editor visual.
 *
 * Dois problemas, cada um com a sua guarda:
 *
 *   1. O EDITOR VISUAL carregava a folha do Fomantic sem camada. O BATCH-156 tirou essa folha do
 *      preview e não do editor, e a assimetria criou o "ambiente intermediário" do relato: medido em
 *      Chromium, o título caía de 72px para 24px, o peso de 900 para 700 e o texto do CTA de branco
 *      para rgb(65,131,196). Além do conflito de cascata, `html{font-size:14px}` do Fomantic
 *      encolhia TODA medida `rem` do Tailwind por 14/16 = 0,875.
 *
 *   2. Os iframes do editor, a Editbar e os previews de widget montam as tags no CLIENTE, e por isso
 *      escaparam do inventário do BATCH-146: seguiam presos a `unpkg.com` e `cdnjs.com` com versões
 *      escritas à mão, paralelas ao registro (DEC-122).
 */
final class ParidadeVisualReq156Test extends TestCase
{
    private const RAIZ = __DIR__ . '/../../../';

    private const HOSTS_CDN = [
        'cdn.jsdelivr.net',
        'cdnjs.cloudflare.com',
        'unpkg.com',
        'ajax.googleapis.com',
        'code.jquery.com',
    ];

    /** Arquivos que montam tags de terceiros no cliente e por isso escaparam do BATCH-146. */
    private const CLIENTES_SEM_CDN = [
        'gestor/assets/interface/html-editor-interface.js',
        'gestor/modulos/dashboard/dashboard.toolbar.js',
        'gestor/modulos/publisher-index/publisher-index.js',
        'gestor/modulos/pages-index/pages-index.js',
        'gestor/modulos/publisher-highlights/publisher-highlights.js',
        'gestor/modulos/menus/menus.js',
        'gestor/modulos/galleries/galleries.js',
    ];

    private static function ler(string $relativo): string
    {
        $caminho = self::RAIZ . $relativo;
        self::assertFileExists($caminho, "arquivo ausente: {$relativo}");

        return (string)file_get_contents($caminho);
    }

    private static function registro(): array
    {
        require_once self::RAIZ . 'gestor/bibliotecas/assets-externos.php';

        return assets_externos_registro();
    }

    // ===== Registro de assets =====

    public function testCompiladorTailwindDoBrowserEstaNoRegistro(): void
    {
        $registro = self::registro();

        // Era o último ponto do gestor preso a `unpkg.com`, com a versão repetida em sete lugares.
        self::assertArrayHasKey('tailwindcss-browser', $registro);
        self::assertSame(['dist/index.global.js'], $registro['tailwindcss-browser']['js']);
        self::assertNotSame('', (string)$registro['tailwindcss-browser']['versao']);
    }

    public function testAddonsDoCodeMirrorUsadosPeloEditorEstaoNoRegistro(): void
    {
        $codemirror = self::registro()['codemirror']['js'];

        // Só o iframe do editor os usava, então nunca passaram pelo registro. Sem eles, migrar o
        // iframe para o disco os deixaria caindo no CDN em silêncio.
        self::assertContains('addon/edit/closetag.js', $codemirror);
        self::assertContains('addon/edit/closebrackets.js', $codemirror);
    }

    public function testTodoArquivoDoRegistroUsadoPeloEditorExisteNoDisco(): void
    {
        $registro = self::registro();
        $vendor = self::RAIZ . 'gestor/assets/vendor/';

        foreach (['jquery', 'fomantic-ui', 'codemirror', 'quill', 'tailwindcss-browser'] as $nome) {
            $lib = $registro[$nome];
            $arquivos = array_merge((array)($lib['css'] ?? []), (array)($lib['js'] ?? []));

            foreach ($arquivos as $arquivo) {
                // `assets_externos_url()` cai no CDN sem avisar quando o local falta; a suíte
                // transforma esse silêncio em falha (DEC-122).
                self::assertFileExists(
                    $vendor . $nome . '/' . $lib['versao'] . '/' . $arquivo,
                    "asset ausente no disco: {$nome}/{$arquivo}"
                );
            }
        }
    }

    public function testMapaDeUrlsResolveDoDiscoQuandoOArquivoExiste(): void
    {
        require_once self::RAIZ . 'gestor/bibliotecas/assets-externos.php';

        $mapa = assets_externos_urls_map(
            ['tailwindcss-browser'],
            self::RAIZ . 'gestor/assets/vendor/',
            '/vendor/'
        );

        self::assertSame(
            '/vendor/tailwindcss-browser/4.3.0/dist/index.global.js',
            $mapa['tailwindcss-browser']['dist/index.global.js']
        );
    }

    public function testMapaCaiNoCdnApenasQuandoOArquivoLocalNaoExiste(): void
    {
        require_once self::RAIZ . 'gestor/bibliotecas/assets-externos.php';

        $mapa = assets_externos_urls_map(['tailwindcss-browser'], '/diretorio/inexistente/', '/vendor/');

        // O fallback continua existindo — o que não pode é ele ser o único caminho, que era o
        // estado antes do BATCH-146.
        self::assertStringContainsString('unpkg.com', $mapa['tailwindcss-browser']['dist/index.global.js']);
    }

    // ===== Ausência de CDN escrito no código =====

    #[DataProvider('clientesQueMontamTags')]
    public function testClienteNaoCarregaBibliotecaDeCdnEscritoNoCodigo(string $relativo): void
    {
        $conteudo = self::ler($relativo);

        foreach (self::HOSTS_CDN as $host) {
            self::assertStringNotContainsString(
                'https://' . $host,
                $conteudo,
                "{$relativo} ainda carrega biblioteca de {$host} em vez do registro de assets"
            );
        }
    }

    public static function clientesQueMontamTags(): array
    {
        return array_map(static fn ($f) => [$f], self::CLIENTES_SEM_CDN);
    }

    public function testBackendPublicaOMapaDeUrlsParaOJavascript(): void
    {
        // Sem isto no objeto `gestor`, `window.gestorAssets.url()` devolve '' e as tags nascem vazias.
        self::assertStringContainsString('assets_externos_urls_js', self::ler('gestor/gestor.php'));
        self::assertStringContainsString("'assetsUrls'", self::ler('gestor/gestor.php'));
        self::assertStringContainsString('assetsUrls', self::ler('gestor/assets/global/global.js'));
    }

    public function testVersaoDoTailwindBrowserSaiDoRegistroENaoDeUmLiteral(): void
    {
        $htmlEditor = self::ler('gestor/bibliotecas/html-editor.php');

        // O editor, a Editbar e o build offline precisam da MESMA versão: uma cópia a mais é uma
        // que envelhece sozinha.
        self::assertStringContainsString('html_editor_assets_registro()', $htmlEditor);
        self::assertStringContainsString("registro['tailwindcss-browser']['versao']", $htmlEditor);
    }

    // ===== Isolamento do chrome do editor =====

    public function testEditorVisualIsolaOFomanticEmCamadaERestauraAUnidadeRem(): void
    {
        $js = self::ler('gestor/assets/interface/html-editor-interface.js');
        $inicio = strpos($js, 'function htmlEditorVisualFrameworkIncludes(');
        self::assertNotFalse($inicio);
        $bloco = substr($js, $inicio, 1400);

        self::assertStringContainsString('layer(${HTML_EDITOR_CHROME_LAYER})', $bloco);
        self::assertStringContainsString('html{font-size:16px}', $bloco);
    }

    public function testEditorVisualNaoInjetaMaisAFolhaSemCamada(): void
    {
        $js = self::ler('gestor/assets/interface/html-editor-interface.js');
        $inicio = strpos($js, 'function editorHtmlVisualConteudo(');
        $fim = strpos($js, 'function htmlEditorRenderVars(', (int)$inicio);
        $editor = substr($js, (int)$inicio, (int)$fim - (int)$inicio);

        // A regressão exata do relato: o `<link>` cru que o BATCH-156 tirou do preview e deixou aqui.
        self::assertDoesNotMatchRegularExpression('/<link[^>]+semantic\.min\.css/', $editor);
    }

    // ===== Procedência do CSS derivado =====

    public function testAssinaturaDeProcedenciaConsideraOCompilador(): void
    {
        require_once self::RAIZ . 'gestor/bibliotecas/gestor.php';

        $base = ['html' => '<div class="flex"></div>', 'css' => '', 'baseline' => '.flex{display:flex}'];

        $v4 = gestor_css_procedencia_assinatura($base + ['compilador' => '4.3.0']);
        $v3 = gestor_css_procedencia_assinatura($base + ['compilador' => '3.4.1']);

        // O build offline sempre carimbou a versão no seu fingerprint; o derivado gravado ONLINE
        // não carregava essa identidade, e um CSS de outra major passava por íntegro.
        self::assertNotSame($v4, $v3);
        self::assertStringStartsWith('v2:', $v4);
    }

    public function testAssinaturaAntigaDeixaDeCasarParaForcarRecompilacao(): void
    {
        require_once self::RAIZ . 'gestor/bibliotecas/gestor.php';

        $entradas = [
            'html' => '<div class="flex"></div>',
            'css' => '',
            'baseline' => '.flex{display:flex}',
            'compilador' => '4.3.0',
        ];

        // `v1` não incluía o compilador. É de propósito que ele não valide mais: o acervo carimbado
        // sob a major anterior precisa ser reprocessado pelo `css:rebuild`, sem migração de dados.
        $v1 = 'v1:' . sha1(implode("\x1f", [sha1($entradas['html']), sha1($entradas['css']), sha1($entradas['baseline'])]));

        self::assertFalse(gestor_css_procedencia_valida($v1, $entradas));
        self::assertTrue(gestor_css_procedencia_valida(gestor_css_procedencia_assinatura($entradas), $entradas));
    }

    public function testConsumidoresDaProcedenciaInformamOCompilador(): void
    {
        // Se o gravador carimba com o compilador e o leitor recalcula sem ele, TODO recurso fica
        // permanentemente stale e a auditoria nunca zera.
        foreach ([
            'gestor/controladores/agents/arquitetura/css-regenerar.php',
            'gestor/controladores/agents/arquitetura/css-auditoria.php',
            'gestor/modulos/publisher-pages/publisher-pages.php',
        ] as $relativo) {
            self::assertStringContainsString(
                'gestor_css_compilador_versao()',
                self::ler($relativo),
                "{$relativo} recalcula a procedência sem informar o compilador"
            );
        }
    }
}
