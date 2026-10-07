<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-258 — modelos de lousa prontos: arranjo em JSON no cadastro de modelos (alvo `dashboard-boards`),
 * ligação dos widgets aos registros da instalação e criação da lousa pelo Dashboard.
 */
final class DashboardLousaModelosReq258Test extends TestCase
{
    private const MODULO = CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/';

    private const STUBS = <<<'PHP'
<?php
$_GESTOR=['linguagem-codigo'=>'pt-br','usuario-id'=>7];
$GLOBALS['c']=['inseridos'=>[],'consultas'=>[]];
function dashboard_widgets_pode_administrar(){return $GLOBALS['administra'];}
function dashboard_widgets_negar(){$GLOBALS['_GESTOR']['ajax-json']=['status'=>'error','message'=>'sem-permissao'];}
function gestor_set($k,$v){$GLOBALS['_GESTOR'][$k]=$v;}
function gestor_variaveis($p){return 'msg:'.$p['id'];}
function banco_escape_field($v){return addslashes((string)$v);}
function dashboard_widget_definicao($id){$t=['menus'=>'menus','pages-index'=>'pages_index','forms'=>'forms','galleries'=>'galleries'];return isset($t[$id])?['id'=>$id,'tabela'=>$t[$id],'coluna_where'=>'']:null;}
function banco_insert_name($campos,$tabela){$linha=[];foreach($campos as $c)$linha[$c[0]]=$c[1];$GLOBALS['c']['inseridos'][]=[$tabela,$linha];if($tabela==='dashboard_boards')$GLOBALS['criada']=$linha;}
function banco_select($p){
    $GLOBALS['c']['consultas'][]=$p['tabela'];
    if($p['tabela']==='templates'){
        if(!empty($p['unico'])){ foreach($GLOBALS['modelos'] as $m) if(strpos($p['extra'],"id='".$m['id']."'")!==false) return $m; return null; }
        return $GLOBALS['modelos'];
    }
    if($p['tabela']==='dashboard_boards'){
        if(strpos($p['extra'],"status='A'")!==false && !empty($GLOBALS['criada']) && strpos($p['extra'],"id='".$GLOBALS['criada']['id']."'")!==false)
            return ['id'=>$GLOBALS['criada']['id'],'name'=>$GLOBALS['criada']['name'],'mode'=>$GLOBALS['criada']['mode'],'layout'=>$GLOBALS['criada']['layout'],'versao'=>1,'data_modificacao'=>'2026-10-07 10:00:00'];
        return null;
    }
    // Registros dos widgets: só menus e páginas têm registro nesta instalação simulada.
    if($p['tabela']==='menus') return ['id'=>'principal'];
    if($p['tabela']==='pages_index') return ['id'=>'todas'];
    return null;
}
PHP;

