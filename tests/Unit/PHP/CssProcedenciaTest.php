<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-144 (req-141 / CR-002) — procedência e cobertura do CSS derivado.
 *
 * `html`/`css` são AUTORIA; `css_precompiled`/`css_compiled` são DERIVADOS dela. O sistema tratava
 * os quatro como campos independentes, escritos por três produtores em momentos diferentes, e nada
 * registrava de que entrada o CSS tinha sido derivado — então o runtime servia HTML de uma origem
 * com CSS de outra sem emitir erro nenhum.
 *
 * Os dois cenários que estes testes travam são os relatados pelo operador:
 *   - o autor edita o HTML online e o derivado continua o antigo;
 *   - o layout é recompilado no projeto e o derivado da página não acompanha.
 */
final class CssProcedenciaTest extends TestCase
{
    private const HTML = '<div class="flex gap-4"><p class="text-right">a</p></div>';
    private const CSS = '.meu-bloco { color: red; }';
    private const BASELINE = '.flex{display:flex}.gap-4{gap:1rem}';

    private static function entradas(array $sobrescreve = []): array
    {
        return $sobrescreve + [
            'html' => self::HTML,
            'css' => self::CSS,
            'baseline' => self::BASELINE,
            // req-156: versão do Tailwind que gerou o derivado. Fixa aqui para a suíte não depender
            // do registro — o vínculo com o registro é coberto por ParidadeVisualReq156Test.
            'compilador' => '4.3.0',
        ];
    }

    // ===== gestor_css_procedencia_assinatura =====

    public function testAssinaturaEhEstavelParaAsMesmasEntradas(): void
    {
        $a = gestor_css_procedencia_assinatura(self::entradas());
        $b = gestor_css_procedencia_assinatura(self::entradas());

        self::assertSame($a, $b);
        // req-156 levou o algoritmo a `v2` ao acrescentar o compilador às entradas. O prefixo existe
        // justamente para isto: assinatura antiga deixa de casar e o derivado entra para a fila do
        // `css:rebuild`, em vez de ser confundida com divergência real de autoria.
        self::assertStringStartsWith('v2:', $a, 'o prefixo de versão permite trocar o algoritmo depois');
    }

    public function testAssinaturaMudaComQualquerEntrada(): void
    {
        $base = gestor_css_procedencia_assinatura(self::entradas());

        $variacoes = [
            'html' => self::entradas(['html' => self::HTML . '<span class="italic">b</span>']),
            'css autoral' => self::entradas(['css' => self::CSS . ' .outro{}']),
            'baseline (layout recompilado)' => self::entradas(['baseline' => self::BASELINE . '.novo{}']),
            // req-156: derivado gerado por outra major do Tailwind é stale mesmo com a autoria
            // intacta — foi assim que CSS v3 seguiu sendo servido depois da migração para v4.
            'compilador (outra major do Tailwind)' => self::entradas(['compilador' => '3.4.1']),
        ];

        foreach ($variacoes as $rotulo => $entradas) {
            self::assertNotSame($base, gestor_css_procedencia_assinatura($entradas), $rotulo);
        }
    }

    public function testAssinaturaNaoColideAoMoverBytesEntreCampos(): void
    {
        // Sem separador, 'ab'+'c' e 'a'+'bc' dariam a mesma assinatura.
        $a = gestor_css_procedencia_assinatura(['html' => 'ab', 'css' => 'c', 'baseline' => '']);
        $b = gestor_css_procedencia_assinatura(['html' => 'a', 'css' => 'bc', 'baseline' => '']);

        self::assertNotSame($a, $b);
    }

    public function testRecursoSemAutoriaNaoRecebeAssinatura(): void
    {
        // Assinar o vazio faria um registro sem conteúdo parecer íntegro.
        self::assertSame('', gestor_css_procedencia_assinatura(['html' => '', 'css' => '']));
        self::assertSame('', gestor_css_procedencia_assinatura(false));
    }

    // ===== gestor_css_procedencia_valida =====

    public function testDerivadoRecemAssinadoEhCoerente(): void
    {
        $assinatura = gestor_css_procedencia_assinatura(self::entradas());
        self::assertTrue(gestor_css_procedencia_valida($assinatura, self::entradas()));
    }

    public function testEdicaoOnlineDoHtmlTornaODerivadoStale(): void
    {
        $assinatura = gestor_css_procedencia_assinatura(self::entradas());
        $editado = self::entradas(['html' => self::HTML . '<p class="ml-auto">novo</p>']);

        self::assertFalse(gestor_css_procedencia_valida($assinatura, $editado));
    }

    public function testRecompilarOLayoutTornaODerivadoStaleMesmoComHtmlIntacto(): void
    {
        // O cenário que não tinha sinal nenhum: a página não mudou, a cascata sob ela mudou.
        $assinatura = gestor_css_procedencia_assinatura(self::entradas());
        $outroLayout = self::entradas(['baseline' => self::BASELINE . '.text-right{text-align:right}']);

        self::assertFalse(gestor_css_procedencia_valida($assinatura, $outroLayout));
    }

