<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * req-154 — contrato mínimo do acervo autoral de templates Tailwind.
 *
 * O runtime lê os recursos do banco, mas a fonte de autoria é `resources/`. Esta guarda impede que
 * um template seja sincronizado sem HTML ou sem o sidecar que sustenta o preview antes do build
 * assíncrono do browser.
 */
final class TemplatesTailwindIntegrityTest extends TestCase
{
    #[DataProvider('idiomas')]
    public function testManifestoEArtefatosTailwindEstaoCompletos(string $idioma): void
    {
        $raiz = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $idioma;
        $manifesto = json_decode((string) file_get_contents($raiz . DIRECTORY_SEPARATOR . 'templates.json'), true);

        self::assertIsArray($manifesto);
        self::assertCount(72, $manifesto, 'O acervo deve manter 72 templates por idioma.');

        foreach ($manifesto as $item) {
            $id = (string) ($item['id'] ?? '');
            self::assertNotSame('', $id);
            $html = $raiz . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $id
                . DIRECTORY_SEPARATOR . $id . '.html';
            $thumbnail = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, (string) ($item['thumbnail'] ?? ''));
            self::assertFileExists($html, $idioma . '/' . $id . ' sem HTML autoral.');
            self::assertNotSame('', trim((string) file_get_contents($html)), $idioma . '/' . $id . ' com HTML vazio.');
            self::assertFileExists($thumbnail, $idioma . '/' . $id . ' sem thumbnail de referência.');
        }

        $tailwind = array_values(array_filter(
            $manifesto,
            static fn(array $item): bool => ($item['framework_css'] ?? '') === 'tailwindcss'
        ));
        self::assertCount(36, $tailwind, 'O acervo deve manter 36 templates Tailwind por idioma.');

        foreach ($tailwind as $item) {
            $id = (string) ($item['id'] ?? '');
            self::assertNotSame('', $id);

            $diretorio = $raiz . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $id;
            $html = $diretorio . DIRECTORY_SEPARATOR . $id . '.html';
            $sidecar = $diretorio . DIRECTORY_SEPARATOR . $id . '.precompiled.css';

            self::assertFileExists($sidecar, $idioma . '/' . $id . ' sem sidecar pré-compilado.');
            $conteudoHtml = (string) file_get_contents($html);
            $conteudoCss = (string) file_get_contents($sidecar);
            self::assertNotSame('', trim($conteudoCss), $idioma . '/' . $id . ' com sidecar vazio.');

            $essenciais = $this->classesEssenciaisUsadas($conteudoHtml);
            $definidas = array_flip($this->classesDefinidas($conteudoCss));
            foreach ($essenciais as $classe) {
                self::assertArrayHasKey(
                    $classe,
                    $definidas,
                    $idioma . '/' . $id . ' não compilou a utility essencial ' . $classe . '.'
                );
            }
        }
    }

    public static function idiomas(): array
    {
        return [['pt-br'], ['en']];
    }

    private function classesEssenciaisUsadas(string $html): array
    {
        preg_match_all('/class\s*=\s*("|\')([^"\']*)\1/i', $html, $atributos, PREG_SET_ORDER);
        $classes = [];

        foreach ($atributos as $atributo) {
            foreach (preg_split('/\s+/', trim($atributo[2])) ?: [] as $classe) {
                $utility = substr($classe, (int) strrpos(':' . $classe, ':'));
                if (preg_match('/^(?:p[trblxy]?|m[trblxy]?|gap(?:-[xy])?|space-[xy]|bg|rounded(?:-[trbl]{1,2})?|shadow)(?:-|$)/', $utility)) {
                    $classes[$classe] = true;
                }
            }
        }

        return array_keys($classes);
    }

    private function classesDefinidas(string $css): array
    {
        preg_match_all('/\.((?:[A-Za-z0-9_-]|\\\\.)+)/', $css, $seletores);
        $classes = [];

        foreach ($seletores[1] ?? [] as $seletor) {
            $classe = preg_replace('/\\\\(.)/', '$1', $seletor);
            if (is_string($classe) && $classe !== '') $classes[$classe] = true;
        }

        return array_keys($classes);
    }
}
