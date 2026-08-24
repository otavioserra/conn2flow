<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'modelo.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'html-editor.php';

/**
 * req-127 (BATCH-129) — extrator semântico de tokens de tema para o Assistente de IA.
 *
 * O Assistente recebia `{{framework_css}}` = `tailwindcss` e nada mais sobre a marca do projeto, e o
 * modelo respondia com `bg-red-600` onde o projeto tem `bg-mp-red`. Mandar o `browser-contract.css`
 * bruto resolveria e custaria 78 KB por interação — o `transformamp` tem 2.255 linhas de contrato,
 * quase todas dentro de `@layer components`, mais SVGs inteiros embutidos em `data:image/svg+xml`.
 *
 * O que decide o resultado é puro (uma string entra, uma string sai), então é o que estes casos
 * alcançam. Os fixtures replicam a FORMA dos contratos reais em vez de lê-los do disco: os arquivos
 * de projeto vivem em `dev-environment/data/sites/` e não existem no CI.
 */
final class HtmlEditorIaThemeTokensTest extends TestCase
{
    /** Contrato sintético com a forma do `transformamp`: tema + asset embutido + componentes. */
    private function contrato(): string
    {
        return <<<'CSS'
/* ============================================================
   CONTRATO DE COMPILAÇÃO
   `@theme static` é obrigatório: sem ele o bundle sai sem --color-mp-coral.
   ============================================================ */

@theme static {
    /* ====== Paleta institucional ====== */
    --color-mp-red: #ff010b;
    --color-mp-gold: #c89d58;
    --color-mp-paper: #f4ead6;

    --font-sans: "Inter", sans-serif;

    /* Silhuetas usadas como mask-image: não viram utility e pesam centenas de bytes. */
    --art-pillar-mask: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='220' height='320'%3E%3Cpath d='M30 30h160M40 50h140'/%3E%3C/svg%3E");
}

@layer base {
    body { background: var(--color-mp-paper); }
}

@layer components {
    .tm-shell {
        width: 100%;
        max-width: 80rem;
    }

    /* ----- Header ----- */

    .tm-nav-link {
        display: inline-flex;
        padding-block: 0.4rem;
    }

    .tm-nav-link::after {
        content: "";
        height: 2px;
    }

    .tm-nav-link:hover {
        color: var(--color-mp-red);
    }
}
CSS;
    }

    // ===== Declarações de tema

    public function testExtraiAsCoresDoThemeStatic(): void
    {
        $saida = html_editor_ia_tokens_tema_compilar($this->contrato());

        self::assertStringContainsString('--color-mp-red: #ff010b;', $saida);
        self::assertStringContainsString('--color-mp-gold: #c89d58;', $saida);
        self::assertStringContainsString('--font-sans: "Inter", sans-serif;', $saida);
    }

    public function testDescartaAssetEmbutidoEmDataUri(): void
    {
        // É o que estoura o orçamento: uma `--art-*-mask` sozinha passa de 700 bytes e não vira
        // utility nenhuma, então não ensina nada ao modelo.
        $saida = html_editor_ia_tokens_tema_compilar($this->contrato());

        self::assertStringNotContainsString('data:image/svg+xml', $saida);
        self::assertStringNotContainsString('--art-pillar-mask', $saida);
    }

    public function testDescartaNamespaceQueNaoViraUtility(): void
    {
        $saida = html_editor_ia_tokens_tema_compilar("@theme {\n--brand-motion-curve: cubic-bezier(0.2, 0, 0, 1);\n--color-x: #fff;\n}");

        self::assertStringContainsString('--color-x', $saida);
        self::assertStringNotContainsString('--brand-motion-curve', $saida);
    }

    public function testAceitaThemeSemStatic(): void
    {
        // O contrato do `conn2flow-site` abre com `@theme {`, sem o modificador.
        $saida = html_editor_ia_tokens_tema_compilar("@theme {\n--color-c2f-blue: rgb(29 170 198);\n}");

        self::assertStringContainsString('--color-c2f-blue: rgb(29 170 198);', $saida);
    }

