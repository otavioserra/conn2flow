<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-032 / BATCH-026 — ecossistema de rotinas automáticas.
 *
 * O que este teste protege, em ordem de risco:
 *
 * 1. A derivação da expressão cron. Ela existe em UM lugar (bibliotecas/cron.php) e é consumida
 *    pelo compilador de recursos, pelo painel e — pelos mesmos valores — pelo agendador HestiaCP.
 *    Divergir aqui produz um agendamento silenciosamente diferente do declarado.
 * 2. O contrato entre o template e o JavaScript. Foi a classe de bug dominante do BATCH-024:
 *    o JS lia ids e atributos que nenhum template declarava, e a tela ficava inerte sem erro.
 * 3. A ausência de texto literal na interface, exigida pela governança i18n.
 * 4. A separação entre autoria (arquivo do módulo) e estado operacional (painel), sem a qual
 *    pausar uma tarefa seria desfeito pela sincronização seguinte.
 */
final class AdminCronReq032Test extends TestCase
{
    private const MODULO = 'admin-cron';

    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'cron.php';
    }

    private static function moduloPath(string $arquivo = ''): string
    {
        $base = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . self::MODULO;
        return $arquivo === '' ? $base : $base . DIRECTORY_SEPARATOR . $arquivo;
    }

    private static function manifesto(): array
    {
        $json = json_decode((string)file_get_contents(self::moduloPath('admin-cron.json')), true);
        self::assertIsArray($json, 'admin-cron.json deve ser um JSON válido');
        return $json;
    }

    // ===================== 1. Vocabulário e expressões =====================

    public function testFrequenciasSaoAsQuatroJanelasMaisACustomizada(): void
    {
        self::assertSame(
            ['minutario', 'horario', 'diario', 'mensal', 'customizado'],
            cron_frequencias_validas()
        );
    }

    public function testExpressaoValidaAceitaCincoCamposERejeitaORestante(): void
    {
        self::assertTrue(cron_expressao_valida('*/10 * * * *'));
        self::assertTrue(cron_expressao_valida('0 3 * * *'));
        self::assertTrue(cron_expressao_valida('0,30 1-5 1 * *'));

        // Quatro e seis campos são os erros de digitação prováveis.
        self::assertFalse(cron_expressao_valida('0 3 * *'));
        self::assertFalse(cron_expressao_valida('0 3 * * * *'));
        self::assertFalse(cron_expressao_valida(''));

        // O ponto da validação: nada além de dígitos e dos operadores do crontab pode passar,
        // porque a string vai virar linha de crontab num servidor real.
        self::assertFalse(cron_expressao_valida('0 3 * * * ; rm -rf /'));
        self::assertFalse(cron_expressao_valida('@daily'));
    }

    public function testExpressaoExplicitaTemPrecedenciaSobreAFrequencia(): void
    {
        self::assertSame(
            '5 2 * * 1',
            cron_expressao_declarada(['expressao_cron' => '5 2 * * 1'], 'diario')
        );
    }

    public function testExpressaoExplicitaInvalidaInvalidaADeclaracao(): void
    {
        self::assertNull(cron_expressao_declarada(['expressao_cron' => 'todo dia'], 'diario'));
    }

    public function testDerivacaoPorFrequenciaUsaOsHorariosPadrao(): void
    {
        self::assertSame('*/10 * * * *', cron_expressao_declarada([], 'minutario'));
        self::assertSame('0 * * * *', cron_expressao_declarada([], 'horario'));
        self::assertSame('0 3 * * *', cron_expressao_declarada([], 'diario'));
        self::assertSame('0 4 1 * *', cron_expressao_declarada([], 'mensal'));
    }

    public function testDerivacaoRespeitaHoraEDiaDeclarados(): void
    {
        self::assertSame('30 2 * * *', cron_expressao_declarada(['hora' => '02:30'], 'diario'));
        self::assertSame('15 * * * *', cron_expressao_declarada(['hora' => '07:15'], 'horario'));
        self::assertSame('0 5 10 * *', cron_expressao_declarada(['hora' => '05:00', 'dia' => 10], 'mensal'));
    }

    public function testHoraOuDiaForaDaFaixaInvalidaADeclaracao(): void
    {
        self::assertNull(cron_expressao_declarada(['hora' => '25:00'], 'diario'));
        self::assertNull(cron_expressao_declarada(['hora' => '3h'], 'diario'));
        self::assertNull(cron_expressao_declarada(['hora' => '05:00', 'dia' => 0], 'mensal'));
        self::assertNull(cron_expressao_declarada(['hora' => '05:00', 'dia' => 32], 'mensal'));
    }

    public function testCustomizadoSemExpressaoNaoViraAgendamentoImplicito(): void
    {
        // Derivar um padrão aqui criaria um agendamento que ninguém declarou.
        self::assertNull(cron_expressao_declarada([], 'customizado'));
        self::assertNull(cron_expressao_padrao('customizado'));
        self::assertSame('0 3 * * *', cron_expressao_declarada(['expressao_cron' => '0 3 * * *'], 'customizado'));
    }

    // ===================== 2. Executor =====================

    public function testCallbackInexistenteViraErroEmVezDeFatal(): void
    {
        $resultado = cron_tarefa_executar([
            'id' => 'tarefa-fantasma',
            'modulo' => 'modulo-que-nao-existe',
            'funcao_callback' => 'funcao_que_nao_existe_em_lugar_nenhum',
        ]);

        self::assertSame('erro', $resultado['status']);
        self::assertStringContainsString('Callback nao encontrado', $resultado['log']);
    }

    public function testNomeDeCallbackInvalidoERecusadoAntesDeQualquerChamada(): void
    {
        // Sem esta guarda, o valor cairia em call_user_func e viraria execução arbitrária.
        $resultado = cron_tarefa_executar([
            'id' => 'tarefa-suja',
            'modulo' => 'sistema',
            'funcao_callback' => 'system("id")',
        ]);

        self::assertSame('erro', $resultado['status']);
        self::assertStringContainsString('invalido', $resultado['log']);
    }

    public function testExcecaoNoCallbackNaoInterrompeOTickEViraStatusErro(): void
    {
        if (!function_exists('admin_cron_teste_callback_explode')) {
            function admin_cron_teste_callback_explode($parametros) {
                throw new RuntimeException('falha proposital');
            }
        }

        $resultado = cron_tarefa_executar([
            'id' => 'tarefa-que-explode',
            'modulo' => 'sistema',
            'funcao_callback' => 'admin_cron_teste_callback_explode',
        ]);

        self::assertSame('erro', $resultado['status']);
        self::assertStringContainsString('falha proposital', $resultado['log']);
        self::assertIsInt($resultado['duracao']);
    }

    public function testSaidaDoCallbackViraLogEOContratoDeAvisoEHonrado(): void
    {
        if (!function_exists('admin_cron_teste_callback_aviso')) {
            function admin_cron_teste_callback_aviso($parametros) {
                echo 'processados: ' . (isset($parametros['lote']) ? $parametros['lote'] : 0);
                return ['status' => 'aviso', 'log' => ' (parcial)'];
            }
        }

        $resultado = cron_tarefa_executar([
            'id' => 'tarefa-aviso',
            'modulo' => 'sistema',
            'funcao_callback' => 'admin_cron_teste_callback_aviso',
            'parametros' => '{"lote":7}',
        ]);

        self::assertSame('aviso', $resultado['status']);
        self::assertStringContainsString('processados: 7', $resultado['log']);
        self::assertStringContainsString('(parcial)', $resultado['log']);
    }

    public function testRetornoFalseViraErro(): void
    {
        if (!function_exists('admin_cron_teste_callback_false')) {
            function admin_cron_teste_callback_false($parametros) { return false; }
        }

        $resultado = cron_tarefa_executar([
            'id' => 'tarefa-false',
            'modulo' => 'sistema',
            'funcao_callback' => 'admin_cron_teste_callback_false',
        ]);

        self::assertSame('erro', $resultado['status']);
    }

    // ===================== 3. Engine de linha de comando =====================

    public function testEngineNaoIncluiOControladorDeModuloNemDuplicaOExecutor(): void
    {
        $engine = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'cron.php');

        // Incluir `<modulo>.php` dispararia `<modulo>_start()` — renderização de interface
        // dentro de uma rotina de fundo.
        self::assertStringNotContainsString('modulos-path\'].$modulo.\'/\'.$modulo.\'.php\'', $engine);

        // O executor mora na biblioteca; redefini-lo aqui faria o "Disparar agora" do painel e o
        // tick agendado divergirem.
        self::assertStringNotContainsString('function cron_tarefa_executar', $engine);
        self::assertStringContainsString("bibliotecas-path'] . 'cron.php'", $engine);
    }

    public function testEngineDespachaPorFrequenciaENaoPorHookFixo(): void
    {
        $engine = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'cron.php');

        self::assertStringContainsString('frequencia=', $engine);
        self::assertStringContainsString('cron_tarefas_carregar($frequencia', $engine);

        // A versão anterior notificava sempre 'diario', qualquer que fosse a janela.
        self::assertStringNotContainsString("hook_do_action('cron', 'diario')", $engine);
        self::assertStringContainsString("hook_do_action('cron', \$frequencia)", $engine);
    }

    public function testEngineNaoTemServidorNemPlataformaFixadosNoCodigo(): void
    {
        $engine = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'cron.php');

        self::assertStringNotContainsString('beta.conn2flow.com', $engine);
        self::assertStringNotContainsString("plataforma_id = 'beta'", $engine);
        self::assertStringContainsString('cron_detectar_host', $engine);
    }

    public function testConfigResolveOAmbienteDeCronAntesDoRamoGenericoDeCli(): void
    {
        $config = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'config.php');

        $posCron = strpos($config, 'if(isset($_CRON)){');
        $posCli = strpos($config, "php_sapi_name() === 'cli'");

        self::assertIsInt($posCron);
        self::assertIsInt($posCli);

        // O cron.php roda sob o SAPI cli. Na ordem inversa o host resolvido era descartado e o
        // .env carregado vinha da primeira pasta de autenticacoes/, não a do domínio agendado.
        self::assertLessThan($posCli, $posCron, 'o ramo de $_CRON deve ser avaliado antes do de CLI');
    }

    public function testBibliotecasSaoIncluidasPorCaminhoAbsoluto(): void
    {
        $config = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'config.php');

        // O agendador do HestiaCP chama o cron.php a partir do home do usuário; um require
        // relativo a 'bibliotecas/' só resolveria com o CWD dentro de gestor/.
        self::assertStringContainsString(
            "require_once(\$_GESTOR['bibliotecas-path'].\$_caminho);",
            $config
        );
    }

    // ===================== 4. Compilador de recursos =====================

    public function testCompiladorColetaAChaveCronEUsaABibliotecaCompartilhada(): void
    {
        $compilador = (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores' . DIRECTORY_SEPARATOR
            . 'agents' . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR
            . 'atualizacao-dados-recursos.php'
        );

        self::assertStringContainsString("gestor/bibliotecas/cron.php", $compilador);
        self::assertStringContainsString("\$data['cron']", $compilador);
        self::assertStringContainsString('CronTarefasData.json', $compilador);
        self::assertStringContainsString('cron_expressao_declarada($tarefa, $frequencia)', $compilador);

        // Duplicar a derivação faria disco e painel divergirem sem nada acusar.
        self::assertStringNotContainsString('function cronExpressaoDeclarada', $compilador);
        self::assertStringNotContainsString('function cronFrequenciasValidas', $compilador);
    }

    public function testTabelaDeCronPreservaOEstadoOperacionalDoPainel(): void
    {
        $config = json_decode((string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'resources'
            . DIRECTORY_SEPARATOR . 'tables_config.json'
        ), true);

        self::assertArrayHasKey('cron_tarefas', $config['tabelas']);
        $tabela = $config['tabelas']['cron_tarefas'];

        self::assertSame('natural_key', $tabela['config']['strategy']);
        // A tarefa agendada não é um recurso por idioma: a chave é só o slug.
        self::assertSame(['id'], $tabela['config']['natural_key_columns']);

        // Sem isto, pausar uma tarefa no painel seria revertido pelo próximo resources:sync.
        foreach (['ativo', 'expressao_cron', 'parametros'] as $campo) {
            self::assertContains(
                $campo,
                $tabela['config']['preserve_on_user_modified'],
                "{$campo} é estado operacional e precisa sobreviver à sincronização"
            );
        }
    }

    public function testMigrationCriaAsColunasDoContratoDaRequisicao(): void
    {
        $migration = (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'migrations'
            . DIRECTORY_SEPARATOR . '20260901120000_create_cron_tarefas_table.php'
        );

        $colunas = [
            'id', 'nome', 'descricao', 'modulo', 'frequencia', 'expressao_cron', 'funcao_callback',
            'parametros', 'ativo', 'ultimo_disparo', 'ultima_duracao_ms', 'ultimo_status',
            'ultimo_log', 'origem', 'data_criacao', 'data_modificacao',
        ];
        foreach ($colunas as $coluna) {
            self::assertStringContainsString("addColumn('{$coluna}'", $migration, "coluna {$coluna} ausente");
        }

        // O despacho filtra por frequencia + ativo a cada tick.
        self::assertStringContainsString("addIndex(['frequencia', 'ativo'])", $migration);
        self::assertStringContainsString("addIndex(['id'], ['unique' => true])", $migration);

        // ENUM não existe em SQLite (onde esta suíte roda) — ver D-037.
        self::assertStringNotContainsString("'enum'", $migration);
    }

    // ===================== 5. Manifesto e recursos do módulo =====================

    public function testPaginaDeclaraTailwindEOLayoutAdministrativoTailwind(): void
    {
        $manifesto = self::manifesto();

        foreach (['pt-br', 'en'] as $lang) {
            $pagina = $manifesto['resources'][$lang]['pages'][0];
            self::assertSame('tailwindcss', $pagina['framework_css'], "[{$lang}] framework_css");
            self::assertSame('layout-administrativo-tailwind', $pagina['layout'], "[{$lang}] layout");
            self::assertSame('admin-cron/', $pagina['path']);

            // 'listar' cairia em interface_listar_iniciar() e montaria a listagem padrão por
            // cima da tela Tailwind.
            self::assertSame('painel', $pagina['option'], "[{$lang}] opção de rota");
        }
    }

    public function testTailwindSourcesApontaApenasParaOScriptQueMontaClasses(): void
    {
        $manifesto = self::manifesto();

        foreach (['pt-br', 'en'] as $lang) {
            $pagina = $manifesto['resources'][$lang]['pages'][0];

            // A skill de Tailwind classifica tailwind_sources em PHP/JS como dívida técnica; o
            // JS entra porque as etiquetas de status são montadas em runtime, e a razão precisa
            // estar declarada para que a dívida seja rastreável.
            self::assertSame(['../../../../admin-cron.js'], $pagina['tailwind_sources'], "[{$lang}]");
            self::assertNotEmpty($pagina['tailwind_sources_reason'], "[{$lang}] razão obrigatória");
        }
    }

    /**
     * Os dois md5 possíveis do mesmo arquivo: com LF e com CRLF.
     *
     * req-155 — `buildChecksum()` calcula o md5 dos BYTES lidos do disco, e o disco não é o mesmo
     * em todo lugar. O repositório guarda o HTML com LF (`i/lf`) e o `core.autocrlf` do Windows
     * entrega CRLF na árvore de trabalho (`w/crlf`): o compilador rodando aqui grava o md5 de
     * CRLF, e o runner Linux do CI compara com o de LF. São 233 quebras de linha neste arquivo —
     * 233 bytes de diferença, hash completamente distinto, e um pipeline eternamente "divergente"
     * sem que nada esteja errado com o recurso.
     *
     * Regravar o hash no Windows (o que a req-155 propunha) não fecha o laço: o valor novo é de
     * novo o de CRLF. Aceitar as duas formas mantém a asserção forte — um valor digitado à mão
     * não bate com nenhuma delas — e a torna independente de plataforma.
     *
     * @return list<string>
     */
    private static function md5DoArquivoEmAmbasAsQuebras(string $caminho): array
    {
        $bytes = (string)file_get_contents($caminho);
        $lf = str_replace("\r\n", "\n", $bytes);

        return array_values(array_unique([
            md5($lf),
            md5(str_replace("\n", "\r\n", $lf)),
        ]));
    }

    /**
     * REQ-035 / BATCH-157 — o checksum é DERIVADO, e a asserção precisa refletir isso.
     *
     * A versão anterior exigia string vazia, na leitura de que um valor ali só poderia ter sido
     * escrito à mão. Está errado: `atualizacao-dados-recursos.php` grava o `md5` do HTML de volta
     * no manifesto do módulo como histórico incremental (ORIGIN_UPDATE_MODULE). Zerar o campo
     * fazia o CI passar por uma tarde — o `./c2f resources:sync` seguinte o preenchia de novo e a
     * suíte voltava a quebrar, agora acusando o próprio pipeline de escrita manual.
     *
     * O invariante real é a COINCIDÊNCIA: um checksum presente tem de ser o md5 do arquivo que
     * ele descreve. Assim um valor digitado à mão continua sendo reprovado, e a sincronização
     * passa a ser idempotente.
     */
    public function testChecksumHtmlCoincideComMd5DoArquivo(): void
    {
        $manifesto = self::manifesto();

        foreach (['pt-br', 'en'] as $lang) {
            $pagina = $manifesto['resources'][$lang]['pages'][0];
            $checksum = $pagina['checksum'];

            $html = self::moduloPath(
                'resources' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'pages'
                . DIRECTORY_SEPARATOR . 'admin-cron' . DIRECTORY_SEPARATOR . 'admin-cron.html'
            );
            self::assertFileExists($html, "[{$lang}] HTML do recurso ausente");

            // Vazio = ainda não compilado, e o pipeline calcula no próximo sync. Preenchido só
            // pode ser o md5 do HTML que está em disco.
            if ($checksum['html'] !== '') {
                self::assertContains(
                    $checksum['html'],
                    self::md5DoArquivoEmAmbasAsQuebras($html),
                    "[{$lang}] checksum html divergente do arquivo — valor não veio da compilação"
                );
            }

            // Nenhum campo aceita texto arbitrário: ou está vazio, ou é um md5.
            foreach ($checksum as $tipo => $valor) {
                self::assertMatchesRegularExpression(
                    '/^(|[0-9a-f]{32})$/',
                    (string)$valor,
                    "[{$lang}] checksum {$tipo} não é vazio nem um md5"
                );
            }

            // `combined` é md5(html . css . css_precompiled): sem CSS ele colapsa no md5 do HTML.
            // Divergir aqui significaria que um dos três foi editado isoladamente.
            if ($checksum['css'] === '' && ($checksum['css_precompiled'] ?? '') === '') {
                self::assertSame(
                    $checksum['html'],
                    $checksum['combined'],
                    "[{$lang}] combined precisa colapsar no checksum html quando não há CSS"
                );
            }
        }
    }

    public function testVariaveisSaoSimetricasEntreOsIdiomas(): void
    {
        $manifesto = self::manifesto();

        $ids = [];
        foreach (['pt-br', 'en'] as $lang) {
            $ids[$lang] = array_map(
                static fn(array $v): string => $v['id'],
                $manifesto['resources'][$lang]['variables']
            );
            sort($ids[$lang]);
            self::assertSame(
                array_unique($ids[$lang]),
                $ids[$lang],
                "[{$lang}] há id de variável duplicado"
            );
        }

        // Um id só em pt-br deixaria o painel em inglês com marcador cru na tela.
        self::assertSame($ids['pt-br'], $ids['en']);
    }

    public function testModuloDeclaraABibliotecaDeCron(): void
    {
        $manifesto = self::manifesto();
        self::assertContains('cron', $manifesto['bibliotecas']);
    }

    public function testModuloEstaVinculadoAoPerfilAdministradores(): void
    {
        $vinculos = json_decode((string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'UsuariosPerfisModulosData.json'
        ), true);

        $perfis = [];
        foreach ($vinculos as $v) {
            if (($v['modulo'] ?? '') === self::MODULO) $perfis[] = $v['perfil'];
        }

        // Registrar em `modulos` faz o módulo existir; sem o vínculo de perfil ele não aparece
        // para ninguém — a tela fica publicada e inalcançável.
        self::assertContains('administradores', $perfis, 'admin-cron sem vínculo de perfil');
    }

    public function testRenomearTarefaManualParaUmIdExistenteERecusado(): void
    {
        $php = (string)file_get_contents(self::moduloPath('admin-cron.php'));

        // `id` é a chave natural: sem esta checagem o operador veria um erro de driver do índice
        // único em vez da mensagem de negócio.
        self::assertMatchesRegularExpression(
            '/\$dados\[\'id\'\] !== \$existente\[\'id\'\].*?admin_cron_tarefa\(\$dados\[\'id\'\]\) !== null/s',
            $php
        );
    }

    public function testModuloEstaRegistradoNoMenuNosDoisIdiomas(): void
    {
        $modulos = json_decode((string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'ModulosData.json'
        ), true);

        $encontrados = [];
        foreach ($modulos as $m) {
            if (($m['id'] ?? '') === self::MODULO) $encontrados[$m['language']] = $m;
        }

        // Sem registro na tabela `modulos` o módulo existe em disco mas não aparece no menu.
        self::assertArrayHasKey('pt-br', $encontrados);
        self::assertArrayHasKey('en', $encontrados);
        self::assertSame('administracao-sistema', $encontrados['pt-br']['modulo_grupo_id']);
        self::assertNotEmpty($encontrados['pt-br']['icone_tailwind']);
    }

    // ===================== 6. Contrato template x JavaScript =====================

    /**
     * Converte `data-label-freq-minutario` na chave camelCase que `dataset` expõe.
     */
    private static function datasetChave(string $atributo): string
    {
        $sem = substr($atributo, strlen('data-'));
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $sem))));
    }

    public function testTodoDataLidoPeloJavaScriptExisteNoTemplate(): void
    {
        $js = (string)file_get_contents(self::moduloPath('admin-cron.js'));

        foreach (['pt-br', 'en'] as $lang) {
            $html = (string)file_get_contents(self::moduloPath(
                'resources' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'pages'
                . DIRECTORY_SEPARATOR . 'admin-cron' . DIRECTORY_SEPARATOR . 'admin-cron.html'
            ));

            preg_match_all('/data-[a-z0-9-]+/', $html, $encontrados);
            $declarados = array_map([self::class, 'datasetChave'], array_unique($encontrados[0]));

            preg_match_all('/rotulos\.([A-Za-z0-9]+)/', $js, $usados);
            preg_match_all('/painel\.dataset\.([A-Za-z0-9]+)/', $js, $usadosPainel);
            $lidos = array_unique(array_merge($usados[1], $usadosPainel[1]));

            foreach ($lidos as $chave) {
                self::assertContains(
                    $chave,
                    $declarados,
                    "[{$lang}] o JS lê data-* '{$chave}' que o template não declara"
                );
            }
        }
    }

    public function testTodoIdLidoPeloJavaScriptExisteNoTemplate(): void
    {
        $js = (string)file_get_contents(self::moduloPath('admin-cron.js'));

        preg_match_all("/getElementById\('([^']+)'\)/", $js, $lidos);
        self::assertNotEmpty($lidos[1], 'o teste perdeu o padrão de leitura de ids');

        foreach (['pt-br', 'en'] as $lang) {
            $html = (string)file_get_contents(self::moduloPath(
                'resources' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'pages'
                . DIRECTORY_SEPARATOR . 'admin-cron' . DIRECTORY_SEPARATOR . 'admin-cron.html'
            ));

            foreach (array_unique($lidos[1]) as $id) {
                self::assertStringContainsString(
                    'id="' . $id . '"',
                    $html,
                    "[{$lang}] o JS busca #{$id}, que o template não declara"
                );
            }
        }
    }

    public function testAcoesDeLinhaDoJavaScriptTemTratamentoNoSwitch(): void
    {
        $js = (string)file_get_contents(self::moduloPath('admin-cron.js'));

        // Um data-acao sem case correspondente é exatamente o "botão inerte" do BATCH-024.
        preg_match_all("/data-acao=\"' \+ acao|botaoHtml\('([a-z]+)'/", $js, $emitidas);
        $acoes = array_values(array_filter($emitidas[1]));
        self::assertNotEmpty($acoes);

        foreach (array_unique($acoes) as $acao) {
            self::assertStringContainsString("case '{$acao}':", $js, "ação '{$acao}' sem tratamento");
        }
    }

    public function testTodosOsBotoesDeclaramTypeButton(): void
    {
        foreach (['pt-br', 'en'] as $lang) {
            $html = (string)file_get_contents(self::moduloPath(
                'resources' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'pages'
                . DIRECTORY_SEPARATOR . 'admin-cron' . DIRECTORY_SEPARATOR . 'admin-cron.html'
            ));

            preg_match_all('/<button[^>]*>/', $html, $botoes);
            foreach ($botoes[0] as $botao) {
                // `<button>` sem type é submit: foi a causa real do "F5 involuntário" do BATCH-024.
                self::assertStringContainsString('type="', $botao, "[{$lang}] botão sem type: {$botao}");
            }
        }

        $js = (string)file_get_contents(self::moduloPath('admin-cron.js'));
        self::assertStringContainsString('<button type="button" data-acao=', $js);
    }

    // ===================== 7. Governança i18n =====================

    public function testTemplateNaoTemTextoLiteralVisivel(): void
    {
        foreach (['pt-br', 'en'] as $lang) {
            $html = (string)file_get_contents(self::moduloPath(
                'resources' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'pages'
                . DIRECTORY_SEPARATOR . 'admin-cron' . DIRECTORY_SEPARATOR . 'admin-cron.html'
            ));

            // Remove marcadores do sistema, SVGs e as próprias tags: o que sobrar é texto cru.
            $limpo = preg_replace('/@\[\[[^\]]+\]\]@/', '', $html);
            $limpo = preg_replace('/<svg\b.*?<\/svg>/s', '', (string)$limpo);
            $limpo = preg_replace('/<[^>]+>/', '', (string)$limpo);
            $limpo = preg_replace('/#[a-z_]+#/', '', (string)$limpo);
            $limpo = trim(preg_replace('/\s+/', ' ', (string)$limpo));

            self::assertSame(
                '',
                $limpo,
                "[{$lang}] texto literal no template, fora do sistema de variáveis: '{$limpo}'"
            );
        }
    }

    public function testJavaScriptNaoTemRotuloDeInterfaceEmbutido(): void
    {
        $js = (string)file_get_contents(self::moduloPath('admin-cron.js'));

        // Todo texto visível chega por data-*; o único literal aceitável é técnico.
        foreach (['Tarefa', 'Nenhuma', 'Erro ', 'Sucesso', 'Excluir', 'Salvar', 'Cancelar'] as $literal) {
            self::assertStringNotContainsString(
                "'" . $literal,
                $js,
                "rótulo literal '{$literal}' deve vir do sistema de variáveis"
            );
        }
    }

    public function testControladorUsaAsVariaveisDoSistemaParaTodaMensagem(): void
    {
        $php = (string)file_get_contents(self::moduloPath('admin-cron.php'));
        $manifesto = self::manifesto();

        $declaradas = array_map(
            static fn(array $v): string => $v['id'],
            $manifesto['resources']['pt-br']['variables']
        );

        preg_match_all("/admin_cron_var\('([^']+)'\)/", $php, $usadas);
        self::assertNotEmpty($usadas[1]);

        foreach (array_unique($usadas[1]) as $id) {
            self::assertContains(
                $id,
                $declaradas,
                "o controlador usa a variável '{$id}', que o manifesto não declara"
            );
        }
    }

    public function testMensagensDeErroDoAjaxNaoSaoLiterais(): void
    {
        $php = (string)file_get_contents(self::moduloPath('admin-cron.php'));

        // Toda resposta de erro precisa carregar uma variável, nunca uma string escrita ali.
        preg_match_all("/'message' => '([^']+)'/", $php, $literais);
        self::assertSame([], $literais[1], 'mensagem literal no envelope AJAX');
    }

    // ===================== 8. Autoria x estado operacional =====================

    public function testSincronizacaoNaoSobrescreveOAgendamentoAjustadoNoPainel(): void
    {
        $php = (string)file_get_contents(self::moduloPath('admin-cron.php'));

        // O bloco de estado operacional precisa estar sob a guarda de user_modified.
        self::assertMatchesRegularExpression(
            '/if\(empty\(\$existentes\[\$tid\]\[\'user_modified\'\]\)\)\{\s*'
            . 'banco_update_campo\(\'frequencia\'/s',
            $php,
            'frequencia/expressao/parametros/ativo só podem voltar ao arquivo se user_modified=0'
        );
    }

    public function testPausarUmaTarefaMarcaUserModified(): void
    {
        $php = (string)file_get_contents(self::moduloPath('admin-cron.php'));

        self::assertMatchesRegularExpression(
            '/function admin_cron_ajax_alternar.*?banco_update_campo\(\'user_modified\', 1/s',
            $php,
            'sem marcar user_modified, a pausa seria desfeita na sincronização seguinte'
        );
    }

    public function testTarefaDeModuloNaoPodeSerExcluidaPeloPainel(): void
    {
        $php = (string)file_get_contents(self::moduloPath('admin-cron.php'));

        self::assertMatchesRegularExpression(
            '/function admin_cron_ajax_excluir.*?origem.*?===\s*\'modulo\'.*?api-error-module-task-readonly/s',
            $php
        );
    }

    public function testCamposDeAutoriaDeTarefaDeModuloNaoSaoGravadosPeloPainel(): void
    {
        $php = (string)file_get_contents(self::moduloPath('admin-cron.php'));

        // Gravar nome/callback aqui criaria divergência que a próxima sincronização desfaria.
        self::assertStringContainsString('if(!$origemModulo){', $php);
    }

    public function testEndpointDeListagemNaoColideComOInterceptadorDoNucleo(): void
    {
        $php = (string)file_get_contents(self::moduloPath('admin-cron.php'));
        $js = (string)file_get_contents(self::moduloPath('admin-cron.js'));

        // interface_ajax_finalizar() intercepta ajax-opcao === 'listar' e devolve a listagem
        // padrão do núcleo por cima da resposta do módulo.
        self::assertStringNotContainsString("case 'listar':", $php);
        self::assertStringNotContainsString("chamar('listar'", $js);
        self::assertStringContainsString("case 'tarefas':", $php);
    }

    public function testChamadasAjaxDeclaramOModoAjaxDoNucleo(): void
    {
        $js = (string)file_get_contents(self::moduloPath('admin-cron.js'));

        // Sem `ajax: 'sim'` o núcleo trata a chamada como formulário e responde 403 por CSRF.
        self::assertStringContainsString("ajax: 'sim'", $js);
        self::assertStringContainsString('ajaxOpcao: ajaxOpcao', $js);
        self::assertStringContainsString('resposta.status === 401', $js);
    }

    public function testVisibilidadeEControladaPelaClasseTailwindENaoPeloAtributoHidden(): void
    {
        $js = (string)file_get_contents(self::moduloPath('admin-cron.js'));

        // `.hidden = false` não remove a classe `hidden` do Tailwind: o elemento continuaria
        // invisível, e a falha seria silenciosa.
        self::assertDoesNotMatchRegularExpression('/\.hidden\s*=\s*(true|false)/', $js);
        self::assertStringContainsString("classList.toggle('hidden'", $js);
    }
}
