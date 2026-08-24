<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'modelo.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'html-editor.php';

/**
 * Dublê de `ia_enviar_prompt()` (biblioteca `ia.php`, nunca carregada pela suíte).
 *
 * O prompt montado só existe dentro de `html_editor_ajax_ia_requests()` — ele é construído,
 * entregue ao servidor de IA e descartado. Sem interceptar aqui, o critério de aceite 1 ("o payload
 * enviado para a API contém as variáveis e classes canônicas do tema do projeto ativo") só seria
 * verificável olhando tráfego de rede numa homologação manual.
 */
function ia_enviar_prompt($params = false)
{
    $GLOBALS['__c2f_teste_prompt'] = (string)($params['prompt'] ?? '');
    $GLOBALS['__c2f_teste_servidor'] = (string)($params['servidor_id'] ?? '');

    return [
        'status' => 'success',
        'data' => ['texto_gerado' => "```html\n<p>ok</p>\n```\n```css\n.x{color:red}\n```"],
    ];
}

/**
 * req-127 (BATCH-129) — `{{theme_tokens}}` no payload real do Assistente de IA.
 *
 * Os DOIS editores entram por `html_editor_ajax_ia_requests()`: o clássico direto pelo ajaxOpcao
 * `html-editor-ia-requests`, e a Editbar por `site-toolbar-ia-request` →
 * `dashboard_ajax_site_toolbar_ia_request()`, que só checa permissão e delega. Por isso os três
 * escopos de edição do intake (`tudo`, `sessao`, `editbar-element`) são exercitados aqui contra a
 * mesma função: o que muda entre eles é o `{{html}}`, nunca o tema.
 */
final class HtmlEditorIaRequestsThemeTokensTest extends TestCase
{
    private string $raizProjeto = '';
    private array $gestorOriginal = [];

    protected function setUp(): void
    {
        global $_GESTOR;

        $this->gestorOriginal = is_array($_GESTOR) ? $_GESTOR : [];

        // Projeto sintético com a MESMA estrutura que o runtime do Tailwind Browser resolve:
        // `contents/tailwindcss/browser-contract.css` do projeto na frente do `assets/` do core.
        $this->raizProjeto = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req127-' . uniqid();
        $dir = $this->raizProjeto . DIRECTORY_SEPARATOR . 'contents' . DIRECTORY_SEPARATOR . 'tailwindcss';
        mkdir($dir, 0777, true);

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'browser-contract.css', <<<'CSS'
@theme static {
    --color-mp-red: #ff010b;
    --color-mp-gold: #c89d58;
    --art-mask: url("data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg'%3E%3C/svg%3E");
}

@layer components {
    .tm-button-primary { display: inline-flex; }
}
CSS);

        $_GESTOR['ROOT_PATH'] = $this->raizProjeto . DIRECTORY_SEPARATOR;
        $_GESTOR['ajax-json'] = null;

        $GLOBALS['__c2f_teste_prompt'] = '';
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        global $_GESTOR;

        $_GESTOR = $this->gestorOriginal;
        $_REQUEST = [];

