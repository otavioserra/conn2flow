<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-247 — área de widgets do Dashboard: duas operações do módulo, layout publicado por perfil e
 * guardas no servidor. As funções rodam num processo à parte, com banco e acesso simulados.
 */
final class DashboardWidgetsPermissoesReq247Test extends TestCase
{
    private const STUBS = <<<'PHP'
<?php
$_GESTOR=['linguagem-codigo'=>'pt-br','usuario-id'=>7,'url-raiz'=>'/'];
$GLOBALS['ops']=json_decode(getenv('REQ247_OPS') ?: '[]', true);
$GLOBALS['layouts']=json_decode(getenv('REQ247_LAYOUTS') ?: '[]', true);
$GLOBALS['prefs']=[];
function gestor_acesso($op=false,$modulo=false){ if($op===false) return $modulo!=='sem-acesso'; return in_array($op,$GLOBALS['ops'],true); }
function gestor_usuario(){ return ['id_usuarios_perfis'=>3]; }
function gestor_variaveis($p){ return 'msg:'.$p['id']; }
function gestor_set($id,$v){ $GLOBALS['_GESTOR'][$id]=$v; }
function banco_escape_field($v){ return addslashes((string)$v); }
function banco_campos_virgulas($c){ return implode(',', $c); }
function banco_select($p){
 if($p['tabela']==='usuarios_perfis'){
  if(strpos($p['extra'],'id_usuarios_perfis')!==false) return ['id'=>'consumidores'];
  return [['id'=>'administradores','nome'=>'Administradores'],['id'=>'consumidores','nome'=>'Consumidores']];
 }
 if($p['tabela']==='dashboard_layouts'){
  if(empty($p['extra'])){ $r=[]; foreach($GLOBALS['layouts'] as $perfil=>$layout) $r[]=['perfil'=>$perfil,'layout'=>$layout]; return $r; }
  preg_match("/perfil='([^']*)'/", $p['extra'], $m);
  $perfil=stripslashes($m[1]);
  return isset($GLOBALS['layouts'][$perfil]) ? ['layout'=>$GLOBALS['layouts'][$perfil],'id_dashboard_layouts'=>$perfil] : null;
 }
 if($p['tabela']==='paginas') return strpos($p['extra'],"modulo='menus'")!==false ? ['caminho'=>'menus/editar/'] : null;
 return null;
}
function banco_update($campos,$tabela,$extra){ preg_match("/id_dashboard_layouts='([^']*)'/", $extra, $m); preg_match("/layout='(.*?)',id_usuarios/s", $campos, $l); $GLOBALS['layouts'][stripslashes($m[1])]=stripslashes($l[1]); }
function banco_insert_name($dados,$tabela){ $linha=[]; foreach($dados as $d) $linha[$d[0]]=$d[1]; $GLOBALS['layouts'][$linha['perfil']]=$linha['layout']; }
function banco_delete($tabela,$extra){ preg_match("/perfil='([^']*)'/", $extra, $m); unset($GLOBALS['layouts'][stripslashes($m[1])]); }
function dashboard_preferencias_salvar($chave,$valor){ $GLOBALS['prefs'][$chave]=$valor; return true; }
PHP;

