<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-251 — lousas nomeadas da área de widgets: registro do sistema, duplicar e versões.
 * As partes sem banco rodam num processo à parte; criar, gravar, restaurar e excluir são conferidos
 * contra o banco real pelo roteiro de navegador (`sdd/validation/req251/req251-browser.cjs`).
 */
final class DashboardLousasReq251Test extends TestCase
{
    private function fonte(): string
    {
        return (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/dashboard.php');
    }

    private function rodar(string $codigo, array $entrada = [], bool $administra = true)
    {
        $fonte = $this->fonte();
        $inicio = strpos($fonte, '// ===== Lousas nomeadas: registro do sistema, duplicar e versões (REQ-251)');
        $fim = strpos($fonte, "/**\n * req-226 (CA-2, CA-4): Endpoint AJAX para salvar preferências");
        self::assertNotFalse($inicio);
        self::assertNotFalse($fim);
        $stubs = '$_GESTOR=["linguagem-codigo"=>"pt-br","usuario-id"=>7];$GLOBALS["consultas"]=0;'
            . 'function dashboard_widgets_pode_administrar(){return ' . ($administra ? 'true' : 'false') . ';}'
            . 'function dashboard_widgets_negar(){$GLOBALS["_GESTOR"]["ajax-json"]=["status"=>"error","message"=>"sem-permissao"];}'
            . 'function gestor_set($k,$v){$GLOBALS["_GESTOR"][$k]=$v;}'
            . 'function gestor_variaveis($p){return "msg:".$p["id"];}'
            . 'function banco_escape_field($v){return addslashes((string)$v);}'
            . 'function banco_select($p){$GLOBALS["consultas"]++;if(!empty($GLOBALS["explode"]))throw new RuntimeException("banco fora");return null;}'
            . 'function dashboard_widgets_layout_normalizar($l){return is_array($l)?$l:[];}';
        $script = "<?php\n\$entrada=" . var_export($entrada, true) . ";\n\$_REQUEST=\$entrada;\n" . $stubs . "\n" . substr($fonte, $inicio, $fim - $inicio) . "\n" . $codigo
            . "\necho json_encode(['saida'=>\$saida ?? null,'json'=>\$_GESTOR['ajax-json'] ?? null,'consultas'=>\$GLOBALS['consultas']]);";
        $arquivo = tempnam(sys_get_temp_dir(), 'req251-');
        try {
            file_put_contents($arquivo, $script);
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo) . ' 2>&1', $linhas, $status);
            self::assertSame(0, $status, implode("\n", $linhas));
            // A resposta é a última linha; antes dela vem o que o servidor registrou no log de erros.
            $resposta = json_decode((string) array_pop($linhas), true, 512, JSON_THROW_ON_ERROR);
            $resposta['log'] = implode("\n", $linhas);
            return $resposta;
        } finally {
            unlink($arquivo);
        }
    }

    public function testIdentificadorSaiDoNome(): void
    {
        $casos = ['Campanha de Outubro' => 'campanha-de-outubro', '  Lançamento: versão 3.1!  ' => 'lancamento-versao-3-1', 'ÁÉÍÓÚ ção' => 'aeiou-cao', '***' => 'lousa', '' => 'lousa',
            str_repeat('a', 200) => str_repeat('a', 80), "x' OR 1=1 --" => 'x-or-1-1'];
        self::assertSame(array_values($casos), $this->rodar('$saida=array_map("dashboard_lousas_slug", $entrada);', array_keys($casos))['saida']);
    }

    public function testNomeEModo(): void
    {
        $r = $this->rodar('$saida=[dashboard_lousas_nome($entrada[0]), mb_strlen(dashboard_lousas_nome($entrada[1])), dashboard_lousas_modo("lousa"), dashboard_lousas_modo("grade"), dashboard_lousas_modo("x"), dashboard_lousas_limite_versoes()];',
            ["  Vendas \n\t do  mês ", str_repeat('é', 300)])['saida'];
        self::assertSame(['Vendas do mês', 120, 'lousa', 'grade', 'grade', 20], $r);
    }

    public function testIdentificadorInvalidoNemConsultaOBanco(): void
    {
        foreach (["vendas' OR '1'='1", 'Vendas', '../x', '', str_repeat('a', 121)] as $id) {
            $r = $this->rodar('$saida=dashboard_lousas_linha($entrada[0]);', [$id]);
            self::assertNull($r['saida'], $id);
            self::assertSame(0, $r['consultas'], $id);
        }
        self::assertSame(1, $this->rodar('$saida=dashboard_lousas_linha("vendas-2");')['consultas']);
    }

    public function testToAcaoRecusaQuemNaoAdministra(): void
    {
        $acoes = ['dashboard_ajax_lousas_listar', 'dashboard_ajax_lousa_salvar', 'dashboard_ajax_lousa_obter', 'dashboard_ajax_lousa_duplicar', 'dashboard_ajax_lousa_excluir', 'dashboard_ajax_lousa_versoes', 'dashboard_ajax_lousa_restaurar'];
        foreach ($acoes as $acao) {
            $r = $this->rodar($acao . '();', ['id' => 'vendas', 'nome' => 'Vendas', 'layout' => '[]', 'versao' => '1'], false);
            self::assertSame(['status' => 'error', 'message' => 'sem-permissao'], $r['json'], $acao);
            self::assertSame(0, $r['consultas'], $acao);
        }
        $fonte = $this->fonte();
        foreach (['lousas-listar', 'lousa-salvar', 'lousa-obter', 'lousa-duplicar', 'lousa-excluir', 'lousa-versoes', 'lousa-restaurar'] as $rota) {
            self::assertStringContainsString("case '$rota':", $fonte);
        }
    }

    public function testRespostasDeErroSemVazarDetalhe(): void
    {
        // Lousa que não existe, criação sem nome e falha de banco.
        self::assertSame('msg:widgets-boards-not-found', $this->rodar('dashboard_ajax_lousa_obter();', ['id' => 'nao-existe'])['json']['message']);
        self::assertSame('msg:widgets-boards-name-required', $this->rodar('dashboard_ajax_lousa_salvar();', ['nome' => '   ', 'layout' => '[]'])['json']['message']);
        self::assertSame('msg:widgets-boards-not-found', $this->rodar('dashboard_ajax_lousa_restaurar();', ['id' => 'vendas', 'versao' => '0'])['json']['message']);
        $falha = $this->rodar('$GLOBALS["explode"]=true; dashboard_ajax_lousas_listar();');
        self::assertSame(['status' => 'error', 'message' => 'msg:widgets-label-error'], $falha['json']);
        self::assertStringContainsString('dashboard_lousas: banco fora', $falha['log']);
    }

    public function testMigracaoEComponente(): void
    {
        $migracao = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/db/migrations/20261007100000_create_dashboard_boards_tables.php');
        foreach (["\$this->table('dashboard_boards', ['id' => 'id_dashboard_boards'])", "->addIndex(['id', 'language'], ['unique' => true])", "\$this->table('dashboard_boards_versions', ['id' => 'id_dashboard_boards_versions'])",
            "hasTable('dashboard_boards')", "hasTable('dashboard_boards_versions')", "->addColumn('name', 'string'", "->addColumn('status', 'char'"] as $trecho) {
            self::assertStringContainsString($trecho, $migracao);
        }
        foreach (['pt-br', 'en'] as $lang) {
            $html = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . "/modulos/dashboard/resources/$lang/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html");
            foreach (['id="dashboard-board-name"', 'id="dashboard-board-create"', 'id="dashboard-boards-list"', 'data-label-board-open=', 'data-label-board-update=', 'data-label-board-duplicate=', 'data-label-board-versions=',
                'data-label-board-restore=', 'data-label-board-none=', 'data-label-board-no-versions=', 'data-label-board-version=', 'data-label-board-items=', 'data-label-mode-grid=', 'data-label-mode-board='] as $trecho) {
                self::assertSame(1, substr_count($html, $trecho), "$lang $trecho");
            }
            $semAdmin = preg_replace('/<!-- (widgets-(?:menu|aviso|vazio|modais)-admin) < -->[\s\S]*?<!-- \1 > -->/', '', $html);
            self::assertStringNotContainsString('dashboard-boards-list', $semAdmin, $lang);
        }
    }
}