    public function testValorLongoDemaisEDescartado(): void
    {
        $longo = '--shadow-x: ' . str_repeat('0 1px 0 rgba(0,0,0,0.1), ', 12) . '0 0 0 #000;';
        $saida = html_editor_ia_tokens_tema_compilar("@theme {\n{$longo}\n--color-y: #abc;\n}");

        self::assertStringContainsString('--color-y', $saida);
        self::assertStringNotContainsString('--shadow-x', $saida);
    }

    public function testUrlCurtaDeFontePassa(): void
    {
        $saida = html_editor_ia_tokens_tema_compilar("@theme {\n--font-brand: url(/fonts/brand.woff2);\n}");

        self::assertStringContainsString('--font-brand: url(/fonts/brand.woff2);', $saida);
    }

    public function testChaveAninhadaNoThemeNaoTruncaOBloco(): void
    {
        // Um `@media` dentro do tema faria um padrão guloso `@theme[^}]*}` fechar cedo e a paleta
        // chegaria pela metade na IA — sem erro em lugar nenhum.
        $css = "@theme {\n--color-a: #a;\n@media (min-width: 40rem) { --color-b: #b; }\n--color-c: #c;\n}";
        $saida = html_editor_ia_tokens_tema_compilar($css);

        self::assertStringContainsString('--color-a', $saida);
        self::assertStringContainsString('--color-c', $saida);
    }

    public function testComentarioNaoVazaParaOPrompt(): void
    {
        $saida = html_editor_ia_tokens_tema_compilar($this->contrato());

        self::assertStringNotContainsString('CONTRATO DE COMPILAÇÃO', $saida);
        self::assertStringNotContainsString('Paleta institucional', $saida);
        self::assertStringNotContainsString('----- Header -----', $saida);
    }

    // ===== Classes de `@layer components`

    public function testListaAsClassesDeComponentes(): void
    {
        $saida = html_editor_ia_tokens_tema_compilar($this->contrato());

        self::assertStringContainsString('.tm-shell', $saida);
        self::assertStringContainsString('.tm-nav-link', $saida);
    }

    public function testPseudoClasseEPseudoElementoColapsamNaClasseRaiz(): void
    {
        $classes = html_editor_ia_tokens_tema_componentes($this->contrato());

        self::assertSame(['.tm-shell', '.tm-nav-link'], $classes);
    }

    public function testDeclaracaoDeComponenteNaoVirouLista(): void
    {
        // Só o NOME importa. `max-width: 80rem` e `padding-block` são 1,87 mil linhas no contrato
        // real e zero informação nova para quem já conhece CSS.
        $saida = html_editor_ia_tokens_tema_compilar($this->contrato());

        self::assertStringNotContainsString('max-width', $saida);
        self::assertStringNotContainsString('padding-block', $saida);
    }

    public function testBlocosSeparadosDeLayerComponentsSaoSomados(): void
    {
        $css = "@layer components { .a { color: red } }\n@layer components { .b { color: blue } }";

        self::assertSame(['.a', '.b'], html_editor_ia_tokens_tema_componentes($css));
    }

    public function testContratoSemComponentesDevolveListaVazia(): void
    {
        self::assertSame([], html_editor_ia_tokens_tema_componentes("@theme {\n--color-a: #a;\n}"));
    }

    // ===== Orçamento

    public function testNuncaUltrapassaOLimite(): void
    {
        // Critério de aceite 2: contrato de 80 KB+ não pode estourar o teto.
        $paleta = '';
        for ($i = 0; $i < 400; $i++) $paleta .= "--color-marca-tom-numero-{$i}: rgba(255, 128, 64, 0.5);\n";

        $componentes = '';
        for ($i = 0; $i < 400; $i++) $componentes .= ".tm-componente-de-nome-longo-{$i} { color: red; padding: 1rem; }\n";

        $css = "@theme static {\n{$paleta}}\n@layer components {\n{$componentes}}";

        self::assertGreaterThan(30000, strlen($css));

        $saida = html_editor_ia_tokens_tema_compilar($css);

        self::assertLessThanOrEqual(html_editor_ia_tokens_tema_limite(), strlen($saida));
        self::assertLessThanOrEqual(2048, strlen($saida));
    }

