<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-283 — motor canônico de CRUD da API (`gestor/bibliotecas/api-crud.php`).
 *
 * Cada cenário roda num processo à parte com o arnês `tests/Fixtures/ApiCrudHarness.php`:
 * SQLite em memória e as funções reais de montagem de SQL do banco.php.
 */
final class ApiCrudReq283Test extends TestCase
{
    private static string $plugins;

    public static function setUpBeforeClass(): void
    {
        // Plugin de teste: módulo com idioma, coluna fora da lista de leitura, exclusão definitiva e hooks.
        self::$plugins = sys_get_temp_dir() . '/req283-plugins-' . getmypid();
        $dir = self::$plugins . '/plug-teste/modules/itens-teste';
        @mkdir($dir, 0777, true);
        file_put_contents($dir . '/itens-teste.json', json_encode([
            'versao' => '1.0.0',
            'tabela' => [
                'nome' => 'itens_teste', 'id' => 'id', 'id_numerico' => 'id_itens_teste', 'status' => 'status',
                'versao' => 'versao', 'data_criacao' => 'data_criacao', 'data_modificacao' => 'data_modificacao',
            ],
            'api' => ['crud' => [
                'campos' => ['titulo', 'status', 'versao'],
                'obrigatorios' => ['titulo'],
                'id_origem' => 'titulo',
                'idioma' => true,
                'exclusao' => 'definitiva',
                'hooks' => 'itens-teste-api.php',
            ]],
        ]));
        file_put_contents($dir . '/itens-teste-api.php', <<<'PHP'
<?php
function itens_teste_api_crud_before_create(array $c) {
    $GLOBALS['ciclo'][] = 'before_create';
    if ($c['dados']['titulo'] === 'proibido') return ['erro' => true, 'code' => 422, 'mensagem' => 'Título recusado'];
    return ['dados' => ['titulo' => strtoupper($c['dados']['titulo']), 'segredo' => 'injetado']];
}
function itens_teste_api_crud_after_create(array $c) { $GLOBALS['ciclo'][] = 'after_create:' . $c['registro']['id']; }
function itens_teste_api_crud_before_delete(array $c) {
    $GLOBALS['ciclo'][] = 'before_delete';
    return $c['registro']['titulo'] === 'TRAVADO' ? ['erro' => true, 'code' => 409, 'mensagem' => 'Em uso'] : null;
}
function itens_teste_api_crud_after_delete(array $c) { $GLOBALS['ciclo'][] = 'after_delete:' . $c['exclusao']; }
PHP);
    }

