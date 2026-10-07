<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-253 — módulo "Páginas de Lousa": controles de exibição, montagem do HTML da página a partir do
 * modelo, conferência do pedido e os controles como parâmetros do widget "Lousa".
 * Gravar, listar, desativar e a página no ar são conferidos pelo roteiro de navegador.
 */
final class DashboardPagesReq253Test extends TestCase
{
    private const MODULO = CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard-pages/';
    private const WIDGET = CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/';

    private const STUBS = <<<'PHP'
<?php
$_GESTOR=['linguagem-codigo'=>'pt-br','url-raiz'=>'/'];
$GLOBALS['consultas']=[];
function banco_escape_field($v){return addslashes((string)$v);}
function banco_campo_existe($c,$t){return true;}
function banco_select($p){
    $GLOBALS['consultas'][]=$p['tabela'];
    $b=$GLOBALS['banco'];
    if($p['tabela']==='paginas') return !empty($b['caminho_em_uso']) ? ['id'=>'outra'] : null;
    if($p['tabela']==='dashboard_boards') return !empty($b['lousa']) ? ['id'=>'vendas','name'=>'Vendas'] : null;
    if($p['tabela']==='templates') return !empty($b['modelo']) ? ['id'=>'dashboard-pages-simples','nome'=>'Simples','html'=>$b['modelo'],'css'=>'.x{}','framework_css'=>''] : null;
    if($p['tabela']==='layouts') return !empty($b['layout']) ? ['id'=>'layout-site','nome'=>'Site','framework_css'=>'tailwindcss'] : null;
    return null;
}
PHP;

