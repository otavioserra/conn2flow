<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-096 (BATCH-096) — detecção do motor de exibição PDF.js e montagem dos assets.
 *
 * O core só deve incluir a biblioteca do PDF.js nas páginas que realmente possuem o contênier
 * `.conn2flow-pdfjs` gravado pelo Editor HTML — nunca em todo o site.
 */
final class PdfViewerAssetsTest extends TestCase
{
    public function testDetectaContainerDoPdfJs(): void
    {
        $html = '<div class="conn2flow-pdfjs" data-pdf-src="/contents/manual.pdf" '
            . 'data-pdf-zoom="page-width" style="width:100%;height:600px"></div>';

        self::assertTrue(gestor_pdf_viewer_detectar($html));
    }

    public function testDetectaContainerComOutrasClassesEAspasSimples(): void
    {
        self::assertTrue(gestor_pdf_viewer_detectar(
            "<div class='destaque conn2flow-pdfjs mt-4' data-pdf-src='/a.pdf'></div>"
        ));
        self::assertTrue(gestor_pdf_viewer_detectar(
            '<section><div class="mb-2 conn2flow-pdfjs" data-pdf-src="/a.pdf"></div></section>'
        ));
    }

    public function testNaoDetectaQuandoAPaginaUsaOutroMotorDePdf(): void
    {
        $nativo = '<object class="conn2flow-pdf-object" data="/a.pdf" type="application/pdf"></object>';
        $google = '<iframe class="conn2flow-pdf-google" src="https://docs.google.com/viewer?url=x&embedded=true"></iframe>';

        self::assertFalse(gestor_pdf_viewer_detectar($nativo));
        self::assertFalse(gestor_pdf_viewer_detectar($google));
    }

    public function testNaoDetectaClasseParecidaOuEntradaVazia(): void
    {
        self::assertFalse(gestor_pdf_viewer_detectar('<div class="conn2flow-pdfjs-legacy"></div>'));
        self::assertFalse(gestor_pdf_viewer_detectar('<p>conn2flow-pdfjs</p>'));
        self::assertFalse(gestor_pdf_viewer_detectar(''));
        self::assertFalse(gestor_pdf_viewer_detectar(false));
    }

    public function testAssetsIncluemBibliotecaDaCdnEInicializadorDoCoreComCacheBust(): void
    {
        $assets = gestor_pdf_viewer_assets('https://site.test/', '9.9.9');

        self::assertCount(2, $assets);
        self::assertStringContainsString('cdnjs.cloudflare.com/ajax/libs/pdf.js/', $assets[0]);
        self::assertStringContainsString('pdf.min.js', $assets[0]);
        self::assertSame(
            '<script src="https://site.test/interface/pdf-viewer.js?v=9.9.9"></script>',
            $assets[1]
        );
    }
}
