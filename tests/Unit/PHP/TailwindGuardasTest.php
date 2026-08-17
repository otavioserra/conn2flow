<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('SDD_NO_AUTORUN')) define('SDD_NO_AUTORUN', true);
if (!function_exists('tailwind_recursos_tokens_ausentes')) {
    require_once dirname(__DIR__, 3)
        . DIRECTORY_SEPARATOR . 'gestor'
        . DIRECTORY_SEPARATOR . 'controladores'
        . DIRECTORY_SEPARATOR . 'agents'
        . DIRECTORY_SEPARATOR . 'arquitetura'
        . DIRECTORY_SEPARATOR . 'tailwind-recursos.php';
}

/**
 * Guardas de build do compilador Tailwind — findings F1 e F4c do review de 2026-08-15.
 *
 * As três funções cobertas aqui são puras. Elas existem porque os defeitos que detectam são MUDOS:
 * não quebram o build, não aparecem no console e não geram erro no PHP — o sintoma é CSS que some
 * semanas depois. Sem teste, voltariam a passar despercebidas exatamente como antes.
 */
final class TailwindGuardasTest extends TestCase
{
    // ===== F1 — tokens de @theme podados =========================================================

    public function testAcusaTokenDeTemaUsadoNoCssAutoralEAusenteNaSaida(): void
    {
        // Cenário medido no lumix: `photon-admin.css` referenciava 23 tokens e o bundle trazia 9.
        $autoral = '.badge { border: 2px solid var(--color-photon-accent); color: var(--color-photon-emerald); }';
        $saida = '@layer theme{:root{--color-photon-emerald:rgb(15,110,86);}}';

        self::assertSame(['--color-photon-accent'], tailwind_recursos_tokens_ausentes($autoral, $saida));
    }

    public function testNaoAcusaQuandoTodosOsTokensEstaoNaSaida(): void
    {
        $autoral = '.badge { color: var(--color-photon-accent); }';
        $saida = '@layer theme{:root{--color-photon-accent:rgb(29,158,117);}}';

        self::assertSame([], tailwind_recursos_tokens_ausentes($autoral, $saida));
    }

    public function testIgnoraVariavelDeclaradaPeloProprioCssAutoral(): void
    {
        // `--ha-bg` é do próprio arquivo (padrão do CSS autoral das páginas do photon): não depende
        // do @theme e cobrá-la seria falso positivo.
        $autoral = '.ha { --ha-bg: #050b14; background: var(--ha-bg); }';

        self::assertSame([], tailwind_recursos_tokens_ausentes($autoral, ''));
    }

    public function testIgnoraVariaveisInternasDoTailwindEDeComponente(): void
    {
        $autoral = '.x { opacity: var(--tw-bg-opacity); width: var(--minha-largura); }';

        self::assertSame([], tailwind_recursos_tokens_ausentes($autoral, ''));
    }

    public function testCssAutoralVazioNaoGeraAviso(): void
    {
        self::assertSame([], tailwind_recursos_tokens_ausentes('', '@layer theme{:root{--color-x:red;}}'));
    }

    public function testDevolveTokensOrdenadosESemRepeticao(): void
    {
        $autoral = '.a{color:var(--color-z)}.b{color:var(--color-a)}.c{color:var(--color-z)}';

        self::assertSame(['--color-a', '--color-z'], tailwind_recursos_tokens_ausentes($autoral, ''));
    }

    // ===== F4c(b) — utilities removidas na v4 ====================================================

    public function testAcusaBgOpacityQueDeixouDeExistirNaV4(): void
    {
        // Caso real: o dimmer do componente `form-ui` usava `bg-opacity-50`; o overlay era injetado
        // no DOM e ficava invisível, sem erro em lugar nenhum.
        $html = '<div class="fixed inset-0 bg-gray-500 bg-opacity-50"></div>';

        self::assertSame(['bg-opacity-50'], tailwind_recursos_utilities_removidas($html));
    }

    public function testAcusaFlexShrinkEFlexGrowNumerados(): void
    {
        $html = '<div class="flex-shrink-0"><span class="flex-grow-1"></span></div>';

        self::assertSame(['flex-grow-1', 'flex-shrink-0'], tailwind_recursos_utilities_removidas($html));
    }