    /** @param list<string> $ops @param array<string,string> $layouts */
    private function rodar(string $codigo, array $ops, array $layouts = [], array $request = []): array
    {
        $fonte = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/dashboard.php');
        $inicio = strpos($fonte, '// ===== Área de widgets: permissões, layout por perfil e layouts salvos (REQ-247)');
        $fim = strpos($fonte, "/**\n * req-226 (CA-4): Retorna catálogo de widgets ativos");
        self::assertNotFalse($inicio);
        self::assertNotFalse($fim);
        $script = self::STUBS . "\n" . '$_REQUEST=' . var_export($request, true) . ";\n" . substr($fonte, $inicio, $fim - $inicio) . "\n" . $codigo
            . "\necho json_encode(['json'=>\$_GESTOR['ajax-json'] ?? null,'layouts'=>\$GLOBALS['layouts'],'prefs'=>\$GLOBALS['prefs'],'saida'=>\$saida ?? null]);";
        $arquivo = tempnam(sys_get_temp_dir(), 'req247-');
        try {
            file_put_contents($arquivo, $script);
            putenv('REQ247_OPS=' . json_encode($ops));
            putenv('REQ247_LAYOUTS=' . json_encode($layouts));
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo) . ' 2>&1', $linhas, $status);
            self::assertSame(0, $status, implode("\n", $linhas));
            return json_decode(implode("\n", $linhas), true, 512, JSON_THROW_ON_ERROR);
        } finally {
            putenv('REQ247_OPS');
            putenv('REQ247_LAYOUTS');
            unlink($arquivo);
        }
    }

    public function testOperacoesDeclaradasNosDoisIdiomasEAdministradoresRecebemAEdicao(): void
    {
        foreach (['pt-br', 'en'] as $lang) {
            $ops = json_decode((string) file_get_contents(CONN2FLOW_GESTOR_ROOT . "/resources/$lang/module_operations.json"), true);
            $doPainel = array_column(array_filter($ops, static fn ($o) => $o['modulo_id'] === 'dashboard'), 'operacao', 'id');
            self::assertSame(['administrar-widgets-do-dashboard' => 'widgets-administrar', 'visualizar-widgets-do-dashboard' => 'widgets-visualizar'], $doPainel, $lang);
        }
        $vinculos = json_decode((string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/resources/user_profiles_modules_operations.json'), true);
        self::assertContains(['perfil' => 'administradores', 'operacao' => 'administrar-widgets-do-dashboard'], $vinculos);
        // Só visualizar não vem ligado a nenhum perfil: quem concede é o administrador da instalação.
        self::assertNotContains('visualizar-widgets-do-dashboard', array_column($vinculos, 'operacao'));
    }

    public function testQuemAdministraTambemVeEQuemSoVisualizaNaoAdministra(): void
    {
        $codigo = '$saida=[dashboard_widgets_pode_administrar(), dashboard_widgets_pode_ver()];';
        self::assertSame([true, true], $this->rodar($codigo, ['widgets-administrar'])['saida']);
        self::assertSame([false, true], $this->rodar($codigo, ['widgets-visualizar'])['saida']);
        self::assertSame([false, false], $this->rodar($codigo, [])['saida']);
    }

    public function testLayoutPublicadoENormalizado(): void
    {
        $layout = [
            ['id' => 'menus', 'name' => 'Menus', 'registro_id' => 'principal', 'instance_id' => 'a<b>', 'width' => 1, 'height_px' => 5000, 'params' => ['grupo_slug' => 'principal', 'x' => ['y']],
                'options' => ['header' => false, 'background' => 'red;x', 'padding' => 'huge', 'refresh' => 300, 'title' => str_repeat('T', 200), 'intruso' => 1]],
            ['id' => 'menus"><script>', 'registro_id' => 'x'],
            'texto',
            ['id' => 'galleries', 'width' => 20],
        ];
        $r = $this->rodar('$saida=dashboard_widgets_layout_normalizar($_REQUEST["layout"]);', [], [], ['layout' => json_encode($layout)])['saida'];
        self::assertCount(2, $r);
        self::assertSame(['id' => 'menus', 'name' => 'Menus', 'registro_id' => 'principal', 'instance_id' => 'ab', 'width' => 2, 'height' => 1, 'height_px' => 960, 'params' => ['grupo_slug' => 'principal'],
            'options' => ['header' => false, 'frame' => true, 'title' => str_repeat('T', 80), 'background' => '', 'padding' => 'none', 'refresh' => 300], 'x' => null, 'y' => null], $r[0]);
        // REQ-248: na lousa a largura vai a 24 células.
        self::assertSame(20, $r[1]['width']);
        self::assertSame('perfil-1', $r[1]['instance_id']);
    }

    public function testPerfilSemLayoutProprioUsaODeTodos(): void
    {
        $menus = json_encode([['id' => 'menus', 'registro_id' => 'a']]);
        $todos = json_encode([['id' => 'galleries', 'registro_id' => 'b']]);
        $codigo = '$saida=dashboard_widgets_layout_perfil(dashboard_widgets_perfil_atual());';
        $proprio = $this->rodar($codigo, [], ['consumidores' => $menus, '*' => $todos])['saida'];
        self::assertSame(['consumidores', 'menus'], [$proprio['origem'], $proprio['layout'][0]['id']]);
        $geral = $this->rodar($codigo, [], ['administradores' => $menus, '*' => $todos])['saida'];
        self::assertSame(['*', 'galleries'], [$geral['origem'], $geral['layout'][0]['id']]);
        self::assertSame(['layout' => [], 'origem' => null, 'modo' => 'grade'], $this->rodar($codigo, [], [])['saida']);
    }

    public function testPublicarExigeAdministrarEAceitaSoPerfilQueExiste(): void
    {
        $pedido = ['perfis' => json_encode(['consumidores', '*', 'inventado', "x' OR 1=1"]), 'layout' => json_encode([['id' => 'menus', 'registro_id' => 'a', 'width' => 3]])];
        $negado = $this->rodar('dashboard_ajax_widgets_layout_publicar();', ['widgets-visualizar'], [], $pedido);
        self::assertSame('error', $negado['json']['status']);
        self::assertSame('msg:widgets-sem-permissao', $negado['json']['message']);
        self::assertSame([], $negado['layouts']);

        $ok = $this->rodar('dashboard_ajax_widgets_layout_publicar();', ['widgets-administrar'], ['*' => '[]'], $pedido);
        self::assertSame('Ok', $ok['json']['status']);
        self::assertSame(['consumidores', '*'], $ok['json']['data']['perfis']);
        self::assertSame(['*', 'consumidores'], array_keys($ok['layouts']));
        // REQ-248: o registro guarda o modo da área junto com os widgets.
        self::assertSame(['grade', 3], [json_decode($ok['layouts']['consumidores'], true)['modo'], json_decode($ok['layouts']['consumidores'], true)['widgets'][0]['width']]);
        self::assertSame(3, json_decode($ok['layouts']['*'], true)['widgets'][0]['width']);

        $vazio = $this->rodar('dashboard_ajax_widgets_layout_publicar();', ['widgets-administrar'], [], ['perfis' => '["inventado"]', 'layout' => '[]']);
        self::assertSame('msg:widgets-layouts-sem-perfil', $vazio['json']['message']);
    }

    public function testRemoverEListarSaoSoDeQuemAdministra(): void
    {
        $base = ['consumidores' => json_encode([['id' => 'menus', 'registro_id' => 'a']])];
        self::assertSame($base, $this->rodar('dashboard_ajax_widgets_layout_remover();', ['widgets-visualizar'], $base, ['perfil' => 'consumidores'])['layouts']);
        self::assertSame([], $this->rodar('dashboard_ajax_widgets_layout_remover();', ['widgets-administrar'], $base, ['perfil' => 'consumidores'])['layouts']);

        self::assertSame('error', $this->rodar('dashboard_ajax_widgets_layouts();', ['widgets-visualizar'], $base)['json']['status']);
        $lista = $this->rodar('dashboard_ajax_widgets_layouts();', ['widgets-administrar'], $base)['json']['data'];
        self::assertSame([['id' => 'administradores', 'nome' => 'Administradores', 'publicado' => false, 'total' => 0], ['id' => 'consumidores', 'nome' => 'Consumidores', 'publicado' => true, 'total' => 1]], $lista['perfis']);
        self::assertSame(['publicado' => false, 'total' => 0], $lista['todos']);
        self::assertSame('consumidores', $lista['perfil_atual']);
    }

    public function testPreferenciaDosWidgetsSoGravaParaQuemAdministra(): void
    {
        foreach (['dashboard_widgets_layout', 'dashboard_widgets_salvos', 'dashboard_widgets_fonte'] as $chave) {
            $negado = $this->rodar('dashboard_ajax_preferencia_salvar();', ['widgets-visualizar'], [], ['chave' => $chave, 'valor' => '[]']);
            self::assertSame('msg:widgets-sem-permissao', $negado['json']['message'], $chave);
            self::assertSame([], $negado['prefs'], $chave);
            self::assertArrayHasKey($chave, $this->rodar('dashboard_ajax_preferencia_salvar();', ['widgets-administrar'], [], ['chave' => $chave, 'valor' => '[]'])['prefs']);
        }
        // Densidade e aba continuam de qualquer usuário.
        self::assertSame(['dashboard_densidade' => 'g'], $this->rodar('dashboard_ajax_preferencia_salvar();', [], [], ['chave' => 'dashboard_densidade', 'valor' => 'g'])['prefs']);
    }

    public function testAtalhoDeEdicaoSoParaQuemAdministraEAcessaOModulo(): void
    {
        $codigo = '$saida=[dashboard_widgets_url_edicao("menus","docs sidebar"), dashboard_widgets_url_edicao("galleries","x"), dashboard_widgets_url_edicao("sem-acesso","x")];';
        self::assertSame(['/menus/editar/?id=docs%20sidebar', '', ''], $this->rodar($codigo, ['widgets-administrar'])['saida']);
        self::assertSame(['', '', ''], $this->rodar($codigo, ['widgets-visualizar'])['saida']);
    }

    public function testCatalogoRegistrosERenderizacaoTemGuarda(): void
    {
        $fonte = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/dashboard.php');
        foreach (['dashboard_ajax_widgets_catalogo' => 'dashboard_widgets_pode_administrar', 'dashboard_ajax_widgets_registros' => 'dashboard_widgets_pode_administrar', 'dashboard_ajax_widget_render' => 'dashboard_widgets_pode_ver'] as $funcao => $guarda) {
            preg_match('/function ' . $funcao . '\(\)\{[\s\S]*?\n\}/', $fonte, $m);
            self::assertStringContainsString('if(!' . $guarda . '()){ dashboard_widgets_negar(); return; }', $m[0], $funcao);
        }
        foreach (['widgets-layouts', 'widgets-layout-publicar', 'widgets-layout-remover'] as $acao) self::assertStringContainsString("case '$acao':", $fonte);
        // Sem a operação o bloco não chega ao navegador.
        self::assertStringContainsString("Array('widgets-aba', 'widgets-painel', 'widgets-menu-ver')", $fonte);
        self::assertStringContainsString("Array('widgets-menu-admin', 'widgets-aviso-admin', 'widgets-vazio-admin', 'widgets-modais-admin')", $fonte);
    }

    public function testComponenteTemOsBlocosPorPermissaoNosDoisIdiomas(): void
    {
        foreach (['pt-br', 'en'] as $lang) {
            $html = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . "/modulos/dashboard/resources/$lang/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html");
            foreach (['widgets-aba', 'widgets-painel', 'widgets-menu-ver', 'widgets-menu-admin', 'widgets-aviso-admin', 'widgets-vazio-admin', 'widgets-modais-admin'] as $bloco) {
                self::assertSame(1, substr_count($html, "<!-- $bloco < -->"), "$lang $bloco");
                self::assertSame(1, substr_count($html, "<!-- $bloco > -->"), "$lang $bloco");
            }
            // Tudo que edita fica dentro de um bloco de quem administra.
            $semAdmin = preg_replace('/<!-- (widgets-(?:menu|aviso|vazio|modais)-admin) < -->[\s\S]*?<!-- \1 > -->/', '', $html);
            foreach (['dashboard-edit-mode', 'dashboard-btn-add-widget', 'dashboard-btn-reset-widgets', 'dashboard-widgets-source', 'dashboard-widgets-mode', 'dashboard-widgets-per-row', 'dashboard-btn-layouts', 'dashboard-btn-toggle-headers',
                'dashboard-btn-open-catalog', 'dashboard-widgets-modal', 'dashboard-widget-config-modal', 'dashboard-widgets-layouts-modal', 'dashboard-btn-copy-profile'] as $controle) {
                self::assertStringNotContainsString($controle, $semAdmin, "$lang $controle");
            }
            self::assertStringContainsString('id="dashboard-btn-widgets-window"', $semAdmin, $lang);
            self::assertStringContainsString('id="dashboard-widgets-grid"', $semAdmin, $lang);
            $css = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . "/modulos/dashboard/resources/$lang/components/dashboard-cards-tailwind/dashboard-cards-tailwind.css");
            for ($n = 2; $n <= 12; $n++) self::assertSame(3, substr_count($css, '.dashboard-widget-card[data-widget-cols="' . $n . '"] {'), "$lang colunas $n");
        }
    }

    public function testLousaGuardaModoEPosicaoNoLayoutPublicado(): void
    {
        $widgets = [['id' => 'menus', 'registro_id' => 'a', 'width' => 20, 'x' => 7, 'y' => 12], ['id' => 'menus', 'registro_id' => 'b', 'x' => -1, 'y' => 99999], ['id' => 'menus', 'registro_id' => 'c', 'x' => '3', 'y' => 2.5]];
        $pedido = ['perfis' => '["consumidores"]', 'layout' => json_encode($widgets), 'modo' => 'lousa'];
        $ok = $this->rodar('dashboard_ajax_widgets_layout_publicar(); $saida=dashboard_widgets_layout_perfil("consumidores");', ['widgets-administrar'], [], $pedido);
        self::assertSame('lousa', $ok['json']['data']['modo']);
        self::assertSame('lousa', $ok['saida']['modo']);
        self::assertSame([[20, 7, 12], [4, null, null], [4, 3, null]], array_map(static fn ($w) => [$w['width'], $w['x'], $w['y']], $ok['saida']['layout']));
        // Modo desconhecido vale como grade; registro antigo (só a lista) também.
        $outro = $this->rodar('dashboard_ajax_widgets_layout_publicar(); $saida=dashboard_widgets_layout_perfil("consumidores");', ['widgets-administrar'], [], ['modo' => 'qualquer'] + $pedido);
        self::assertSame('grade', $outro['saida']['modo']);
        $antigo = $this->rodar('$saida=dashboard_widgets_layout_perfil("consumidores");', [], ['consumidores' => json_encode([['id' => 'menus', 'registro_id' => 'a']])]);
        self::assertSame(['grade', 1], [$antigo['saida']['modo'], count($antigo['saida']['layout'])]);
        $lista = $this->rodar('dashboard_ajax_widgets_layouts();', ['widgets-administrar'], ['consumidores' => json_encode(['modo' => 'lousa', 'widgets' => $widgets])])['json']['data'];
        self::assertSame(3, $lista['perfis'][1]['total']);
    }

    public function testMigracaoCriaATabelaComPerfilUnico(): void
    {
        $migracao = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/db/migrations/20261006200000_create_dashboard_layouts_table.php');
        self::assertStringContainsString("\$this->table('dashboard_layouts', ['id' => 'id_dashboard_layouts'])", $migracao);
        self::assertStringContainsString("->addIndex(['perfil'], ['unique' => true])", $migracao);
        self::assertStringContainsString("hasTable('dashboard_layouts')", $migracao);
    }
}