    private function rodar(string $codigo, array $entrada = [], array $banco = [])
    {
        $fonte = (string) file_get_contents(self::MODULO . 'dashboard-pages.php');
        $inicio = strpos($fonte, '// ===== Controles de exibição');
        $fim = strpos($fonte, '// ===== Formulário');
        self::assertNotFalse($inicio);
        self::assertNotFalse($fim);
        $script = self::STUBS . "\n\$entrada=" . var_export($entrada, true) . ";\n\$GLOBALS['banco']=" . var_export($banco, true) . ";\n" . substr($fonte, $inicio, $fim - $inicio)
            . "\n" . $this->funcao($fonte, 'dashboard_pages_pedido') . "\n" . $codigo . "\necho json_encode(['saida'=>\$saida,'consultas'=>\$GLOBALS['consultas']]);";
        $arquivo = tempnam(sys_get_temp_dir(), 'req253-');
        try {
            file_put_contents($arquivo, $script);
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo) . ' 2>&1', $linhas, $status);
            self::assertSame(0, $status, implode("\n", $linhas));
            return json_decode(implode("\n", $linhas), true, 512, JSON_THROW_ON_ERROR);
        } finally {
            unlink($arquivo);
        }
    }

    /** Texto de uma função do módulo, do `function` até a chave que fecha no início da linha. */
    private function funcao(string $fonte, string $nome): string
    {
        $inicio = strpos($fonte, 'function ' . $nome . '(');
        self::assertNotFalse($inicio, $nome);
        $fim = strpos($fonte, "\n}\n", $inicio);
        return substr($fonte, $inicio, $fim - $inicio + 3);
    }

    public function testControlesSoComChavesConhecidas(): void
    {
        $padrao = ['show_title' => true, 'title' => '', 'show_item_titles' => true, 'show_frames' => true, 'show_backgrounds' => true, 'show_objects' => true, 'mode' => 'auto'];
        self::assertSame($padrao, $this->rodar('$saida=dashboard_pages_schema_normalizar($entrada);', [])['saida']);
        self::assertSame($padrao, $this->rodar('$saida=dashboard_pages_schema_normalizar("isto não é json");')['saida']);
        $r = $this->rodar('$saida=dashboard_pages_schema_normalizar($entrada);', ['show_title' => 'false', 'title' => "  Campanha \n de  outubro  ", 'show_frames' => 0, 'show_objects' => '1', 'mode' => 'lousa', 'intruso' => '<script>'])['saida'];
        self::assertSame(['show_title' => false, 'title' => 'Campanha de outubro', 'show_item_titles' => true, 'show_frames' => false, 'show_backgrounds' => true, 'show_objects' => true, 'mode' => 'lousa'], $r);
        self::assertSame('auto', $this->rodar('$saida=dashboard_pages_schema_normalizar($entrada);', ['mode' => 'explode'])['saida']['mode']);
        self::assertSame(160, mb_strlen($this->rodar('$saida=dashboard_pages_schema_normalizar($entrada);', ['title' => str_repeat('é', 400)])['saida']['title']));
        // No formulário, chave desmarcada não vem no pedido.
        $pedido = $this->rodar('$saida=dashboard_pages_schema_do_pedido($entrada);', ['show_title' => '1', 'show_objects' => '1', 'mode' => 'grade', 'title' => 'Oi'])['saida'];
        self::assertSame(['show_title' => true, 'title' => 'Oi', 'show_item_titles' => false, 'show_frames' => false, 'show_backgrounds' => false, 'show_objects' => true, 'mode' => 'grade'], $pedido);
    }

    public function testCaminhoDaPagina(): void
    {
        $casos = ['Campanhas/Outubro 2026' => 'campanhas/outubro-2026/', '/promoção//de  verão/' => 'promocao/de-verao/', 'ÁÉÍ' => 'aei/', "x' OR 1=1 --" => 'x-or-1-1/',
            '../../etc/passwd' => 'etc/passwd/', '***' => '', '' => '', str_repeat('a', 201) => ''];
        self::assertSame(array_values($casos), $this->rodar('$saida=array_map("dashboard_pages_caminho_normalizar", $entrada);', array_keys($casos))['saida']);
    }

    public function testMarcadorLevaSoOQueFogeDoPadrao(): void
    {
        $tudo = $this->rodar('$saida=dashboard_pages_marcador("vendas", $entrada);', [])['saida'];
        $assinatura = 'widgets#dashboard->render({"grupo_slug":"vendas","id":"vendas"})';
        self::assertSame('<!-- ' . $assinatura . ' < --><!-- ' . $assinatura . ' > -->', $tudo);
        $menos = $this->rodar('$saida=dashboard_pages_marcador("vendas", $entrada);', ['show_item_titles' => false, 'show_frames' => false, 'show_backgrounds' => false, 'show_objects' => false, 'mode' => 'lousa'])['saida'];
        self::assertStringContainsString('render({"grupo_slug":"vendas","id":"vendas","titulos":false,"molduras":false,"fundos":false,"objetos":false,"modo":"lousa"})', $menos);
        // O marcador é reconhecido pelo sistema de widgets da página.
        self::assertSame(1, preg_match('/<!--\s*widgets#(.+?)\s*<\s*-->([\s\S]*?)<!--\s*widgets#\s*\1\s*>\s*-->/i', $menos));
    }

    public function testHtmlDaPaginaSaiDoModelo(): void
    {
        $modelo = (string) file_get_contents(self::MODULO . 'resources/pt-br/templates/dashboard-pages-simples/dashboard-pages-simples.html');
        $codigo = '$saida=dashboard_pages_html($entrada["modelo"], "vendas", $entrada["schema"], $entrada["nome"]);';
        $com = $this->rodar($codigo, ['modelo' => $modelo, 'schema' => [], 'nome' => 'Vendas <b>2026</b>'])['saida'];
        self::assertStringContainsString('<h1 class="c2f-lousa-pagina-titulo">Vendas &lt;b&gt;2026&lt;/b&gt;</h1>', $com);
        self::assertStringContainsString('<!-- widgets#dashboard->render({"grupo_slug":"vendas","id":"vendas"}) < -->', $com);
        self::assertStringNotContainsString('[[lousa#', $com);
        self::assertStringNotContainsString('lousa-titulo', $com);

        $sem = $this->rodar($codigo, ['modelo' => $modelo, 'schema' => ['show_title' => false], 'nome' => 'Vendas'])['saida'];
        self::assertStringNotContainsString('<h1', $sem);
        self::assertStringContainsString('widgets#dashboard->render(', $sem);

        // Título próprio vale no lugar do nome e não é lido de novo como marcador.
        $proprio = $this->rodar($codigo, ['modelo' => $modelo, 'schema' => ['title' => '[[lousa#widget]] & cia'], 'nome' => 'Vendas'])['saida'];
        self::assertStringContainsString('>[[lousa#widget]] &amp; cia</h1>', $proprio);
        self::assertSame(1, substr_count($proprio, '<!-- widgets#dashboard->render({"grupo_slug":"vendas","id":"vendas"}) < -->'));

        // Modelo sem lugar para a lousa ganha o marcador no fim.
        $semLugar = $this->rodar($codigo, ['modelo' => '<main>Olá</main>', 'schema' => [], 'nome' => 'Vendas'])['saida'];
        self::assertSame(1, preg_match('#^<main>Olá</main>\n<!-- widgets\#dashboard->render\(.+\) > -->$#', $semLugar));
    }

    public function testPedidoRecusaNaPrimeiraCoisaErrada(): void
    {
        $bom = ['nome' => ' Campanha  de outubro ', 'caminho' => 'Campanhas/Outubro', 'board_id' => 'vendas', 'template_id' => 'dashboard-pages-simples', 'layout_id' => 'layout-site', 'show_title' => '1', 'sem_permissao' => '1'];
        $banco = ['lousa' => true, 'modelo' => '<section>[[lousa#titulo]] [[lousa#widget]]</section>', 'layout' => true];
        $codigo = '$saida=dashboard_pages_pedido($entrada);';
        $erro = fn (array $pedido, array $b) => $this->rodar($codigo, $pedido, $b)['saida']['erro'] ?? null;

        self::assertSame('alert-name-required', $erro(['nome' => '   '] + $bom, $banco));
        self::assertSame('alert-path-invalid', $erro(['caminho' => '***'] + $bom, $banco));
        self::assertSame('alert-path-exists', $erro($bom, ['caminho_em_uso' => true] + $banco));
        self::assertSame('alert-board-missing', $erro($bom, ['lousa' => false] + $banco));
        self::assertSame('alert-board-missing', $erro(['board_id' => "vendas' OR '1'='1"] + $bom, $banco));
        self::assertSame('alert-template-missing', $erro($bom, ['modelo' => ''] + $banco));
        self::assertSame('alert-layout-missing', $erro($bom, ['layout' => false] + $banco));
        // Identificador fora do formato nem chega ao banco.
        self::assertSame(['paginas'], $this->rodar($codigo, ['board_id' => '../x'] + $bom, $banco)['consultas']);

        $ok = $this->rodar($codigo, $bom, $banco)['saida'];
        self::assertSame(['Campanha de outubro', 'campanhas/outubro/', 'vendas', 'dashboard-pages-simples', 'layout-site', true], [$ok['nome'], $ok['caminho'], $ok['lousa']['id'], $ok['modelo']['id'], $ok['layout']['id'], $ok['publica']]);
        self::assertSame(['show_title' => true, 'title' => '', 'show_item_titles' => false, 'show_frames' => false, 'show_backgrounds' => false, 'show_objects' => false, 'mode' => 'auto'], $ok['schema']);
        self::assertSame('<section>Campanha de outubro <!-- widgets#dashboard->render({"grupo_slug":"vendas","id":"vendas","titulos":false,"molduras":false,"fundos":false,"objetos":false}) < -->'
            . '<!-- widgets#dashboard->render({"grupo_slug":"vendas","id":"vendas","titulos":false,"molduras":false,"fundos":false,"objetos":false}) > --></section>', $ok['html']);
    }

    public function testWidgetObedeceAosControles(): void
    {
        $itens = [
            ['id' => 'menus', 'registro_id' => 'principal', 'width' => 6, 'height_px' => 220, 'options' => ['title' => 'Título', 'background' => '#0f172a', 'bgImage' => '/files/a.png']],
            ['id' => 'objeto', 'width' => 3, 'height_px' => 120, 'object' => ['type' => 'text', 'text' => 'Olá'], 'options' => ['header' => false]],
        ];
        $render = function (array $params) use ($itens): string {
            $script = "<?php\n\$_GESTOR=['linguagem-codigo'=>'pt-br'];\nfunction banco_escape_field(\$v){return addslashes((string)\$v);}\n"
                . "function banco_select(\$p){return \$p['tabela']==='dashboard_boards' ? ['id'=>'vendas','mode'=>'grade','layout'=>" . var_export(json_encode($itens), true) . "] : [['id'=>'menus']];}\n"
                . "function gestor_componente(\$p){return file_get_contents(" . var_export(self::WIDGET . 'resources/pt-br/components/dashboard-lousa-widget/dashboard-lousa-widget.html', true) . ");}\n"
                . "function gestor_incluir_biblioteca(\$b){}\nfunction gestor_pagina_recursos_incluir(\$p){}\nfunction gestor_pagina_javascript_incluir(\$p){}\nfunction widgets_get(\$p){return '<nav>menu</nav>';}\n"
                . "require " . var_export(self::WIDGET . 'dashboard.widget.php', true) . ";\necho dashboard_render(" . var_export(['grupo_slug' => 'vendas'] + $params, true) . ");";
            $arquivo = tempnam(sys_get_temp_dir(), 'req253w-');
            try {
                file_put_contents($arquivo, $script);
                exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo) . ' 2>&1', $linhas, $status);
                self::assertSame(0, $status, implode("\n", $linhas));
                return implode("\n", $linhas);
            } finally {
                unlink($arquivo);
            }
        };

        $tudo = $render([]);
        self::assertSame([2, 1, 1, 1, 0, 1], [substr_count($tudo, 'class="c2f-lousa-item'), substr_count($tudo, 'c2f-lousa-titulo'), substr_count($tudo, 'background-color:#0f172a'), substr_count($tudo, 'has-bg-image'),
            substr_count($tudo, 'c2f-lousa-item is-frameless'), substr_count($tudo, 'data-mode="grade"')]);

        self::assertSame(0, substr_count($render(['titulos' => false]), 'c2f-lousa-titulo'));
        self::assertSame(2, substr_count($render(['molduras' => false]), 'c2f-lousa-item is-frameless'));
        $semFundo = $render(['fundos' => false]);
        self::assertSame([0, 0, 2], [substr_count($semFundo, 'background-color'), substr_count($semFundo, 'has-bg-image'), substr_count($semFundo, 'data-tone="light"')]);
        $semObjeto = $render(['objetos' => false]);
        self::assertSame([1, 0], [substr_count($semObjeto, 'class="c2f-lousa-item'), substr_count($semObjeto, 'is-objeto')]);
        self::assertSame(1, substr_count($render(['modo' => 'lousa']), 'data-mode="lousa"'));
        self::assertSame(1, substr_count($render(['modo' => 'explode']), 'data-mode="grade"'));
    }

    public function testModuloRegistradoComRecursosNosDoisIdiomas(): void
    {
        $modulo = json_decode((string) file_get_contents(self::MODULO . 'dashboard-pages.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('paginas', $modulo['tabela']['nome']);
        $php = (string) file_get_contents(self::MODULO . 'dashboard-pages.php');
        preg_match_all("/dashboard_pages_texto\('([a-z-]+)'\)|'(alert-[a-z-]+)'|'(form-[a-z-]+-label)'/", $php, $usadas);
        $usadas = array_unique(array_filter(array_merge($usadas[1], $usadas[2], $usadas[3])));
        self::assertGreaterThanOrEqual(12, count($usadas));
        $marcadores = [];
        foreach (['pt-br', 'en'] as $lang) {
            $r = $modulo['resources'][$lang];
            self::assertSame(['dashboard-pages', 'dashboard-pages-adicionar', 'dashboard-pages-editar'], array_column($r['pages'], 'id'));
            self::assertSame(['listar', 'adicionar', 'editar'], array_column($r['pages'], 'option'));
            self::assertSame(['dashboard-pages', 'dashboard-pages'], array_column($r['templates'], 'target'));
            $variaveis = array_column($r['variables'], 'id');
            foreach ($usadas as $id) {
                self::assertContains($id, $variaveis, "$lang $id");
            }
            foreach (['adicionar', 'editar'] as $pagina) {
                $html = (string) file_get_contents(self::MODULO . "resources/$lang/pages/dashboard-pages-$pagina/dashboard-pages-$pagina.html");
                preg_match_all('/#[a-z_-]+#/', $html, $m);
                $marcadores[$pagina][$lang] = array_values(array_unique($m[0]));
                foreach (['name="nome"', 'name="caminho"', 'name="board_id"', 'name="template_id"', 'name="layout_id"', 'name="sem_permissao"', 'name="show_title"', 'name="title"', 'name="show_item_titles"',
                    'name="show_frames"', 'name="show_backgrounds"', 'name="show_objects"', 'name="mode"'] as $campo) {
                    self::assertSame(1, substr_count($html, $campo), "$lang $pagina $campo");
                }
                self::assertSame(substr_count($html, '<select'), substr_count($html, 'c2fc-campo-selecao'), "$lang $pagina");
            }
            foreach ($r['templates'] as $modelo) {
                $html = (string) file_get_contents(self::MODULO . "resources/$lang/templates/{$modelo['id']}/{$modelo['id']}.html");
                self::assertSame([1, 1, 1], [substr_count($html, '[[lousa#widget]]'), substr_count($html, '[[lousa#titulo]]'), substr_count($html, '<!-- lousa-titulo < -->')], "$lang {$modelo['id']}");
                self::assertFileExists(self::MODULO . "resources/$lang/templates/{$modelo['id']}/{$modelo['id']}.css");
            }
            self::assertContains('dashboard-pages', array_column(json_decode((string) file_get_contents(CONN2FLOW_GESTOR_ROOT . "/resources/$lang/modules.json"), true), 'id'));
            self::assertContains('modificar-permissao-da-pagina-de-lousa', array_column(json_decode((string) file_get_contents(CONN2FLOW_GESTOR_ROOT . "/resources/$lang/module_operations.json"), true), 'id'));
        }
        // Os dois idiomas trocam os mesmos marcadores, e o servidor preenche todos.
        foreach ($marcadores as $pagina => $porIdioma) {
            self::assertSame($porIdioma['pt-br'], $porIdioma['en'], $pagina);
            foreach ($porIdioma['pt-br'] as $marcador) {
                self::assertTrue(str_contains($php, "'" . $marcador . "'") || str_contains($php, "'#'.\$chave.'#'") && str_starts_with($marcador, '#show_') || str_starts_with($marcador, '#mode-'), "$pagina $marcador");
            }
        }
        $perfis = json_decode((string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/resources/user_profiles_modules.json'), true);
        self::assertContains(['perfil' => 'administradores', 'modulo' => 'dashboard-pages'], $perfis);
        $migracao = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/db/migrations/20261007110000_create_dashboard_pages_table.php');
        foreach (["hasTable('dashboard_pages')", "->addIndex(['page_id', 'language'], ['unique' => true])", "->addColumn('board_id'", "->addColumn('fields_schema'"] as $trecho) {
            self::assertStringContainsString($trecho, $migracao);
        }
    }
}