    public function testNaoAcusaUtilitiesValidasDaV4(): void
    {
        $html = '<div class="shrink-0 grow bg-gray-500/50 flex items-center gap-2"></div>';

        self::assertSame([], tailwind_recursos_utilities_removidas($html));
    }

    public function testHtmlVazioNaoGeraAviso(): void
    {
        self::assertSame([], tailwind_recursos_utilities_removidas(''));
    }

    // ===== F4c(a) — recurso sem framework_css ====================================================

    public function testDetectaHtmlComUtilitiesTailwind(): void
    {
        $html = '<div class="flex items-center gap-2 px-4"><p class="text-3xl">Oi</p></div>';

        self::assertTrue(tailwind_recursos_html_usa_tailwind($html));
    }

    public function testDetectaUtilityComVariantResponsiva(): void
    {
        self::assertTrue(tailwind_recursos_html_usa_tailwind('<div class="lg:grid-cols-3 gap-4"></div>'));
    }

    public function testNaoConfundeClasseAutoralComUtilityTailwind(): void
    {
        // Classes de projeto (o padrão `.ha-*` das páginas do photon) não podem disparar o aviso,
        // senão ele vira ruído e ninguém mais o lê.
        $html = '<div class="ha ha-card ha-btn-ghost conn2flow-embed"></div>';

        self::assertFalse(tailwind_recursos_html_usa_tailwind($html));
    }

    public function testNaoConfundeFomanticUiComTailwind(): void
    {
        // Calibração medida contra o inventário do core: com `flex`/`grid`/`hidden` isolados na
        // heurística, o aviso disparava em 176 recursos administrativos do Fomantic — `ui grid`,
        // `ui items`, `left floated`. Um aviso que soa sempre é um aviso que ninguém lê.
        $html = '<div class="ui grid"><div class="left floated column"><div class="ui items">x</div></div></div>';

        self::assertFalse(tailwind_recursos_html_usa_tailwind($html));
    }

    public function testUmaUtilitySozinhaNaoBastaParaAcusar(): void
    {
        // O limiar é DUAS utilities distintas com valor: uma só aparece por acidente em markup
        // legado e não sustenta a conclusão de que o recurso é Tailwind.
        self::assertFalse(tailwind_recursos_html_usa_tailwind('<div class="ui button mt-2">x</div>'));
        self::assertTrue(tailwind_recursos_html_usa_tailwind('<div class="mt-2 px-4">x</div>'));
    }

    public function testHtmlSemClasseNaoDisparaAviso(): void
    {
        self::assertFalse(tailwind_recursos_html_usa_tailwind('<div><p>Sem classes</p></div>'));
    }

    // ===== F3 — layout com variante responsiva de display ========================================
    //
    // Caso medido na Busca Clínica: `.hidden` de um sidecar posterior anulava o `lg:flex` do layout
    // e o menu invertia — sidebar sumindo no desktop, hambúrguer aparecendo nele. O build não
    // avisava, e o sintoma levava a suspeitar do markup, que estava correto.

    public function testAcusaLayoutComDisplayResponsivoEConcorrenteIncondicional(): void
    {
        $css = '.hidden{display:none}@media (min-width:64rem){.lg\:flex{display:flex}}';

        self::assertTrue(tailwind_recursos_layout_display_sensivel($css));
    }

    public function testNaoAcusaLayoutSemVarianteResponsivaDeDisplay(): void
    {
        $css = '.hidden{display:none}.flex{display:flex}@media (min-width:64rem){.lg\:p-4{padding:1rem}}';

        self::assertFalse(tailwind_recursos_layout_display_sensivel($css));
    }

    public function testNaoAcusaLayoutSemConcorrenteIncondicional(): void
    {
        // Sem um `display` fora de media query não há o que sobrescrever a variante.
        $css = '@media (min-width:64rem){.lg\:flex{display:flex}}';

        self::assertFalse(tailwind_recursos_layout_display_sensivel($css));
    }

    public function testCssVazioNaoAcusa(): void
    {
        self::assertFalse(tailwind_recursos_layout_display_sensivel(''));
    }
}