        $contrato = $this->raizProjeto . '/contents/tailwindcss/browser-contract.css';
        if (is_file($contrato)) @unlink($contrato);
        foreach (['/contents/tailwindcss', '/contents', ''] as $sub) {
            $dir = $this->raizProjeto . $sub;
            if (is_dir($dir)) @rmdir($dir);
        }
    }

    private function modo(): string
    {
        return "Gere HTML com o framework `{{framework_css}}`.\n"
            . "<!-- theme-tokens < -->\n"
            . "Utilize prioritariamente os tokens abaixo.\n"
            . "```css\n{{theme_tokens}}\n```\n"
            . "<!-- theme-tokens > -->\n"
            . "HTML anterior:\n```html\n{{html}}\n```\n";
    }

    private function disparar(array $data, string $target = 'paginas'): string
    {
        $_REQUEST = [
            'mode' => $this->modo(),
            'target' => $target,
            'prompt' => 'Deixe o botão com a cor da marca.',
            'server_id' => 'gemini-1',
            'model' => null,
            'data' => $data,
        ];

        html_editor_ajax_ia_requests();

        return (string)$GLOBALS['__c2f_teste_prompt'];
    }

    /**
     * Os três escopos de edição do intake, com o `{{html}}` que cada um envia de verdade.
     *
     * @return array<string,array{0:array<string,mixed>}>
     */
    public static function escoposProvider(): array
    {
        return [
            // Editor clássico, "modificar tudo": o documento inteiro.
            'tudo' => [[
                'html' => '<section data-id="1"><h1>Título</h1></section>',
                'css' => '',
                'framework_css' => 'tailwindcss',
                'sessao_id' => '',
                'sessao_opcao' => '',
            ]],
            // Editor clássico, "modificar sessão": só o outerHTML da `<section>` selecionada.
            'sessao' => [[
                'html' => '<section data-id="2"><p>Trecho</p></section>',
                'css' => '',
                'framework_css' => 'tailwindcss',
                'sessao_id' => '2',
                'sessao_opcao' => 'target',
            ]],
            // Editbar: o `outerHTML` do elemento selecionado, e `css` sempre vazio.
            'editbar-element' => [[
                'html' => '<h1>Título</h1>',
                'css' => '',
                'framework_css' => 'tailwindcss',
                'sessao_id' => '',
                'sessao_opcao' => '',
            ]],
        ];
    }

    #[DataProvider('escoposProvider')]
    public function testOsTresEscoposRecebemOsTokensDoProjeto(array $data): void
    {
        $prompt = $this->disparar($data);

        self::assertStringContainsString('--color-mp-red: #ff010b;', $prompt);
        self::assertStringContainsString('--color-mp-gold: #c89d58;', $prompt);
        self::assertStringContainsString('.tm-button-primary', $prompt);

        // O HTML do escopo continua chegando junto — o tema é acréscimo, não substituição.
        self::assertStringContainsString($data['html'], $prompt);
    }

    #[DataProvider('escoposProvider')]
    public function testNenhumMarcadorNemTagSobraNoPayload(array $data): void
    {
        $prompt = $this->disparar($data);

        self::assertStringNotContainsString('{{theme_tokens}}', $prompt);
        self::assertStringNotContainsString('theme-tokens', $prompt);
        self::assertStringNotContainsString('{{html}}', $prompt);
        self::assertStringNotContainsString('{{framework_css}}', $prompt);
    }

    public function testAssetEmbutidoNaoEntraNoPayload(): void
    {
        // O ponto do lote: o contrato do `transformamp` tem 78 KB por causa de SVG embutido, e ele
        // não pode custar token nenhum.
        $prompt = $this->disparar(self::escoposProvider()['tudo'][0]);

        self::assertStringNotContainsString('data:image/svg+xml', $prompt);
        self::assertStringNotContainsString('--art-mask', $prompt);
    }

    public function testProjetoSemContratoNaoRecebeSecaoVazia(): void
    {
        global $_GESTOR;

        // Sem `contents/` nem `assets/`: instalação fora do Tailwind. A diretriz inteira tem de
        // sumir, senão o prompt manda "usar prioritariamente" uma lista em branco.
        $_GESTOR['ROOT_PATH'] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req127-inexistente' . DIRECTORY_SEPARATOR;

        $prompt = $this->disparar(self::escoposProvider()['tudo'][0]);

        self::assertStringNotContainsString('{{theme_tokens}}', $prompt);
        self::assertStringNotContainsString('theme-tokens', $prompt);
        self::assertStringNotContainsString('Utilize prioritariamente', $prompt);
        self::assertStringContainsString('HTML anterior:', $prompt);
    }

    public function testPayloadFicaLongeDoCustoDoContratoBruto(): void
    {
        // Objetivo 2 do intake: o acréscimo por interação tem de caber em ~1,5 KB.
        $semTema = html_editor_ia_modo_theme_tokens_aplicar($this->modo(), '');
        $prompt = $this->disparar(self::escoposProvider()['tudo'][0]);

        $acrescimo = strlen($prompt) - strlen($semTema) - strlen('Deixe o botão com a cor da marca.');

        self::assertLessThanOrEqual(2048, $acrescimo);
    }

    public function testCssCompiledSoEntraQuandoOModoPede(): void
    {
        // Opt-in: nenhum modo do core declara a tag, e o valor cru é o output inteiro do Tailwind.
        $data = self::escoposProvider()['tudo'][0];
        $data['css_compiled'] = '@layer utilities{ .bg-mp-red{background:#ff010b} .lg\:flex{display:flex} }';

        $prompt = $this->disparar($data);
        self::assertStringNotContainsString('.bg-mp-red', $prompt);

        $_REQUEST['mode'] = $this->modo() . "CSS já compilado:\n```css\n{{css_compiled}}\n```\n";
        $_REQUEST['data'] = $data;
        html_editor_ajax_ia_requests();
        $prompt = (string)$GLOBALS['__c2f_teste_prompt'];

        self::assertStringContainsString('.bg-mp-red', $prompt);
        self::assertStringContainsString('.lg:flex', $prompt);
        self::assertStringNotContainsString('{{css_compiled}}', $prompt);
        // Resumido, nunca cru: a declaração fica de fora.
        self::assertStringNotContainsString('background:#ff010b', $prompt);
    }

    public function testRespostaDoEndpointSegueIntacta(): void
    {
        global $_GESTOR;

        // Guarda de regressão: a injeção não pode alterar o contrato de saída do AJAX.
        $this->disparar(self::escoposProvider()['tudo'][0]);

        self::assertSame('Ok', $_GESTOR['ajax-json']['status'] ?? null);
        self::assertSame('<p>ok</p>', $_GESTOR['ajax-json']['data']['html_gerado'] ?? null);
        self::assertSame('.x{color:red}', $_GESTOR['ajax-json']['data']['css_gerado'] ?? null);
    }
}