    public function testMarcadorDeRestantesAparecePelosDoisLados(): void
    {
        $paleta = '';
        for ($i = 0; $i < 400; $i++) $paleta .= "--color-marca-tom-numero-{$i}: rgba(255, 128, 64, 0.5);\n";

        $componentes = '';
        for ($i = 0; $i < 400; $i++) $componentes .= ".tm-componente-de-nome-longo-{$i} { color: red; }\n";

        $saida = html_editor_ia_tokens_tema_compilar("@theme {\n{$paleta}}\n@layer components {\n{$componentes}}");

        self::assertSame(2, preg_match_all('/\/\* \+\d+ \*\//', $saida));
    }

    public function testNamespacePequenoNaoEEngolidoPeloGrande(): void
    {
        // Corte sequencial deixaria a fonte da marca de fora: as cores consomem o orçamento inteiro
        // antes de o laço chegar nela. O round-robin dá uma rodada a cada namespace.
        $paleta = '';
        for ($i = 0; $i < 400; $i++) $paleta .= "--color-marca-tom-numero-{$i}: rgba(255, 128, 64, 0.5);\n";

        $saida = html_editor_ia_tokens_tema_compilar("@theme {\n{$paleta}--font-sans: \"Inter\", sans-serif;\n}");

        self::assertStringContainsString('--font-sans', $saida);
        self::assertStringContainsString('--color-marca-tom-numero-0', $saida);
    }

    public function testAOrdemDeSaidaSegueOsNamespacesNaoORoundRobin(): void
    {
        // O round-robin decide QUEM entra; a saída volta à ordem natural para o bloco continuar
        // legível como CSS de tema.
        $saida = html_editor_ia_tokens_tema_compilar("@theme {\n--color-a: #a;\n--font-sans: sans-serif;\n--color-b: #b;\n}");

        self::assertLessThan(strpos($saida, '--font-sans'), strpos($saida, '--color-b'));
    }

    public function testLimiteZeradoDevolveVazio(): void
    {
        self::assertSame('', html_editor_ia_tokens_tema_compilar($this->contrato(), 0));
    }

    public function testCssVazioDevolveVazio(): void
    {
        self::assertSame('', html_editor_ia_tokens_tema_compilar(''));
    }

    public function testContratoSoComComentarioDevolveVazio(): void
    {
        // É o contrato do CORE: só a nota explicando que o gerador injeta os `@source`.
        self::assertSame('', html_editor_ia_tokens_tema_compilar("/*\n * Contrato central do tema.\n */\n"));
    }

    // ===== Resolução do arquivo

    public function testCaminhoInexistenteDevolveVazio(): void
    {
        self::assertSame('', html_editor_ia_extrair_tokens_tema(__DIR__ . '/nao-existe-browser-contract.css'));
    }

    public function testLeOContratoDoCaminhoInformado(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'c2f') . '.css';
        file_put_contents($tmp, $this->contrato());