    private function rodar(string $codigo, array $modelos, bool $administra = true, array $pedido = []): array
    {
        $fonte = (string) file_get_contents(self::MODULO . 'dashboard.php');
        $inicio = strpos($fonte, '// ===== Lousas nomeadas: registro do sistema, duplicar e versões (REQ-251)');
        $fim = strpos($fonte, "/**\n * req-226 (CA-2, CA-4): Endpoint AJAX para salvar preferências");
        self::assertNotFalse($inicio);
        self::assertNotFalse($fim);
        $script = self::STUBS . "\n\$GLOBALS['administra']=" . var_export($administra, true) . ";\n\$GLOBALS['modelos']=" . var_export($modelos, true) . ";\n\$_REQUEST=" . var_export($pedido, true)
            . ";\nrequire " . var_export(self::MODULO . 'dashboard-layout.php', true) . ";\n" . substr($fonte, $inicio, $fim - $inicio) . "\n" . $codigo
            . "\necho json_encode(['saida'=>\$saida ?? null,'json'=>\$_GESTOR['ajax-json'] ?? null]+\$GLOBALS['c']);";
        $arquivo = tempnam(sys_get_temp_dir(), 'req258-');
        try {
            file_put_contents($arquivo, $script);
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo) . ' 2>&1', $linhas, $status);
            self::assertSame(0, $status, implode("\n", $linhas));
            return json_decode((string) array_pop($linhas), true, 512, JSON_THROW_ON_ERROR);
        } finally {
            unlink($arquivo);
        }
    }

    private function modelo(string $id, string $nome, $conteudo): array
    {
        return ['id' => $id, 'nome' => $nome, 'html' => is_string($conteudo) ? $conteudo : json_encode($conteudo)];
    }

    private function arranjo(): array
    {
        return ['modo' => 'grade', 'widgets' => [
            ['id' => 'objeto', 'width' => 12, 'height_px' => 120, 'object' => ['type' => 'text', 'text' => 'Bem-vindo <script>']],
            ['id' => 'menus', 'registro_id' => '', 'width' => 4, 'height_px' => 320],
            ['id' => 'pages-index', 'registro_id' => 'escolhida', 'width' => 8, 'height_px' => 320],
            ['id' => 'forms', 'registro_id' => '', 'width' => 6],
            ['id' => 'modulo-que-nao-existe', 'registro_id' => '', 'width' => 6],
            ['id' => 'dashboard', 'registro_id' => 'outra', 'width' => 12],
            ['id' => 'menus', 'registro_id' => '', 'width' => 4],
        ]];
    }

    public function testArranjoDoModeloPassaPelaNormalizacaoDasLousas(): void
    {
        $bom = $this->rodar('$saida=dashboard_lousas_modelo_arranjo($GLOBALS["modelos"][0]["html"]);', [$this->modelo('m', 'M', ['modo' => 'lousa', 'widgets' => [['id' => 'menus', 'width' => 99, 'height_px' => 5, 'intruso' => 1]]])])['saida'];
        self::assertSame(['lousa', 1, 24, 120], [$bom['modo'], count($bom['widgets']), $bom['widgets'][0]['width'], $bom['widgets'][0]['height_px']]);
        self::assertArrayNotHasKey('intruso', $bom['widgets'][0]);
        foreach (['<section>isto é HTML</section>', '', '{"modo":"grade","widgets":[]}', '[]', '{"modo":"grade","widgets":[{"id":"../x"}]}'] as $ruim) {
            self::assertNull($this->rodar('$saida=dashboard_lousas_modelo_arranjo($GLOBALS["modelos"][0]["html"]);', [$this->modelo('m', 'M', $ruim)])['saida'], $ruim);
        }
    }

    public function testWidgetDoModeloELigadoAoRegistroDaInstalacao(): void
    {
        $r = $this->rodar('$a=dashboard_lousas_modelo_arranjo($GLOBALS["modelos"][0]["html"]); $saida=dashboard_lousas_modelo_ligar($a["widgets"]);', [$this->modelo('m', 'M', $this->arranjo())]);
        $itens = $r['saida']['itens'];
        // Objeto entra sempre; menus recebe o primeiro registro; quem já traz registro fica; formulário sem registro,
        // tipo que não existe e lousa dentro de lousa ficam de fora.
        self::assertSame([['objeto', ''], ['menus', 'principal'], ['pages-index', 'escolhida'], ['menus', 'principal']], array_map(static fn ($i) => [$i['id'], $i['registro_id']], $itens));
        self::assertSame(3, $r['saida']['fora']);
        // O registro de cada tipo é procurado uma vez só.
        self::assertSame(['menus', 'forms'], $r['consultas']);
    }

    public function testListaSoModelosQueSaoArranjo(): void
    {
        $r = $this->rodar('dashboard_ajax_lousa_modelos();', [$this->modelo('a', 'Boas-vindas', $this->arranjo()), $this->modelo('b', 'Quebrado', '<div>html</div>')]);
        self::assertSame([['id' => 'a', 'nome' => 'Boas-vindas', 'modo' => 'grade', 'total' => 7]], $r['json']['data']['modelos']);
    }

    public function testCriaALousaPeloModelo(): void
    {
        $modelos = [$this->modelo('dashboard-boards-boas-vindas', 'Lousa - Boas-vindas', $this->arranjo())];
        $r = $this->rodar('dashboard_ajax_lousa_de_modelo();', $modelos, true, ['modelo' => 'dashboard-boards-boas-vindas', 'nome' => '  Campanha de outubro ']);
        self::assertSame('Ok', $r['json']['status']);
        self::assertSame(['campanha-de-outubro', 'Campanha de outubro', 'grade', 4, 3], [$r['json']['data']['id'], $r['json']['data']['nome'], $r['json']['data']['modo'], $r['json']['data']['total'], $r['json']['data']['fora']]);
        [$tabela, $linha] = $r['inseridos'][0];
        self::assertSame(['dashboard_boards', 'Campanha de outubro', 'pt-br', 'grade'], [$tabela, $linha['name'], $linha['language'], $linha['mode']]);
        $layout = json_decode($linha['layout'], true);
        self::assertSame('Bem-vindo <script>', $layout[0]['object']['text']);

        // Sem nome, usa o do modelo.
        $semNome = $this->rodar('dashboard_ajax_lousa_de_modelo();', $modelos, true, ['modelo' => 'dashboard-boards-boas-vindas']);
        self::assertSame(['lousa-boas-vindas', 'Lousa - Boas-vindas'], [$semNome['json']['data']['id'], $semNome['json']['data']['nome']]);
    }

    public function testRecusas(): void
    {
        $modelos = [$this->modelo('ok', 'Ok', $this->arranjo()), $this->modelo('quebrado', 'Quebrado', '<b>x</b>'),
            $this->modelo('so-formulario', 'Só formulário', ['modo' => 'grade', 'widgets' => [['id' => 'forms', 'registro_id' => '']]])];
        $erro = fn (array $pedido) => $this->rodar('dashboard_ajax_lousa_de_modelo();', $modelos, true, $pedido);
        foreach (['nao-existe', "ok' OR '1'='1", '', 'quebrado'] as $id) {
            $r = $erro(['modelo' => $id]);
            self::assertSame(['error', 'msg:widgets-boards-model-missing', []], [$r['json']['status'], $r['json']['message'], $r['inseridos']], $id);
        }
        $vazio = $erro(['modelo' => 'so-formulario']);
        self::assertSame(['msg:widgets-boards-model-empty', []], [$vazio['json']['message'], $vazio['inseridos']]);

        foreach (['dashboard_ajax_lousa_modelos', 'dashboard_ajax_lousa_de_modelo'] as $acao) {
            $r = $this->rodar($acao . '();', $modelos, false, ['modelo' => 'ok']);
            self::assertSame([['status' => 'error', 'message' => 'sem-permissao'], [], []], [$r['json'], $r['consultas'], $r['inseridos']], $acao);
        }
    }

    public function testModelosDoModuloSaoArranjosValidosNosDoisIdiomas(): void
    {
        $modulo = json_decode((string) file_get_contents(self::MODULO . 'dashboard.json'), true, 512, JSON_THROW_ON_ERROR);
        $php = (string) file_get_contents(self::MODULO . 'dashboard.php');
        foreach (["case 'lousa-modelos':", "case 'lousa-de-modelo':"] as $rota) {
            self::assertStringContainsString($rota, $php);
        }
        $estrutura = [];
        foreach (['pt-br', 'en'] as $lang) {
            $r = $modulo['resources'][$lang];
            self::assertSame(['dashboard-boards-boas-vindas', 'dashboard-boards-marketing', 'dashboard-boards-vitrine'], array_column($r['templates'], 'id'));
            self::assertSame(['dashboard-boards'], array_values(array_unique(array_column($r['templates'], 'target'))));
            self::assertSame([['dashboard-boards', 'dashboard-boards', true]], array_map(static fn ($m) => [$m['id'], $m['target'], $m['default']], $r['ai_modes']));
            self::assertSame(['dashboard-boards'], array_column($r['ai_prompts_targets'], 'id'));
            $variaveis = array_column($r['variables'], 'id');
            foreach (['widgets-boards-model', 'widgets-boards-from-model', 'widgets-boards-model-required', 'widgets-boards-model-skipped', 'widgets-boards-model-missing', 'widgets-boards-model-empty'] as $id) {
                self::assertContains($id, $variaveis, "$lang $id");
            }
            $modo = (string) file_get_contents(self::MODULO . "resources/$lang/ai_modes/dashboard-boards/dashboard-boards.md");
            foreach (['"modo": "grade"', '"widgets"', '"registro_id": ""', '"id": "objeto"', 'height_px'] as $regra) {
                self::assertStringContainsString($regra, $modo, "$lang $regra");
            }
            foreach ($r['templates'] as $modelo) {
                $conteudo = (string) file_get_contents(self::MODULO . "resources/$lang/templates/{$modelo['id']}/{$modelo['id']}.html");
                $arranjo = $this->rodar('$saida=dashboard_lousas_modelo_arranjo($GLOBALS["modelos"][0]["html"]);', [$this->modelo($modelo['id'], $modelo['name'], $conteudo)])['saida'];
                self::assertNotNull($arranjo, "$lang {$modelo['id']}");
                self::assertSame('grade', $arranjo['modo']);
                // Toda linha da grade fecha em 12 colunas e nenhum widget traz registro fixo.
                self::assertSame(0, array_sum(array_column($arranjo['widgets'], 'width')) % 12, "$lang {$modelo['id']}");
                foreach ($arranjo['widgets'] as $item) {
                    self::assertSame('', $item['registro_id'], "$lang {$modelo['id']}");
                }
                $estrutura[$modelo['id']][$lang] = array_map(static fn ($i) => [$i['id'], $i['width'], $i['height_px'], $i['object']['type'] ?? null], $arranjo['widgets']);
            }
            $html = (string) file_get_contents(self::MODULO . "resources/$lang/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html");
            foreach (['id="dashboard-board-model"', 'id="dashboard-board-from-model"', 'data-label-board-model-required=', 'data-label-board-model-skipped='] as $trecho) {
                self::assertSame(1, substr_count($html, $trecho), "$lang $trecho");
            }
            $semAdmin = preg_replace('/<!-- (widgets-(?:menu|aviso|vazio|modais)-admin) < -->[\s\S]*?<!-- \1 > -->/', '', $html);
            self::assertStringNotContainsString('dashboard-board-model', $semAdmin, $lang);
        }
        // Os dois idiomas têm a mesma estrutura; só os textos mudam.
        foreach ($estrutura as $id => $porIdioma) {
            self::assertSame($porIdioma['pt-br'], $porIdioma['en'], $id);
        }
    }
}
