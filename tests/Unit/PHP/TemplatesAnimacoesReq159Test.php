<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-160 (req-159) — animações de entrada dos templates de sessão.
 *
 * Quatro templates aplicavam `animate-fade-in-up` sem que o token `--animate-fade-in-up` existisse
 * em contrato nenhum. No Tailwind v4 a classe `animate-<nome>` só é gerada quando o token existe:
 * sem ele a utility é descartada em SILÊNCIO na compilação — o template parece correto no código e
 * não anima na tela. Medido em Chromium antes da correção: `animation-name: none`, duração `0s`.
 *
 * O modo de falha é o que torna a guarda necessária. Nenhum dos elementos afetados tinha `opacity-0`
 * junto, então eles apareciam normalmente e o defeito passou despercebido; se algum tivesse, o
 * conteúdo ficaria invisível para sempre, porque a animação que o traria de volta não existe.
 */
final class TemplatesAnimacoesReq159Test extends TestCase
{
    private const RAIZ = __DIR__ . '/../../../';
    private const IDIOMAS = ['pt-br', 'en'];

    /** Utilities de animação nativas do Tailwind: não dependem de token no `@theme`. */
    private const NATIVAS = ['animate-none', 'animate-spin', 'animate-ping', 'animate-pulse', 'animate-bounce'];

    private static function contratoCentral(): string
    {
        return (string)file_get_contents(self::RAIZ . 'gestor/assets/tailwindcss/system-input.css');
    }

    /** @return list<array{lang:string,id:string,html:string,css:string}> */
    private static function templatesTailwind(): array
    {
        $itens = [];

        foreach (self::IDIOMAS as $lang) {
            $dir = self::RAIZ . 'gestor/resources/' . $lang . '/templates';
            if (!is_dir($dir)) continue;

            foreach (scandir($dir) ?: [] as $id) {
                if ($id === '.' || $id === '..') continue;
                // Templates Fomantic não passam pelo pipeline do Tailwind.
                if (str_ends_with($id, '-fomantic-ui')) continue;

                $html = $dir . '/' . $id . '/' . $id . '.html';
                $css = $dir . '/' . $id . '/' . $id . '.precompiled.css';
                if (!is_file($html)) continue;

                $itens[] = [
                    'lang' => $lang,
                    'id' => $id,
                    'html' => (string)file_get_contents($html),
                    'css' => is_file($css) ? (string)file_get_contents($css) : '',
                ];
            }
        }

        return $itens;
    }

    /** @return list<string> Classes `animate-*` aplicadas em atributos `class`. */
    private static function animacoesUsadas(string $html): array
    {
        $classes = [];

        if (preg_match_all('/class\s*=\s*["\']([^"\']+)["\']/', $html, $blocos)) {
            foreach ($blocos[1] as $bloco) {
                foreach (preg_split('/\s+/', $bloco) ?: [] as $classe) {
                    if (str_starts_with($classe, 'animate-')) $classes[$classe] = true;
                }
            }
        }

        return array_keys($classes);
    }

    public function testContratoCentralDefineOsTokensDeAnimacaoUsadosPelosTemplates(): void
    {
        $contrato = self::contratoCentral();

        self::assertStringContainsString('--animate-fade-in-up:', $contrato);
        self::assertStringContainsString('@keyframes fade-in-up', $contrato);
        self::assertStringContainsString('--animate-fade-in:', $contrato);
        self::assertStringContainsString('@keyframes fade-in', $contrato);
    }

    public function testContratoDoNavegadorHerdaOsMesmosTokens(): void
    {
        $caminho = self::RAIZ . 'gestor/assets/tailwindcss/browser-contract.css';
        self::assertFileExists($caminho, 'contrato do navegador não foi derivado');

        // `tailwind_recursos_browser_contract()` deriva este arquivo do contrato central. Se ele não
        // herdar o token, o editor visual e a Editbar animam diferente do build offline — que é a
        // classe de divergência tratada no BATCH-158.
        $browser = (string)file_get_contents($caminho);
        self::assertStringContainsString('--animate-fade-in-up:', $browser);
        self::assertStringContainsString('@keyframes fade-in-up', $browser);
    }

    /**
     * A guarda central: nenhuma classe `animate-*` de template pode ficar sem regra compilada.
     */
    public function testTodaAnimacaoUsadaEmTemplateTemRegraNoSidecar(): void
    {
        $semRegra = [];

        foreach (self::templatesTailwind() as $t) {
            foreach (self::animacoesUsadas($t['html']) as $classe) {
                if (in_array($classe, self::NATIVAS, true) && $t['css'] === '') continue;

                // O Tailwind escapa o seletor a partir do nome da classe.
                $seletor = '.' . str_replace([':', '/', '[', ']', '.'], ['\\:', '\\/', '\\[', '\\]', '\\.'], $classe);

                // Uma definição local no próprio HTML também resolve (é o caso do `sessao-com-abas`).
                $noHtml = str_contains($t['html'], '.' . $classe);

                if (!str_contains($t['css'], $seletor) && !$noHtml) {
                    $semRegra[] = $t['lang'] . '/' . $t['id'] . ' -> ' . $classe;
                }
            }
        }

        self::assertSame(
            [],
            $semRegra,
            "classe de animação sem regra compilada (não anima, e o template parece correto no código):\n  "
            . implode("\n  ", $semRegra)
        );
    }

    public function testOsTemplatesRelatadosSeguemUsandoAAnimacaoDeEntrada(): void
    {
        $esperados = [
            'sessao-contato-mapa',
            'sessao-contato-mapa-alternativo',
            'sessao-galeria-masonry',
            'sessao-newsletter-minimalista',
        ];

        foreach (self::IDIOMAS as $lang) {
            foreach ($esperados as $id) {
                $css = self::RAIZ . 'gestor/resources/' . $lang . '/templates/' . $id . '/' . $id . '.precompiled.css';
                self::assertFileExists($css, "{$lang}/{$id} sem sidecar");

                // Trava a correção: antes da req-159 o sidecar não tinha regra alguma para a classe.
                self::assertStringContainsString(
                    '.animate-fade-in-up',
                    (string)file_get_contents($css),
                    "{$lang}/{$id}: sidecar sem a regra de animate-fade-in-up"
                );
            }
        }
    }
}