        try {
            $saida = html_editor_ia_extrair_tokens_tema($tmp);

            self::assertStringContainsString('--color-mp-red', $saida);
            self::assertLessThanOrEqual(2048, strlen($saida));
        } finally {
            @unlink($tmp);
        }
    }

    // ===== Bloco condicional no texto do modo de IA

    private function modo(): string
    {
        return "Gere uma página.\n"
            . "O HTML usa o framework CSS `{{framework_css}}`.\n"
            . "<!-- theme-tokens < -->\n"
            . "Utilize prioritariamente os tokens abaixo.\n"
            . "```css\n{{theme_tokens}}\n```\n"
            . "<!-- theme-tokens > -->\n"
            . "HTML recebido:\n";
    }

    public function testComTokensOsMarcadoresSaemEADiretrizFica(): void
    {
        $saida = html_editor_ia_modo_theme_tokens_aplicar($this->modo(), "@theme {\n--color-mp-red: #ff010b;\n}");

        self::assertStringNotContainsString('theme-tokens <', $saida);
        self::assertStringNotContainsString('theme-tokens >', $saida);
        self::assertStringNotContainsString('{{theme_tokens}}', $saida);
        self::assertStringContainsString('Utilize prioritariamente os tokens abaixo.', $saida);
        self::assertStringContainsString('--color-mp-red: #ff010b;', $saida);
    }

    public function testSemTokensOBlocoInteiroSai(): void
    {
        // Projeto sem contrato, ou fora do Tailwind: mandar o modelo "usar prioritariamente" uma
        // lista em branco é pior do que não mandar nada — ele preenche a lacuna inventando.
        $saida = html_editor_ia_modo_theme_tokens_aplicar($this->modo(), '');

        self::assertStringNotContainsString('theme-tokens', $saida);
        self::assertStringNotContainsString('{{theme_tokens}}', $saida);
        self::assertStringNotContainsString('Utilize prioritariamente', $saida);
        self::assertStringContainsString('HTML recebido:', $saida);
        self::assertStringContainsString('{{framework_css}}', $saida);
    }

    public function testModoSemMarcadoresFicaIntacto(): void
    {
        // Modo customizado pelo operador, sem a seção: nada pode mudar nele.
        $modo = "Gere uma página com `{{framework_css}}`.\n";

        self::assertSame($modo, html_editor_ia_modo_theme_tokens_aplicar($modo, '@theme { --color-a: #a; }'));
        self::assertSame($modo, html_editor_ia_modo_theme_tokens_aplicar($modo, ''));
    }

    public function testMarcadorEmCrlfTambemERemovido(): void
    {
        // O `.md` do core é LF, mas o modo chega do banco e passa pelo CodeMirror do painel de IA.
        $modo = str_replace("\n", "\r\n", $this->modo());
        $saida = html_editor_ia_modo_theme_tokens_aplicar($modo, '@theme { --color-a: #a; }');

        self::assertStringNotContainsString('theme-tokens', $saida);
        self::assertStringNotContainsString('<!--', $saida);
    }

    public function testTagSemMarcadoresNaoVazaLiteralQuandoNaoHaTokens(): void
    {
        // O modo é EDITÁVEL no painel de IA e vive no banco: o operador pode ter apagado os
        // marcadores e mantido a tag. Sem a troca por vazio, o payload sairia com `{{theme_tokens}}`
        // escrito por extenso dentro do bloco ```css.
        $modo = "Use `{{framework_css}}`.\n```css\n{{theme_tokens}}\n```\nHTML:\n";
        $saida = html_editor_ia_modo_theme_tokens_aplicar($modo, '');

        self::assertStringNotContainsString('{{theme_tokens}}', $saida);
        self::assertStringContainsString('HTML:', $saida);
        self::assertStringContainsString('{{framework_css}}', $saida);
    }

    public function testMarcadorInvertidoNaoLevaORestoDoPromptJunto(): void
    {
        // `modelo_tag_del()` corta de `tag_in` até `tag_out` por posição: com o par fora de ordem
        // ele cortaria do lugar errado. O guard de posição evita isso, e a tag ainda assim some.
        $modo = "A\n<!-- theme-tokens > -->\n{{theme_tokens}}\n<!-- theme-tokens < -->\nB\n";
        $saida = html_editor_ia_modo_theme_tokens_aplicar($modo, '');

        self::assertStringContainsString('A', $saida);
        self::assertStringContainsString('B', $saida);
        self::assertStringNotContainsString('{{theme_tokens}}', $saida);
        self::assertStringNotContainsString('theme-tokens', $saida);
    }

    public function testTagSemMarcadoresAindaRecebeOsTokens(): void
    {
        $modo = "```css\n{{theme_tokens}}\n```\n";
        $saida = html_editor_ia_modo_theme_tokens_aplicar($modo, "@theme {\n--color-a: #a;\n}");

        self::assertStringContainsString('--color-a: #a;', $saida);
        self::assertStringNotContainsString('{{theme_tokens}}', $saida);
    }

    // ===== Resumo de classes (`{{css_compiled}}`, opt-in)

    public function testResumoDesescapaAUtilityEDeduplica(): void
    {
        // O que a IA escreve no atributo `class` é o nome desescapado: `lg:flex`, não `lg\:flex`.
        $css = '@layer utilities{ .bg-mp-red{color:red} .lg\:flex{display:flex} .p-1\.5{padding:1px} .bg-mp-red{x:y} }';

        self::assertSame('.bg-mp-red .lg:flex .p-1.5', html_editor_ia_css_classes_resumir($css));
    }

    public function testResumoRespeitaOLimite(): void
    {
        $css = '';
        for ($i = 0; $i < 500; $i++) $css .= ".classe-de-nome-longo-{$i} { color: red; }\n";

        $saida = html_editor_ia_css_classes_resumir($css);

        self::assertLessThanOrEqual(html_editor_ia_tokens_tema_limite(), strlen($saida));
        self::assertStringContainsString('.classe-de-nome-longo-0', $saida);
    }

    public function testResumoDeCssVazioDevolveVazio(): void
    {
        self::assertSame('', html_editor_ia_css_classes_resumir(''));
        self::assertSame('', html_editor_ia_css_classes_resumir('body { color: red; }'));
    }

    // ===== Guardas dos recursos (a fonte dos modos de IA)

    /**
     * @return array<int,string>
     */
    public static function modosComTokensProvider(): array
    {
        $raiz = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR;

        return [
            'paginas pt-br' => [$raiz . 'admin-paginas/resources/pt-br/ai_modes/paginas/paginas.md'],
            'paginas en' => [$raiz . 'admin-paginas/resources/en/ai_modes/paginas/paginas.md'],
            'paginas-editbar pt-br' => [$raiz . 'admin-paginas/resources/pt-br/ai_modes/paginas-editbar/paginas-editbar.md'],
            'paginas-editbar en' => [$raiz . 'admin-paginas/resources/en/ai_modes/paginas-editbar/paginas-editbar.md'],
            'componentes pt-br' => [$raiz . 'admin-componentes/resources/pt-br/ai_modes/componentes/componentes.md'],
            'componentes en' => [$raiz . 'admin-componentes/resources/en/ai_modes/componentes/componentes.md'],
        ];
    }

    #[DataProvider('modosComTokensProvider')]
    public function testModoDeclaraASecaoComOParDeMarcadores(string $caminho): void
    {
        self::assertFileExists($caminho);

        $md = (string)file_get_contents($caminho);

        self::assertStringContainsString('{{theme_tokens}}', $md);
        self::assertSame(1, substr_count($md, '<!-- theme-tokens < -->'), 'marcador de abertura');
        self::assertSame(1, substr_count($md, '<!-- theme-tokens > -->'), 'marcador de fechamento');

        // Marcador invertido deixaria `modelo_tag_del()` cortando do lugar errado.
        self::assertLessThan(
            strpos($md, '<!-- theme-tokens > -->'),
            strpos($md, '<!-- theme-tokens < -->')
        );

        // A seção tem de ser removível por inteiro sem levar o resto do prompt junto.
        $semSecao = modelo_tag_del($md, '<!-- theme-tokens < -->', '<!-- theme-tokens > -->');
        self::assertStringNotContainsString('{{theme_tokens}}', $semSecao);
        self::assertStringContainsString('{{html}}', $semSecao);
    }

    public function testModosIaDataCompiladoCarregaATag(): void
    {
        // O `.md` é a fonte; `ModosIaData.json` é o artefato do gerador e é o que chega ao banco.
        // Editar um sem recompilar o outro deixa o prompt do ambiente sem a seção.
        $arquivo = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'ModosIaData.json';
        self::assertFileExists($arquivo);

        $modos = json_decode((string)file_get_contents($arquivo), true);
        self::assertIsArray($modos);

        $encontrados = 0;
        foreach ($modos as $modo) {
            if (!in_array($modo['id'] ?? '', ['paginas', 'paginas-editbar', 'componentes'], true)) continue;
            $encontrados++;
            self::assertStringContainsString(
                '{{theme_tokens}}',
                (string)($modo['prompt'] ?? ''),
                'modo ' . $modo['id'] . ' (' . ($modo['language'] ?? '?') . ')'
            );
        }

        self::assertSame(6, $encontrados);
    }
}
