<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-257 — a aba Modelos do editor lista os modelos do alvo também na inclusão, quando a tela não tem
 * campo de framework CSS e nenhum modelo foi carregado ainda. Com framework informado, o filtro continua.
 */
final class HtmlEditorModelosInclusaoReq257Test extends TestCase
{
    /** Roda `html_editor_ajax_templates_load()` com o banco simulado e devolve as cláusulas usadas. */
    private function consultar(array $params): array
    {
        $fonte = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/html-editor.php');
        $inicio = strpos($fonte, 'function html_editor_ajax_templates_load(){');
        self::assertNotFalse($inicio);
        $fim = strpos($fonte, "\n}\n", $inicio);
        $script = "<?php\n\$_GESTOR=['linguagem-codigo'=>'pt-br','url-raiz'=>'/','variavel-global'=>['open'=>'@[[','close'=>']]@','openText'=>'[[','closeText'=>']]']];\n"
            . "\$_REQUEST=['params'=>" . var_export($params, true) . "];\n\$GLOBALS['where']=[];\n"
            . "function banco_escape_field(\$v){return addslashes((string)\$v);}\nfunction hook_apply_filters(\$a,\$b,\$w){return \$w;}\n"
            . "function banco_select(\$p){\$GLOBALS['where'][]=\$p['extra'];return null;}\n"
            . substr($fonte, $inicio, $fim - $inicio + 3) . "\nhtml_editor_ajax_templates_load();\necho json_encode(['where'=>\$GLOBALS['where'],'json'=>\$_GESTOR['ajax-json'] ?? null]);";
        $arquivo = tempnam(sys_get_temp_dir(), 'req257-');
        try {
            file_put_contents($arquivo, $script);
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo) . ' 2>&1', $linhas, $status);
            self::assertSame(0, $status, implode("\n", $linhas));
            return json_decode((string) array_pop($linhas), true, 512, JSON_THROW_ON_ERROR);
        } finally {
            unlink($arquivo);
        }
    }

    public function testFrameworkVazioListaOsModelosDoAlvoEmQualquerFramework(): void
    {
        $r = $this->consultar(['alvo' => 'forms', 'alvos_modelos' => 'forms', 'framework_css' => '']);
        self::assertNotEmpty($r['where']);
        foreach ($r['where'] as $where) {
            self::assertStringNotContainsString('framework_css', $where);
            self::assertStringContainsString("target = 'forms'", $where);
            self::assertStringContainsString("language = 'pt-br'", $where);
            self::assertStringContainsString("status = 'A'", $where);
        }
        self::assertSame('Ok', $r['json']['status']);
    }

    public function testFrameworkInformadoContinuaFiltrando(): void
    {
        $tailwind = $this->consultar(['alvo' => 'paginas', 'framework_css' => 'tailwindcss']);
        self::assertStringContainsString("framework_css = 'tailwindcss'", $tailwind['where'][0]);
        // Sem o parâmetro (chamada antiga), o padrão de antes continua valendo.
        $antigo = $this->consultar(['alvo' => 'paginas']);
        self::assertStringContainsString("framework_css = 'fomantic-ui'", $antigo['where'][0]);
        $varios = $this->consultar(['alvo' => 'paginas', 'alvos_modelos' => "paginas, publisher' OR '1'='1", 'framework_css' => '']);
        self::assertStringNotContainsString('framework_css', $varios['where'][0]);
        self::assertStringContainsString("target IN ('paginas','publisher\\' OR \\'1\\'=\\'1')", $varios['where'][0]);
    }

    public function testEditorPedeSemFrameworkQuandoNaoSabe(): void
    {
        $js = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/assets/interface/html-editor-interface.js');
        self::assertStringContainsString('function frameworkCSSModelos() {', $js);
        // A aba Modelos usa a função nova; o resto do editor continua com a de sempre.
        $carregar = substr($js, (int) strpos($js, 'function modelosCarregar('), 900);
        self::assertStringContainsString('const framework_css = frameworkCSSModelos();', $carregar);
        // Modelo escolhido pela aba informa o framework quando a tela não tem o campo.
        $selecionar = substr($js, (int) strpos($js, 'function modeloSelecionar('), 900);
        self::assertStringContainsString("if (!\$('#framework-css').length && modelo.framework_css) gestor.html_editor.framework_css = modelo.framework_css;", $selecionar);
    }
}