    public function testAssinaturaAusenteContaComoStale(): void
    {
        // Todo o acervo anterior a esta mudança não tem carimbo; tratá-lo como íntegro esconderia
        // exatamente o que o mecanismo existe para revelar.
        self::assertFalse(gestor_css_procedencia_valida('', self::entradas()));
        self::assertFalse(gestor_css_procedencia_valida(null, self::entradas()));
        self::assertFalse(gestor_css_procedencia_valida('   ', self::entradas()));
    }

    public function testAssinaturaDeOutroRecursoNaoValida(): void
    {
        $outra = gestor_css_procedencia_assinatura(self::entradas(['html' => '<div class="grid">x</div>']));
        self::assertFalse(gestor_css_procedencia_valida($outra, self::entradas()));
    }

    // ===== cobertura de classes =====

    public function testClassesUsadasLeOMarkupIgnorandoMarcadoresDeTemplate(): void
    {
        $html = '<div class="flex gap-4"><p class="@[[VAR]]@ {{x}} [[item#y]] text-sm">a</p></div>';
        $usadas = gestor_css_classes_usadas($html);

        sort($usadas);
        self::assertSame(['flex', 'gap-4', 'text-sm'], $usadas);
    }

    public function testClassesDefinidasDesescapamAsUtilitiesDoTailwind(): void
    {
        // Sem desescapar, toda utility com variante apareceria como descoberta.
        $css = '.flex{display:flex}.md\:grid{display:grid}.w-1\/2{width:50%}.hover\:bg-red-500{}';
        $definidas = gestor_css_classes_definidas($css);

        self::assertContains('md:grid', $definidas);
        self::assertContains('w-1/2', $definidas);
        self::assertContains('hover:bg-red-500', $definidas);
    }

    public function testDescobertasSaoApenasAsClassesSemNenhumaRegra(): void
    {
        $html = '<div class="flex md:grid w-1/2 sem-regra outra-sem-regra"></div>';
        $css = '.flex{display:flex}.md\:grid{display:grid}.w-1\/2{width:50%}';

        self::assertSame(['outra-sem-regra', 'sem-regra'], gestor_css_classes_descobertas($html, $css));
    }

    public function testMarcadoresDeVarianteNaoContamComoDescobertas(): void
    {
        // `group`/`peer` existem para que `group-hover:*` e `peer-checked:*` tenham a que se referir;
        // o Tailwind não emite regra própria para elas. Contá-las acusava piora onde nada piorou —
        // foi o que apareceu em 4 páginas do lote de regeneração do transformamp.
        $html = '<div class="group peer"><span class="group-hover:underline">a</span></div>';
        $css = '.group:hover .group-hover\:underline{text-decoration:underline}';

        self::assertSame([], gestor_css_classes_descobertas($html, $css));
    }

    public function testPaginaTotalmenteCobertaNaoReportaNada(): void
    {
        $html = '<div class="flex"></div>';
        self::assertSame([], gestor_css_classes_descobertas($html, '.flex{display:flex}'));
        self::assertSame([], gestor_css_classes_descobertas('', '.flex{display:flex}'));
    }

    // ===== classes embutidas em código (violação da norma de componentes) =====

    public function testDetectaClassesMontadasPorClassList(): void
    {
        // O padrão real do `perfil-usuario.js`: estado visual montado em runtime, invisível para o
        // compilador, que só era coberto declarando o arquivo em `tailwind_sources`.
        $js = "el.classList.add('w-0', 'bg-slate-300');\nel.classList.remove('hidden');";
        $classes = gestor_css_classes_em_codigo($js);

        self::assertSame(['bg-slate-300', 'hidden', 'w-0'], $classes);
    }

    public function testEmToggleApenasOPrimeiroArgumentoEhClasse(): void
    {
        // `toggle(classe, condicao)`: ler o segundo argumento inventava nomes inexistentes — foi
        // assim que `data-perfil-painel` apareceu como se fosse classe.
        $js = "secao.classList.toggle('hidden', secao.getAttribute('data-perfil-painel') !== nome);";
        $classes = gestor_css_classes_em_codigo($js);

        self::assertSame(['hidden'], $classes);
        self::assertNotContains('data-perfil-painel', $classes);
    }

    public function testDetectaClassLiteralEClassName(): void
    {
        $codigo = "echo '<div class=\"flex gap-4\">';\nel.className = 'mt-2 hidden';";
        $classes = gestor_css_classes_em_codigo($codigo);

        foreach (['flex', 'gap-4', 'mt-2', 'hidden'] as $esperada) {
            self::assertContains($esperada, $classes);
        }
    }

    public function testCodigoLimpoNaoAcusaViolacao(): void
    {
        // O `perfil-usuario.php` real está assim: nenhuma classe, só `<a href>` em texto de e-mail.
        $php = "\$html = '<a href=\"' . \$url . '\">contato</a>';";
        self::assertSame([], gestor_css_classes_em_codigo($php));
        self::assertSame([], gestor_css_classes_em_codigo(''));
        self::assertSame([], gestor_css_classes_em_codigo(null));
    }

    public function testClasseDefinidaEmSeletorCompostoContaComoCoberta(): void
    {
        // O Tailwind emite `.md\:flex` isolada, mas o CSS autoral usa seletores compostos.
        $html = '<div class="cartao destaque"></div>';
        $css = '.cartao .destaque{color:red}';

        self::assertSame([], gestor_css_classes_descobertas($html, $css));
    }
}