    public static function tearDownAfterClass(): void
    {
        $dir = self::$plugins . '/plug-teste/modules/itens-teste';
        @unlink($dir . '/itens-teste.json');
        @unlink($dir . '/itens-teste-api.php');
        @rmdir($dir);
        @rmdir(self::$plugins . '/plug-teste/modules');
        @rmdir(self::$plugins . '/plug-teste');
        @rmdir(self::$plugins);
    }

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite indisponível.');
        }
    }

    /** @param list<string> $negados */
    private function rodar(string $codigo, array $negados = [], bool $acessoReal = false): array
    {
        $script = "<?php\nrequire " . var_export(CONN2FLOW_ROOT . '/tests/Fixtures/ApiCrudHarness.php', true) . ";\n"
            . $codigo
            . "\necho json_encode(['saida' => \$saida ?? null, 'avisos' => \$GLOBALS['avisos'], 'acessos' => \$GLOBALS['acessos'], 'ciclo' => \$GLOBALS['ciclo'] ?? [], 'sqls' => \$GLOBALS['sqls'], 'erros_sql' => \$GLOBALS['erros_sql'], 'logs' => logs()]);";
        $arquivo = tempnam(sys_get_temp_dir(), 'req283-');
        try {
            file_put_contents($arquivo, $script);
            putenv('API_CRUD_PLUGINS=' . self::$plugins);
            putenv('API_CRUD_NEGADOS=' . json_encode($negados));
            putenv('API_CRUD_ACESSO_REAL=' . ($acessoReal ? '1' : ''));
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo) . ' 2>&1', $linhas, $status);
            self::assertSame(0, $status, implode("\n", $linhas));
            $r = json_decode(implode("\n", $linhas), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame([], $r['avisos'], 'Avisos do PHP durante o cenário');
            return $r;
        } finally {
            putenv('API_CRUD_PLUGINS');
            putenv('API_CRUD_NEGADOS');
            putenv('API_CRUD_ACESSO_REAL');
            unlink($arquivo);
        }
    }

    public function testManifestoRealDeCategoriasHabilitaSoAsColunasDeclaradas(): void
    {
        $r = $this->rodar('$saida = api_crud_config("admin-categorias");');
        $cfg = $r['saida'];

        self::assertSame(['nome', 'plugin', 'id_modulos', 'id_categorias_pai'], $cfg['campos']);
        self::assertSame(['nome'], $cfg['obrigatorios']);
        self::assertSame('id_usuarios', $cfg['campo_usuario']);
        self::assertSame('logica', $cfg['exclusao']);
        self::assertSame(['id_categorias', 'id', 'nome', 'plugin', 'id_modulos', 'id_categorias_pai', 'id_usuarios', 'status', 'versao', 'data_criacao', 'data_modificacao'], $cfg['leitura']);
    }

    public function testModuloSemApiCrudOuInativoNaoEAtendido(): void
    {
        $r = $this->rodar('$saida = [req("GET", "", [], [], null, null, "admin-layouts"), req("GET", "", [], [], null, null, "modulo-inativo"), req("GET", "", [], [], null, null, "inexistente")];');
        self::assertSame([null, null, null], $r['saida']);
    }

    public function testCriarGeraIdUnicoMetadadosEAutor(): void
    {
        $r = $this->rodar(<<<'PHP'
$a = req('POST', '', [], ['nome' => 'Notícias Gerais', 'id_modulos' => 4]);
$b = req('POST', '', [], ['nome' => 'Notícias Gerais']);
$saida = [$a, $b, linha("SELECT * FROM categorias WHERE id='noticias-gerais'")];
PHP);
        [$a, $b, $banco] = $r['saida'];

        self::assertSame(201, $a['code']);
        self::assertSame('noticias-gerais', $a['dados']['id']);
        self::assertSame('Notícias Gerais', $a['dados']['nome']);
        self::assertSame('A', $a['dados']['status']);
        self::assertSame('1', (string)$a['dados']['versao']);
        self::assertSame('7', (string)$a['dados']['id_usuarios']);
        self::assertSame('4', (string)$a['dados']['id_modulos']);
        self::assertNotEmpty($a['dados']['data_criacao']);
        self::assertSame('noticias-gerais-1', $b['dados']['id']);
        self::assertSame('noticias-gerais', $banco['id']);
        // Acesso base ao módulo e depois a operação, em cada requisição (F1 da REV-001).
        self::assertSame([[false, 'admin-categorias', 3], ['adicionar', 'admin-categorias', 3], [false, 'admin-categorias', 3], ['adicionar', 'admin-categorias', 3]], $r['acessos']);
    }

    public function testCriarValidaObrigatoriosCamposDesconhecidosEValores(): void
    {
        $r = $this->rodar(<<<'PHP'
$saida = [
    req('POST', '', [], []),
    req('POST', '', [], ['nome' => '   ']),
    req('POST', '', [], ['nome' => 'X', 'senha' => 'abc']),
    req('POST', '', [], ['nome' => 'X', 'status' => 'D']),
    req('POST', '', [], ['nome' => 'X', 'id' => 'forcado']),
    req('POST', '', [], ['nome' => ['a']]),
    linha("SELECT COUNT(*) AS n FROM categorias"),
];
PHP);
        $s = $r['saida'];
        foreach (range(0, 5) as $i) {
            self::assertTrue($s[$i]['erro'], "cenário $i");
            self::assertSame(400, $s[$i]['code'], "cenário $i");
        }
        self::assertStringContainsString('nome', $s[0]['mensagem']);
        self::assertStringContainsString('senha', $s[2]['mensagem']);
        self::assertStringContainsString('status', $s[3]['mensagem']);
        self::assertStringContainsString('id', $s[4]['mensagem']);
        self::assertSame('0', (string)$s[6]['n']);
    }

    public function testListarPaginaBuscaOrdenaFiltraEIgnoraExcluidos(): void
    {
        $r = $this->rodar(<<<'PHP'
foreach (['Alfa', 'Beta', 'Gama', 'Delta', 'Epsilon'] as $n) req('POST', '', [], ['nome' => $n]);
req('POST', 'gama/status', [], ['status' => 'I']);
req('DELETE', 'delta');
$saida = [
    req('GET', '', ['per_page' => 2]),
    req('GET', '', ['per_page' => 2, 'page' => 2]),
    req('GET', '', ['sort' => 'id', 'order' => 'desc']),
    req('GET', '', ['search' => 'ta']),
    req('GET', '', ['status' => 'I']),
    req('GET', '', ['per_page' => 500]),
    req('GET', '', ['sort' => 'senha']),
    req('GET', '', ['order' => 'lado']),
    req('GET', '', ['status' => 'D']),
    req('GET', '', ['search' => "x' OR '1'='1"]),
];
PHP);
        $s = $r['saida'];

        self::assertSame(['page' => 1, 'per_page' => 2, 'total' => 4, 'total_pages' => 2], $s[0]['dados']['pagination']);
        self::assertSame(['alfa', 'beta'], array_column($s[0]['dados']['items'], 'id'));
        self::assertSame(['gama', 'epsilon'], array_column($s[1]['dados']['items'], 'id'));
        self::assertSame(['Gama', 'Epsilon', 'Beta', 'Alfa'], array_column($s[2]['dados']['items'], 'nome'));
        self::assertSame(['beta'], array_column($s[3]['dados']['items'], 'id'));
        self::assertSame(['gama'], array_column($s[4]['dados']['items'], 'id'));
        foreach ([5, 6, 7, 8] as $i) self::assertSame(400, $s[$i]['code'], "cenário $i");
        self::assertSame(0, $s[9]['dados']['pagination']['total']);
        self::assertContains(['listar', 'admin-categorias', 3], $r['acessos']);
    }

    public function testLerPorIdAlfanumericoENumerico(): void
    {
        $r = $this->rodar(<<<'PHP'
$a = req('POST', '', [], ['nome' => 'Esportes']);
$saida = [req('GET', 'esportes'), req('GET', (string)$a['dados']['id_categorias']), req('GET', 'nao-existe'), req('GET', "x' OR '1'='1")];
PHP);
        [$porSlug, $porNumero, $ausente, $injecao] = $r['saida'];
        self::assertSame(200, $porSlug['code']);
        self::assertSame('Esportes', $porSlug['dados']['nome']);
        self::assertSame($porSlug['dados'], $porNumero['dados']);
        self::assertSame(404, $ausente['code']);
        self::assertSame(404, $injecao['code']);
        self::assertContains(['visualizar', 'admin-categorias', 3], $r['acessos']);
    }

    public function testAtualizarParcialTotalEConflitoDeVersao(): void
    {
        $r = $this->rodar(<<<'PHP'
req('POST', '', [], ['nome' => 'Cultura', 'id_modulos' => 2]);
$saida = [
    req('PATCH', 'cultura', [], ['nome' => 'Cultura e Arte']),
    req('PUT', 'cultura', [], ['plugin' => 'x']),
    req('PATCH', 'cultura', [], ['nome' => 'Velha', 'versao' => 1]),
    req('PATCH', 'cultura', [], ['nome' => 'Velha'], null, '"1"'),
    req('PUT', 'cultura', [], ['nome' => 'Cultura', 'id_modulos' => null, 'versao' => 2]),
    req('PATCH', 'cultura', [], []),
    req('PATCH', 'cultura', [], ['versao' => 'abc', 'nome' => 'Y']),
    req('PATCH', 'cultura', [], ['nome' => '']),
];
PHP);
        $s = $r['saida'];

        self::assertSame(200, $s[0]['code']);
        self::assertSame('Cultura e Arte', $s[0]['dados']['nome']);
        self::assertSame('2', (string)$s[0]['dados']['versao']);
        self::assertSame('cultura', $s[0]['dados']['id'], 'O id alfanumérico não muda na edição pela API');
        self::assertSame('2', (string)$s[0]['dados']['id_modulos']);
        self::assertSame(400, $s[1]['code'], 'PUT exige os obrigatórios');
        self::assertSame(409, $s[2]['code']);
        self::assertSame(2, $s[2]['detalhes']['versao_atual']);
        self::assertSame(409, $s[3]['code'], 'If-Match desatualizado');
        self::assertSame(200, $s[4]['code']);
        self::assertSame('3', (string)$s[4]['dados']['versao']);
        self::assertNull($s[4]['dados']['id_modulos']);
        self::assertSame(400, $s[5]['code']);
        self::assertSame(400, $s[6]['code']);
        self::assertSame(400, $s[7]['code']);
        self::assertContains(['editar', 'admin-categorias', 3], $r['acessos']);
    }

    public function testExcluirLogicamenteEsconderDasDemaisRotas(): void
    {
        $r = $this->rodar(<<<'PHP'
req('POST', '', [], ['nome' => 'Saúde']);
$saida = [req('DELETE', 'saude'), req('GET', 'saude'), req('DELETE', 'saude'), linha("SELECT status, versao FROM categorias WHERE id='saude'"), req('POST', '', [], ['nome' => 'Saúde'])];
PHP);
        [$exclusao, $leitura, $segunda, $banco, $recriada] = $r['saida'];
        self::assertSame(200, $exclusao['code']);
        self::assertSame(['id' => 'saude', 'id_categorias' => $exclusao['dados']['id_categorias'], 'exclusao' => 'logica'], $exclusao['dados']);
        self::assertSame(404, $leitura['code']);
        self::assertSame(404, $segunda['code']);
        self::assertSame(['status' => 'D', 'versao' => 2], $banco);
        // O id do registro excluído volta a ficar livre (mesma regra do painel).
        self::assertSame('saude', $recriada['dados']['id']);
        self::assertContains(['excluir', 'admin-categorias', 3], $r['acessos']);
    }

    public function testStatusAlternaOuDefineEValida(): void
    {
        $r = $this->rodar(<<<'PHP'
req('POST', '', [], ['nome' => 'Lazer']);
$saida = [req('POST', 'lazer/status'), req('POST', 'lazer/status'), req('POST', 'lazer/status', [], ['status' => 'I']), req('POST', 'lazer/status', [], ['status' => 'D'])];
PHP);
        $s = $r['saida'];
        self::assertSame(['I', 'A', 'I'], [$s[0]['dados']['status'], $s[1]['dados']['status'], $s[2]['dados']['status']]);
        self::assertSame('4', (string)$s[2]['dados']['versao']);
        self::assertSame(400, $s[3]['code']);
        self::assertContains(['status', 'admin-categorias', 3], $r['acessos']);
    }

    public function testClonarCopiaCamposESobrepoeOCorpo(): void
    {
        $r = $this->rodar(<<<'PHP'
req('POST', '', [], ['nome' => 'Política', 'id_modulos' => 9, 'plugin' => 'p1']);
req('PATCH', 'politica', [], ['nome' => 'Política Nacional']);
$saida = [req('POST', 'politica/clone'), req('POST', 'politica/clone', [], ['nome' => 'Economia', 'plugin' => null]), req('POST', 'politica/clone', [], ['senha' => 'x'])];
PHP);
        [$a, $b, $c] = $r['saida'];
        self::assertSame(201, $a['code']);
        self::assertSame('politica-nacional', $a['dados']['id']);
        self::assertSame('Política Nacional', $a['dados']['nome']);
        self::assertSame('9', (string)$a['dados']['id_modulos']);
        self::assertSame('p1', $a['dados']['plugin']);
        self::assertSame('1', (string)$a['dados']['versao']);
        self::assertSame('economia', $b['dados']['id']);
        self::assertNull($b['dados']['plugin']);
        self::assertSame(400, $c['code']);
        self::assertContains(['clonar', 'admin-categorias', 3], $r['acessos']);
    }

    public function testRbacDoPerfilEscopoDoTokenERotas(): void
    {
        $r = $this->rodar(<<<'PHP'
req('POST', '', [], ['nome' => 'Base']);
$leitor = ['id_usuarios' => 8, 'id_usuarios_perfis' => 4, 'scope' => 'read'];
$movel = ['id_usuarios' => 9, 'id_usuarios_perfis' => 4, 'scope' => 'mobile'];
$vazio = ['id_usuarios' => 10, 'id_usuarios_perfis' => 4, 'scope' => ''];
$GLOBALS['negados'] = ['excluir'];
$saida = [
    req('DELETE', 'base'),
    req('GET', 'base', [], [], $leitor),
    req('POST', '', [], ['nome' => 'Outra'], $leitor),
    req('PATCH', 'base', [], ['nome' => 'Z'], $movel),
    req('GET', '', [], [], $vazio),
    req('DELETE', ''),
    req('PUT', 'base/status'),
    req('GET', 'base/historico'),
    req('GET', 'base/status/extra'),
];
PHP);
        $s = $r['saida'];
        self::assertSame(403, $s[0]['code']);
        self::assertStringContainsString('excluir', $s[0]['mensagem']);
        self::assertSame(200, $s[1]['code']);
        self::assertSame(403, $s[2]['code']);
        self::assertSame('write', $s[2]['detalhes']['necessario']);
        self::assertSame(200, $s[3]['code']);
        self::assertSame(403, $s[4]['code']);
        self::assertSame(405, $s[5]['code']);
        self::assertSame(405, $s[6]['code']);
        self::assertSame(404, $s[7]['code']);
        self::assertSame(404, $s[8]['code']);
        // Escopo insuficiente é recusado antes de consultar o perfil.
        self::assertNotContains(['adicionar', 'admin-categorias', 4], $r['acessos']);
    }

    public function testPluginComIdiomaHooksEExclusaoDefinitiva(): void
    {
        $r = $this->rodar(<<<'PHP'
$m = 'itens-teste';
$c = req('POST', '', ['language' => 'en'], ['titulo' => 'primeiro'], null, null, $m);
$saida = [
    api_crud_modulo($m),
    $c,
    req('POST', '', [], ['titulo' => 'proibido'], null, null, $m),
    req('GET', '', [], [], null, null, $m),
    req('GET', '', ['language' => 'en'], [], null, null, $m),
    linha("SELECT segredo, language FROM itens_teste"),
    req('POST', '', ['language' => 'en'], ['titulo' => 'travado'], null, null, $m),
    req('DELETE', 'travado', ['language' => 'en'], [], null, null, $m),
    req('DELETE', 'primeiro', ['language' => 'en'], [], null, null, $m),
    linha("SELECT COUNT(*) AS n FROM itens_teste WHERE id='primeiro'"),
];
PHP);
        [$cfg, $criado, $recusado, $listaPt, $listaEn, $banco, , $travado, $excluido, $restantes] = $r['saida'];

        // Colunas de controle declaradas em "campos" são descartadas; segredo não é legível.
        self::assertSame(['titulo'], $cfg['campos']);
        self::assertNotContains('segredo', $cfg['leitura']);
        self::assertContains('language', $cfg['leitura']);
        self::assertSame(201, $criado['code']);
        self::assertSame('PRIMEIRO', $criado['dados']['titulo']);
        self::assertSame('primeiro', $criado['dados']['id']);
        self::assertSame(422, $recusado['code']);
        self::assertSame(0, $listaPt['dados']['pagination']['total']);
        self::assertSame(['primeiro'], array_column($listaEn['dados']['items'], 'id'));
        self::assertSame(['segredo' => null, 'language' => 'en'], $banco, 'Hook não grava coluna fora da lista');
        self::assertSame(409, $travado['code']);
        self::assertSame(200, $excluido['code']);
        self::assertSame('definitiva', $excluido['dados']['exclusao']);
        self::assertSame(0, (int)$restantes['n']);
        self::assertSame(['before_create', 'after_create:primeiro', 'before_create', 'before_create', 'after_create:travado', 'before_delete', 'before_delete', 'after_delete:definitiva'], $r['ciclo']);
    }

    /** REV-001 F1: permissão residual de operação não substitui o vínculo do perfil ao módulo (gestor_acesso real). */
    public function testRbacRealExigeVinculoDoPerfilAoModulo(): void
    {
        $r = $this->rodar(<<<'PHP'
$pdo = $GLOBALS['pdo'];
$pdo->exec("INSERT INTO usuarios_perfis VALUES (3, 'restrito');
INSERT INTO modulos_operacoes VALUES ('editar-categorias', 'editar', 'pt-br', 'admin-categorias', 'A');
INSERT INTO modulos_operacoes VALUES ('excluir-categorias', 'excluir', 'pt-br', 'admin-categorias', 'A');
INSERT INTO usuarios_perfis_modulos_operacoes (operacao, perfil) VALUES ('editar-categorias', 'restrito');");
$cfg = api_crud_config('admin-categorias');
api_crud_inserir($cfg, ['nome' => 'Base'], $USUARIO, 'pt-br');
$semVinculo = [
    req('GET', 'base'),
    req('PATCH', 'base', [], ['nome' => 'Alterado sem acesso ao modulo']),
    req('POST', 'base/status'),
    linha("SELECT nome, status, versao FROM categorias WHERE id='base'"),
];
$pdo->exec("INSERT INTO usuarios_perfis_modulos (modulo, perfil) VALUES ('admin-categorias', 'restrito')");
$comVinculo = [
    req('PATCH', 'base', [], ['nome' => 'Alterado com acesso']),
    req('DELETE', 'base'),
    req('POST', 'base/status'),
    req('GET', 'base'),
];
$saida = [$semVinculo, $comVinculo];
PHP, [], true);
        [[$get, $patch, $status, $banco], [$patchOk, $deleteSemOperacao, $statusNaoCadastrado, $getOk]] = $r['saida'];

        self::assertSame(403, $get['code']);
        self::assertSame(403, $patch['code'], 'Permissão residual de editar sem vínculo ao módulo');
        self::assertStringContainsString('admin-categorias', $patch['mensagem']);
        self::assertSame(403, $status['code']);
        self::assertSame(['nome' => 'Base', 'status' => 'A', 'versao' => 1], $banco);
        self::assertSame(200, $patchOk['code']);
        self::assertSame('Alterado com acesso', $patchOk['dados']['nome']);
        self::assertSame(403, $deleteSemOperacao['code'], 'Operação cadastrada sem permissão do perfil');
        self::assertStringContainsString('excluir', $deleteSemOperacao['mensagem']);
        self::assertSame(200, $statusNaoCadastrado['code'], 'Operação não cadastrada: vale o acesso ao módulo');
        self::assertSame(200, $getOk['code']);
    }

    /** REV-001 F2: escrita recusada pelo driver vira 500, sem hooks after_* e sem devolver outro registro. */
    public function testFalhaDePersistenciaResponde500SemHooksAfter(): void
    {
        $r = $this->rodar(<<<'PHP'
$m = 'itens-teste';
req('POST', '', [], ['nome' => 'Base']);
req('POST', '', ['language' => 'en'], ['titulo' => 'fixo'], null, null, $m);
$GLOBALS['ciclo'] = [];
$GLOBALS['falhar_escritas'] = true;
$saida = [
    req('POST', '', [], ['nome' => 'Novo que falha']),
    req('PATCH', 'base', [], ['nome' => 'Update que falha']),
    req('PUT', 'base', [], ['nome' => 'Put que falha', 'versao' => 1]),
    req('DELETE', 'base'),
    req('POST', 'base/status'),
    req('POST', 'base/clone'),
    req('POST', '', ['language' => 'en'], ['titulo' => 'outro'], null, null, $m),
    req('DELETE', 'fixo', ['language' => 'en'], [], null, null, $m),
];
$GLOBALS['falhar_escritas'] = false;
$saida[] = linha("SELECT COUNT(*) AS n, MAX(nome) AS nome, MAX(status) AS status, MAX(versao) AS versao FROM categorias");
$saida[] = linha("SELECT COUNT(*) AS n FROM itens_teste");
PHP);
        $s = $r['saida'];
        foreach (range(0, 7) as $i) {
            self::assertTrue($s[$i]['erro'] ?? false, "cenário $i");
            self::assertSame(500, $s[$i]['code'], "cenário $i");
            self::assertSame('Não foi possível gravar o registro. Tente novamente.', $s[$i]['mensagem'], "cenário $i");
            self::assertArrayNotHasKey('dados', $s[$i], "cenário $i");
        }
        self::assertSame(['n' => 1, 'nome' => 'Base', 'status' => 'A', 'versao' => 1], $s[8]);
        self::assertSame(1, (int)$s[9]['n']);
        // before_* rodam; nenhum after_* depois da falha.
        self::assertSame(['before_create', 'before_delete'], $r['ciclo']);
        // Detalhe técnico fica no log do servidor, não na resposta.
        self::assertNotEmpty($r['logs']);
        foreach (range(0, 7) as $i) self::assertStringNotContainsString('categorias', $s[$i]['mensagem']);
    }

    /** REV-001 F2: INSERT que não grava linha não pode devolver o registro do último id do driver. */
    public function testInsercaoSemLinhaGravadaNaoDevolveRegistroAnterior(): void
    {
        $r = $this->rodar(<<<'PHP'
req('POST', '', [], ['nome' => 'Base']);
$GLOBALS['pdo']->exec("CREATE TRIGGER ignora_insert BEFORE INSERT ON categorias BEGIN SELECT RAISE(IGNORE); END");
$saida = [req('POST', '', [], ['nome' => 'Fantasma']), linha("SELECT COUNT(*) AS n FROM categorias")];
PHP);
        [$criado, $banco] = $r['saida'];
        self::assertSame(500, $criado['code']);
        self::assertArrayNotHasKey('dados', $criado);
        self::assertSame(1, (int)$banco['n']);
    }

    /** REV-001 F3: o UPDATE condicional decide; perder a disputa depois do pré-teste é 409. */
    public function testConcorrenciaDepoisDoPreTesteResponde409(): void
    {
        $r = $this->rodar(<<<'PHP'
req('POST', '', [], ['nome' => 'Base']);
$cfg = api_crud_config('admin-categorias');
$outro = function () { $GLOBALS['pdo']->exec("UPDATE categorias SET nome='Outro escritor', versao=versao+1 WHERE id='base'"); };

// Pela rota: a versão 1 confere no pré-teste; outro escritor grava antes do UPDATE condicional.
$GLOBALS['antes_da_escrita'] = $outro;
$GLOBALS['ciclo'] = [];
$rota = req('PATCH', 'base', [], ['nome' => 'Minha escrita', 'versao' => 1]);
$depoisRota = linha("SELECT nome, versao FROM categorias WHERE id='base'");

// Primitiva, como na sonda da revisão: snapshot v2, outro escritor grava v3.
$snapshot = api_crud_buscar($cfg, 'base', 'pt-br');
$outro();
$primitiva = api_crud_gravar($cfg, $snapshot, ['nome' => 'Minha escrita'], (int)$snapshot['versao']);

// Sem versão: UPDATE que afeta zero linhas (registro removido/ignorado) também é conflito.
$GLOBALS['pdo']->exec("CREATE TRIGGER ignora_update BEFORE UPDATE ON categorias BEGIN SELECT RAISE(IGNORE); END");
$semVersao = req('PATCH', 'base', [], ['nome' => 'Nao persistiu']);
$exclusao = req('DELETE', 'base');
$GLOBALS['pdo']->exec("DROP TRIGGER ignora_update");

// Falha SQL é distinta de conflito: 500.
$GLOBALS['pdo']->exec("CREATE TRIGGER aborta_update BEFORE UPDATE ON categorias BEGIN SELECT RAISE(ABORT, 'falha'); END");
$falha = req('PATCH', 'base', [], ['nome' => 'Falha SQL']);
$GLOBALS['pdo']->exec("DROP TRIGGER aborta_update");

$saida = [$rota, $depoisRota, $primitiva, $semVersao, $exclusao, $falha, linha("SELECT nome, status, versao FROM categorias WHERE id='base'")];
PHP);
        [$rota, $depoisRota, $primitiva, $semVersao, $exclusao, $falha, $banco] = $r['saida'];

        self::assertSame(409, $rota['code']);
        self::assertSame('Conflito de versão: o registro foi alterado por outra operação.', $rota['mensagem']);
        self::assertSame(2, $rota['detalhes']['versao_atual']);
        self::assertSame(['nome' => 'Outro escritor', 'versao' => 2], $depoisRota);
        self::assertTrue($primitiva['erro']);
        self::assertSame(409, $primitiva['code']);
        self::assertSame(3, $primitiva['detalhes']['versao_atual']);
        self::assertSame(409, $semVersao['code']);
        self::assertSame(409, $exclusao['code']);
        self::assertSame(500, $falha['code']);
        self::assertSame(['nome' => 'Outro escritor', 'status' => 'A', 'versao' => 3], $banco);
    }

    /** REV-001 F3: exclusão definitiva que não apaga linha (outro removeu antes) é 409, sem after_delete. */
    public function testExclusaoDefinitivaConcorrenteResponde409(): void
    {
        $r = $this->rodar(<<<'PHP'
$m = 'itens-teste';
req('POST', '', ['language' => 'en'], ['titulo' => 'alvo'], null, null, $m);
$GLOBALS['ciclo'] = [];
$GLOBALS['antes_da_escrita'] = function () { $GLOBALS['pdo']->exec("DELETE FROM itens_teste WHERE id='alvo'"); };
$saida = req('DELETE', 'alvo', ['language' => 'en'], [], null, null, $m);
PHP);
        self::assertSame(409, $r['saida']['code']);
        self::assertSame(['before_delete'], $r['ciclo']);
    }

    /** REV-001 F4: mensagens públicas vêm das variáveis pt-br/en. */
    public function testMensagensDaApiVemDasVariaveisPtBrEEn(): void
    {
        $r = $this->rodar(<<<'PHP'
req('POST', '', [], ['nome' => 'Base']);
$lote = function () {
    return [
        req('POST', '', [], ['nome' => 'X', 'senha' => 'abc'])['mensagem'],
        req('GET', 'nao-existe')['mensagem'],
        req('PATCH', 'base', [], ['nome' => 'Y', 'versao' => 99])['mensagem'],
        req('DELETE', '')['mensagem'],
        req('POST', '', [], ['nome' => 'Z'], ['id_usuarios' => 1, 'id_usuarios_perfis' => 3, 'scope' => 'read'])['mensagem'],
        req('POST', '', [], ['nome' => 'Criado ' . $GLOBALS['_GESTOR']['linguagem-codigo']])['mensagem'],
    ];
};
$pt = $lote();
$GLOBALS['_GESTOR']['linguagem-codigo'] = 'en';
$en = $lote();
$saida = [$pt, $en];
PHP);
        [$pt, $en] = $r['saida'];
        self::assertSame([
            'Campos não permitidos: senha',
            'Registro não encontrado: nao-existe',
            'Conflito de versão: o registro foi alterado por outra operação.',
            'Método não permitido. Use: GET, POST.',
            'Escopo do token insuficiente para esta operação.',
            'Registro criado.',
        ], $pt);
        self::assertSame([
            'Fields not allowed: senha',
            'Record not found: nao-existe',
            'Version conflict: the record was changed by another operation.',
            'Method not allowed. Use: GET, POST.',
            'Token scope is insufficient for this operation.',
            'Record created.',
        ], $en);
    }

    /** REV-001 F4: todo id de mensagem usado no motor e no controlador está cadastrado nos dois idiomas. */
    public function testIdsDeMensagemCadastradosEmPtBrEEn(): void
    {
        $fonte = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/api-crud.php')
            . (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/api/api.php');
        preg_match_all("/'(api-crud-[a-z0-9-]+)'/", $fonte, $m);
        $usados = array_values(array_unique($m[1]));
        self::assertGreaterThanOrEqual(25, count($usados));

        foreach (['pt-br', 'en'] as $idioma) {
            $variaveis = json_decode((string)file_get_contents(CONN2FLOW_GESTOR_ROOT . "/resources/$idioma/variables.json"), true);
            $globais = [];
            foreach ($variaveis as $v) if (empty($v['modulo'])) $globais[$v['id']] = $v['value'];
            foreach ($usados as $id) {
                self::assertNotSame('', $globais[$id] ?? '', "$id ausente em $idioma");
                // Marcadores iguais nos dois idiomas, para a troca do motor funcionar em ambos.
                preg_match_all('/#[a-z]+#/', $globais[$id], $marcadores);
                $porIdioma[$id][$idioma] = $marcadores[0];
            }
        }
        foreach ($porIdioma as $id => $marcadores) self::assertSame($marcadores['pt-br'], $marcadores['en'], $id);
    }

    public function testControladorUsaOMotorDepoisDoHookDoModulo(): void
    {
        $api = str_replace("\r\n", "\n", (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/api/api.php'));
        $inicio = (int)strpos($api, 'function api_handle_modulo(');
        $corpo = substr($api, $inicio, (int)strpos($api, "\n}\n", $inicio) - $inicio);

        self::assertLessThan(strpos($corpo, 'api_crud_despachar('), strpos($corpo, 'api_disparar_hook('));
        self::assertStringContainsString("gestor_incluir_biblioteca('api-crud')", $corpo);
        self::assertStringContainsString('$usuario = api_authenticate(true);', $corpo);
        self::assertStringContainsString('HTTP_IF_MATCH', $corpo);
        self::assertStringContainsString('GET, POST, PUT, PATCH, DELETE, OPTIONS', $api);

        $config = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/config.php');
        self::assertMatchesRegularExpression("/'api-crud'\\s*=>\\s*Array\\('api-crud\\.php'\\)/", $config);
    }
}
