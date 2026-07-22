<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'modulo-distribuido.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'modelo.php';

/**
 * Testes sintéticos da Arquitetura de Módulos Distribuídos (req-005).
 *
 * Cobrem as peças puras e o ciclo central→distribuído→central sem rede real:
 * - escopo do manifesto (scope);
 * - assinatura/verificação HMAC do canal;
 * - detecção de operação, empacotamento JSON e guard anti-empilhamento;
 * - executor local sobre PDO SQLite (INSERT/SELECT/UPDATE/DELETE + rejeições);
 * - BancoResultadoRemoto integrado às funções banco_* de leitura;
 * - parser de rota da API distribuída;
 * - ponta a ponta: banco_query()/banco_select() em modo distribuído via transporte mock.
 */
final class ModuloDistribuidoTest extends TestCase
{
    protected function tearDown(): void
    {
        // Garante que nenhum teste vaze o modo distribuído para o próximo.
        banco_distribuido_finalizar();
        global $_BANCO;
        unset($_BANCO['distribuido-insert-id'], $_BANCO['distribuido-affected-rows']);
    }

    // ============================ Escopo do manifesto

    public function testScopeLeManifestoArray(): void
    {
        self::assertSame('central-module', modulo_distribuido_scope(['scope' => 'central-module']));
        self::assertSame('distributed-module', modulo_distribuido_scope(['scope' => 'distributed-module']));
        self::assertNull(modulo_distribuido_scope(['versao' => '1.0.0']));
    }

    public function testScopeHelpersCentralEDistribuido(): void
    {
        self::assertTrue(modulo_distribuido_scope_central('central-module'));
        self::assertFalse(modulo_distribuido_scope_central('distributed-module'));
        self::assertTrue(modulo_distribuido_scope_distribuido('distributed-module'));
        self::assertFalse(modulo_distribuido_scope_distribuido('central-module'));
    }

    public function testScopeLeManifestoDeArquivoEDiretorio(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f_mod_dist_' . uniqid();
        $dir = $base . DIRECTORY_SEPARATOR . 'meu-modulo';
        mkdir($dir, 0777, true);
        $json = $dir . DIRECTORY_SEPARATOR . 'meu-modulo.json';
        file_put_contents($json, json_encode(['scope' => 'central-module', 'versao' => '1.0.0']));

        // Por caminho de arquivo .json
        self::assertSame('central-module', modulo_distribuido_scope($json));
        // Por diretório (infere <slug>.json)
        self::assertSame('central-module', modulo_distribuido_scope($dir));
        // Por diretório com slug explícito
        self::assertSame('central-module', modulo_distribuido_scope($dir, 'meu-modulo'));

        unlink($json);
        rmdir($dir);
        rmdir($base);
    }

    // ============================ HMAC

    public function testHmacAssinaEVerifica(): void
    {
        $corpo = '{"sql":"SELECT 1"}';
        $secret = 'segredo-super';

        $assinatura = modulo_distribuido_assinar($corpo, $secret);
        self::assertNotSame('', $assinatura);
        self::assertTrue(modulo_distribuido_verificar_assinatura($corpo, $assinatura, $secret));
    }

    public function testHmacRejeitaAssinaturaOuSegredoInvalido(): void
    {
        $corpo = '{"sql":"SELECT 1"}';
        $secret = 'segredo-super';
        $assinatura = modulo_distribuido_assinar($corpo, $secret);

        self::assertFalse(modulo_distribuido_verificar_assinatura($corpo, $assinatura, 'outro-segredo'));
        self::assertFalse(modulo_distribuido_verificar_assinatura($corpo . 'x', $assinatura, $secret));
        self::assertFalse(modulo_distribuido_verificar_assinatura($corpo, '', $secret));
        self::assertFalse(modulo_distribuido_verificar_assinatura($corpo, $assinatura, ''));
    }

    // ============================ Detecção de operação e payload

    public function testDetectaOperacao(): void
    {
        self::assertSame('select', modulo_distribuido_detectar_operacao("SELECT * FROM grupos"));
        self::assertSame('insert', modulo_distribuido_detectar_operacao("  insert INTO grupos VALUES(1)"));
        self::assertSame('update', modulo_distribuido_detectar_operacao("UPDATE grupos SET nome='a'"));
        self::assertSame('delete', modulo_distribuido_detectar_operacao("DELETE FROM grupos WHERE id=1"));
        self::assertSame('outro', modulo_distribuido_detectar_operacao("DROP TABLE grupos"));
    }

    public function testMontaPayloadComCamposEsperados(): void
    {
        $payload = modulo_distribuido_montar_payload("SELECT id FROM grupos", ['modulo' => 'grupos', 'linguagem' => 'pt-br']);

        self::assertSame(1, $payload['versao']);
        self::assertSame('select', $payload['operacao']);
        self::assertSame('SELECT id FROM grupos', $payload['sql']);
        self::assertSame('grupos', $payload['modulo']);
        self::assertSame('pt-br', $payload['linguagem']);
        self::assertIsInt($payload['timestamp']);
        self::assertNotEmpty($payload['nonce']);

        // Dois payloads consecutivos devem gerar nonces distintos.
        $payload2 = modulo_distribuido_montar_payload("SELECT id FROM grupos", ['modulo' => 'grupos']);
        self::assertNotSame($payload['nonce'], $payload2['nonce']);
    }

    public function testGuardAntiEmpilhamentoDeInstrucoes(): void
    {
        self::assertTrue(modulo_distribuido_sql_segura("SELECT * FROM grupos WHERE id=1"));
        self::assertTrue(modulo_distribuido_sql_segura("SELECT * FROM grupos;"));            // ';' final tolerado
        self::assertTrue(modulo_distribuido_sql_segura("INSERT INTO t (a) VALUES ('x;y')")); // ';' dentro de literal
        self::assertFalse(modulo_distribuido_sql_segura("SELECT 1; DROP TABLE grupos"));     // empilhamento
    }

    // ============================ Executor local (PDO SQLite)

    private function pdoSqliteComTabela(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE grupos (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, host INTEGER)");
        return $pdo;
    }

    public function testExecutorLocalInsertSelectUpdateDelete(): void
    {
        $pdo = $this->pdoSqliteComTabela();

        // INSERT
        $r = modulo_distribuido_executar_local(['sql' => "INSERT INTO grupos (nome, host) VALUES ('Alpha', 1)"], $pdo);
        self::assertSame('ok', $r['status']);
        self::assertSame('write', $r['tipo']);
        self::assertSame(1, $r['affected_rows']);
        self::assertSame(1, $r['insert_id']);

        modulo_distribuido_executar_local(['sql' => "INSERT INTO grupos (nome, host) VALUES ('Beta', 0)"], $pdo);

        // SELECT
        $sel = modulo_distribuido_executar_local(['sql' => "SELECT id, nome FROM grupos ORDER BY id"], $pdo);
        self::assertSame('ok', $sel['status']);
        self::assertSame('select', $sel['tipo']);
        self::assertSame(['id', 'nome'], $sel['fields']);
        self::assertCount(2, $sel['rows']);
        self::assertEquals('Alpha', $sel['rows'][0][1]);
        self::assertEquals('Beta', $sel['rows'][1][1]);

        // UPDATE
        $upd = modulo_distribuido_executar_local(['sql' => "UPDATE grupos SET nome='Alpha2' WHERE nome='Alpha'"], $pdo);
        self::assertSame('write', $upd['tipo']);
        self::assertSame(1, $upd['affected_rows']);

        // DELETE
        $del = modulo_distribuido_executar_local(['sql' => "DELETE FROM grupos WHERE nome='Beta'"], $pdo);
        self::assertSame('write', $del['tipo']);
        self::assertSame(1, $del['affected_rows']);
    }

    public function testExecutorLocalRejeitaOperacaoNaoSuportadaEEmpilhamento(): void
    {
        $pdo = $this->pdoSqliteComTabela();

        $drop = modulo_distribuido_executar_local(['sql' => "DROP TABLE grupos"], $pdo);
        self::assertSame('error', $drop['status']);

        $stack = modulo_distribuido_executar_local(['sql' => "SELECT 1; DROP TABLE grupos"], $pdo);
        self::assertSame('error', $stack['status']);

        $vazio = modulo_distribuido_executar_local(['sql' => "   "], $pdo);
        self::assertSame('error', $vazio['status']);
    }

    public function testExecutorLocalCapturaErroDeSintaxe(): void
    {
        $pdo = $this->pdoSqliteComTabela();
        $r = modulo_distribuido_executar_local(['sql' => "SELECT coluna_inexistente FROM grupos"], $pdo);
        self::assertSame('error', $r['status']);
        self::assertArrayHasKey('message', $r);
    }

    // ============================ BancoResultadoRemoto integrado a banco_*

    public function testBancoResultadoRemotoIntegraFuncoesDeLeitura(): void
    {
        $res = new BancoResultadoRemoto(['id', 'nome'], [[1, 'Alpha'], [2, 'Beta']]);

        self::assertSame(2, banco_num_rows($res));
        self::assertSame(2, banco_num_fields($res));
        self::assertSame('id', banco_field_name($res, 0));
        self::assertSame('nome', banco_field_name($res, 1));

        // fetch por índice numérico + associativo (equivalente a MYSQLI_BOTH)
        $linha = banco_row_array($res);
        self::assertSame(1, $linha[0]);
        self::assertSame('Alpha', $linha[1]);
        self::assertSame(1, $linha['id']);
        self::assertSame('Alpha', $linha['nome']);

        // avanço de cursor
        $linha2 = banco_fetch_assoc($res);
        self::assertSame('Beta', $linha2['nome']);
    }

    public function testRespostaParaResultadoSelectWriteErro(): void
    {
        global $_BANCO;

        // SELECT
        $res = modulo_distribuido_resposta_para_resultado([
            'status' => 'ok', 'tipo' => 'select',
            'fields' => ['id'], 'rows' => [[10]],
        ]);
        self::assertInstanceOf(BancoResultadoRemoto::class, $res);
        self::assertSame(1, banco_num_rows($res));

        // WRITE
        $ok = modulo_distribuido_resposta_para_resultado([
            'status' => 'ok', 'tipo' => 'write', 'insert_id' => 42, 'affected_rows' => 1,
        ]);
        self::assertTrue($ok);
        self::assertSame(42, $_BANCO['distribuido-insert-id']);

        // ERRO
        $err = modulo_distribuido_resposta_para_resultado(['status' => 'error', 'message' => 'x']);
        self::assertFalse($err);
    }

    // ============================ Parser de rota

    public function testParseRota(): void
    {
        $r = modulo_distribuido_parse_rota(['_api', 'modulo-distribuido', 'grupos', 'db']);
        self::assertSame('grupos', $r['slug']);
        self::assertSame('db', $r['acao']);

        // Com v1 intermediário
        $r2 = modulo_distribuido_parse_rota(['_api', 'v1', 'modulo-distribuido', 'grupos', 'signin']);
        self::assertSame('grupos', $r2['slug']);
        self::assertSame('signin', $r2['acao']);

        // Sem ação => default 'db'
        $r3 = modulo_distribuido_parse_rota(['_api', 'modulo-distribuido', 'grupos']);
        self::assertSame('db', $r3['acao']);

        // Sem slug => null
        self::assertNull(modulo_distribuido_parse_rota(['_api', 'modulo-distribuido']));
        // Sem marcador => null
        self::assertNull(modulo_distribuido_parse_rota(['_api', 'status']));
    }

    // ============================ Ponta a ponta (central -> distribuído -> central)

    /**
     * Transporte mock: valida a assinatura, decodifica o payload e executa no PDO local,
     * devolvendo o JSON que o cliente central espera. Simula toda a viagem sem rede.
     */
    private function transporteParaPdo(PDO $pdo, string $secret): callable
    {
        return function ($url, $corpo, array $headers) use ($pdo, $secret) {
            $assinatura = '';
            foreach ($headers as $h) {
                if (stripos($h, 'X-C2F-Signature:') === 0) {
                    $assinatura = trim(substr($h, strlen('X-C2F-Signature:')));
                }
            }
            if (!modulo_distribuido_verificar_assinatura($corpo, $assinatura, $secret)) {
                return json_encode(['status' => 'error', 'message' => 'Assinatura inválida']);
            }
            $payload = json_decode($corpo, true);
            return json_encode(modulo_distribuido_executar_local($payload, $pdo));
        };
    }

    public function testCicloCompletoBancoDistribuidoViaBancoQuery(): void
    {
        global $_BANCO;

        $pdo = $this->pdoSqliteComTabela();
        $secret = 'segredo-canal';
        $config = [
            'endpoint'   => 'https://site.test/_api',
            'slug'       => 'grupos',
            'secret'     => $secret,
            'transporte' => $this->transporteParaPdo($pdo, $secret),
        ];

        banco_distribuido_iniciar($config);
        self::assertTrue(banco_distribuido_ativo());

        // INSERT delegado ao distribuído
        $ins = banco_query("INSERT INTO grupos (nome, host) VALUES ('Alpha', 1)");
        self::assertTrue($ins);
        self::assertSame(1, banco_last_id());

        banco_query("INSERT INTO grupos (nome, host) VALUES ('Beta', 0)");

        // SELECT via API distribuída, lido pela função nativa banco_select()
        $linhas = banco_select(['campos' => ['id', 'nome'], 'tabela' => 'grupos', 'extra' => 'ORDER BY id']);
        self::assertIsArray($linhas);
        self::assertCount(2, $linhas);
        self::assertEquals('Alpha', $linhas[0]['nome']);
        self::assertEquals('Beta', $linhas[1]['nome']);

        banco_distribuido_finalizar();
        self::assertFalse(banco_distribuido_ativo());
    }

    public function testCicloCompletoRejeitaAssinaturaComSegredoDivergente(): void
    {
        $pdo = $this->pdoSqliteComTabela();

        // O distribuído espera 'segredo-correto', mas o central assina com 'segredo-errado'.
        $config = [
            'endpoint'   => 'https://site.test/_api',
            'slug'       => 'grupos',
            'secret'     => 'segredo-errado',
            'transporte' => $this->transporteParaPdo($pdo, 'segredo-correto'),
        ];

        banco_distribuido_iniciar($config);
        $r = banco_query("INSERT INTO grupos (nome) VALUES ('X')");
        self::assertFalse($r); // resposta de erro do distribuído vira false no central
        banco_distribuido_finalizar();
    }

    // ============================ Controle de permissão por perfil (via API)

    /**
     * Monta um SQLite com as tabelas do controle de permissão e popula cenários.
     * A verificação de permissão é exercitada através do canal distribuído (banco_*
     * delegado ao SQLite), provando a árvore de decisão sem depender de MySQL.
     */
    private function pdoPermissoes(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE usuarios (id_usuarios INTEGER, id_hosts TEXT, id_usuarios_perfis TEXT, gestor_perfil TEXT)");
        $pdo->exec("CREATE TABLE modulos (id_modulos INTEGER, id TEXT, status TEXT, language TEXT)");
        $pdo->exec("CREATE TABLE usuarios_perfis (id_usuarios_perfis TEXT, id TEXT)");
        $pdo->exec("CREATE TABLE usuarios_perfis_modulos (id_usuarios_perfis_modulos INTEGER, perfil TEXT, modulo TEXT)");
        $pdo->exec("CREATE TABLE hosts (id_hosts TEXT, id_usuarios INTEGER)");
        $pdo->exec("CREATE TABLE usuarios_gestores_perfis_modulos (id_usuarios_gestores_perfis_modulos INTEGER, perfil TEXT, modulo TEXT, id_hosts TEXT)");

        // Módulo alvo ativo em pt-br.
        $pdo->exec("INSERT INTO modulos VALUES (1, 'modulos-grupos-distribuido', 'A', 'pt-br')");
        // Módulo inativo (para cenário negativo).
        $pdo->exec("INSERT INTO modulos VALUES (2, 'modulo-inativo', 'I', 'pt-br')");

        // Usuário direto (sem host) com perfil 'admin'.
        $pdo->exec("INSERT INTO usuarios VALUES (1, '', '10', '')");
        $pdo->exec("INSERT INTO usuarios_perfis VALUES ('10', 'admin')");
        $pdo->exec("INSERT INTO usuarios_perfis_modulos VALUES (1, 'admin', 'modulos-grupos-distribuido')");

        // Usuário direto (sem host) com perfil 'basico' (SEM o módulo).
        $pdo->exec("INSERT INTO usuarios VALUES (2, '', '20', '')");
        $pdo->exec("INSERT INTO usuarios_perfis VALUES ('20', 'basico')");

        // Usuário filho de host com perfil de gestor 'gp1'.
        $pdo->exec("INSERT INTO usuarios VALUES (3, '5', '30', 'gp1')");
        $pdo->exec("INSERT INTO usuarios_gestores_perfis_modulos VALUES (1, 'gp1', 'modulos-grupos-distribuido', '5')");

        return $pdo;
    }

    private function comCanalPermissoes(PDO $pdo, callable $fn)
    {
        $secret = 'perm-secret';
        banco_distribuido_iniciar([
            'endpoint'   => 'https://site.test/_api',
            'slug'       => 'permissoes',
            'secret'     => $secret,
            'transporte' => $this->transporteParaPdo($pdo, $secret),
        ]);
        try {
            return $fn();
        } finally {
            banco_distribuido_finalizar();
        }
    }

    public function testPermissaoUsuarioDiretoComVinculoRetornaTrue(): void
    {
        $pdo = $this->pdoPermissoes();
        $ok = $this->comCanalPermissoes($pdo, function () {
            return autenticacao_distribuido_verificar_permissao_modulo(1, 'modulos-grupos-distribuido');
        });
        self::assertTrue($ok);
    }

    public function testPermissaoUsuarioDiretoSemVinculoRetornaFalse(): void
    {
        $pdo = $this->pdoPermissoes();
        $ok = $this->comCanalPermissoes($pdo, function () {
            return autenticacao_distribuido_verificar_permissao_modulo(2, 'modulos-grupos-distribuido');
        });
        self::assertFalse($ok);
    }

    public function testPermissaoModuloInativoRetornaFalse(): void
    {
        $pdo = $this->pdoPermissoes();
        $ok = $this->comCanalPermissoes($pdo, function () {
            return autenticacao_distribuido_verificar_permissao_modulo(1, 'modulo-inativo');
        });
        self::assertFalse($ok);
    }

    public function testPermissaoUsuarioGestorDeHostComVinculoRetornaTrue(): void
    {
        $pdo = $this->pdoPermissoes();
        $ok = $this->comCanalPermissoes($pdo, function () {
            return autenticacao_distribuido_verificar_permissao_modulo(3, 'modulos-grupos-distribuido');
        });
        self::assertTrue($ok);
    }

    public function testPermissaoEntradasInvalidasRetornamFalse(): void
    {
        self::assertFalse(autenticacao_distribuido_verificar_permissao_modulo(0, 'x'));
        self::assertFalse(autenticacao_distribuido_verificar_permissao_modulo(1, ''));
    }

    // ============================ Renderização: login / sem-permissão / iframe

    public function testEstadoPorTokenAtivo(): void
    {
        self::assertSame('iframe', modulo_distribuido_estado_por_token_ativo(true));
        self::assertSame('login', modulo_distribuido_estado_por_token_ativo(false));
    }

    public function testEstadoRenderizacaoTresEstados(): void
    {
        self::assertSame('login', modulo_distribuido_estado_renderizacao(false, false));
        self::assertSame('login', modulo_distribuido_estado_renderizacao(false, true));
        self::assertSame('sem-permissao', modulo_distribuido_estado_renderizacao(true, false));
        self::assertSame('iframe', modulo_distribuido_estado_renderizacao(true, true));
    }

    public function testEstadoPorPermissaoCentral(): void
    {
        self::assertSame('iframe', modulo_distribuido_estado_por_permissao_central('permitido'));
        self::assertSame('sem-permissao', modulo_distribuido_estado_por_permissao_central('sem-permissao'));
        self::assertSame('login', modulo_distribuido_estado_por_permissao_central('nao-autenticado'));
        self::assertSame('login', modulo_distribuido_estado_por_permissao_central('qualquer-outro'));
    }

    public function testMontaUrlIframe(): void
    {
        $url = modulo_distribuido_montar_url_iframe('https://conn2flow.com/', 'modulos-grupos-distribuido', [
            'opcao' => 'listar',
            'token' => 'abc123',
        ]);
        self::assertStringStartsWith('https://conn2flow.com/modulos-grupos-distribuido/?', $url);
        self::assertStringContainsString('embed=1', $url);
        self::assertStringContainsString('opcao=listar', $url);
        self::assertStringContainsString('token=abc123', $url);
    }

    // ============================ Middleware e Guardião (fachada obrigatória)

    /**
     * Transporte mock do endpoint central 'permissao': valida a assinatura e responde
     * no formato de api_response_success ({status:success, data:{estado:...}}).
     */
    private function transportePermissao(string $secret, string $estadoCentral): callable
    {
        return function ($url, $corpo, array $headers) use ($secret, $estadoCentral) {
            $assinatura = '';
            foreach ($headers as $h) {
                if (stripos($h, 'X-C2F-Signature:') === 0) {
                    $assinatura = trim(substr($h, strlen('X-C2F-Signature:')));
                }
            }
            if (!modulo_distribuido_verificar_assinatura($corpo, $assinatura, $secret)) {
                return json_encode(['status' => 'error', 'message' => 'Assinatura inválida']);
            }
            return json_encode(['status' => 'success', 'data' => ['estado' => $estadoCentral]]);
        };
    }

    public function testMiddlewarePermissaoMapeiaEstadosDoCentral(): void
    {
        $secret = 'mw-secret';
        $base = ['endpoint' => 'https://central.test/_api', 'slug' => 'grupos', 'secret' => $secret];

        $permitido = modulo_distribuido_middleware_permissao(
            array_merge($base, ['transporte' => $this->transportePermissao($secret, 'permitido')]), 'tok'
        );
        self::assertSame('iframe', $permitido['estado']);

        $sem = modulo_distribuido_middleware_permissao(
            array_merge($base, ['transporte' => $this->transportePermissao($secret, 'sem-permissao')]), 'tok'
        );
        self::assertSame('sem-permissao', $sem['estado']);

        $nao = modulo_distribuido_middleware_permissao(
            array_merge($base, ['transporte' => $this->transportePermissao($secret, 'nao-autenticado')]), ''
        );
        self::assertSame('login', $nao['estado']);
    }

    public function testGuardiaoAutorizadoRetornaIframeUrl(): void
    {
        $secret = 'guard-secret';
        $config = [
            'endpoint'    => 'https://central.test/_api',
            'central-url' => 'https://central.test/',
            'slug'        => 'modulos-grupos-distribuido',
            'secret'      => $secret,
            'transporte'  => $this->transportePermissao($secret, 'permitido'),
        ];

        $g = modulo_distribuido_guardiao($config, ['token' => 'tok-123', 'opcao' => 'listar']);

        self::assertSame('iframe', $g['estado']);
        self::assertNotNull($g['iframe_url']);
        self::assertStringContainsString('https://central.test/modulos-grupos-distribuido/', $g['iframe_url']);
        self::assertStringContainsString('token=tok-123', $g['iframe_url']);
    }

    public function testGuardiaoSemPermissaoNaoRetornaIframeUrl(): void
    {
        $secret = 'guard-secret';
        $config = [
            'endpoint'    => 'https://central.test/_api',
            'central-url' => 'https://central.test/',
            'slug'        => 'modulos-grupos-distribuido',
            'secret'      => $secret,
            'transporte'  => $this->transportePermissao($secret, 'sem-permissao'),
        ];

        $g = modulo_distribuido_guardiao($config, ['token' => 'tok-123']);

        self::assertSame('sem-permissao', $g['estado']);
        self::assertNull($g['iframe_url']);
    }

    public function testGuardiaoNaoAutenticadoVaiParaLogin(): void
    {
        $secret = 'guard-secret';
        $config = [
            'endpoint'    => 'https://central.test/_api',
            'central-url' => 'https://central.test/',
            'slug'        => 'modulos-grupos-distribuido',
            'secret'      => $secret,
            'transporte'  => $this->transportePermissao($secret, 'nao-autenticado'),
        ];

        $g = modulo_distribuido_guardiao($config, ['token' => '']);

        self::assertSame('login', $g['estado']);
        self::assertNull($g['iframe_url']);
    }

    /**
     * Fail-closed: se o central não confirmar (erro de rede/resposta inválida/assinatura
     * divergente), o distribuído NUNCA libera o iframe — resolve para 'login'. Isso garante
     * que adulterar a lib distribuída ou derrubar o canal não conceda acesso.
     */
    public function testGuardiaoFailClosedQuandoCentralNaoConfirma(): void
    {
        // (a) Sem transporte válido / resposta vazia.
        $g1 = modulo_distribuido_guardiao([
            'endpoint'    => 'https://central.test/_api',
            'central-url' => 'https://central.test/',
            'slug'        => 'modulos-grupos-distribuido',
            'secret'      => 's',
            'transporte'  => function ($url, $corpo, $headers) { return ''; },
        ], ['token' => 'tok']);
        self::assertSame('login', $g1['estado']);
        self::assertNull($g1['iframe_url']);

        // (b) Assinatura HMAC divergente => central rejeita => login.
        $g2 = modulo_distribuido_guardiao([
            'endpoint'    => 'https://central.test/_api',
            'central-url' => 'https://central.test/',
            'slug'        => 'modulos-grupos-distribuido',
            'secret'      => 'segredo-errado',
            'transporte'  => $this->transportePermissao('segredo-correto', 'permitido'),
        ], ['token' => 'tok']);
        self::assertSame('login', $g2['estado']);
        self::assertNull($g2['iframe_url']);
    }

    // ============================ Middleware central (autoridade, lógica pura)

    public function testMiddlewareCentralResolveEstados(): void
    {
        // Token inválido => nao-autenticado (resolver devolve null).
        $r1 = modulo_distribuido_middleware_central('', 'grupos', [
            'resolver_usuario' => function ($tk) { return null; },
        ]);
        self::assertSame('nao-autenticado', $r1['estado']);

        // Autenticado sem permissão.
        $r2 = modulo_distribuido_middleware_central('tok', 'grupos', [
            'resolver_usuario'    => function ($tk) { return 7; },
            'verificar_permissao' => function ($id, $mod) { return false; },
        ]);
        self::assertSame('sem-permissao', $r2['estado']);
        self::assertSame(7, $r2['id_usuarios']);

        // Autenticado e autorizado.
        $r3 = modulo_distribuido_middleware_central('tok', 'grupos', [
            'resolver_usuario'    => function ($tk) { return 7; },
            'verificar_permissao' => function ($id, $mod) { return $id === 7 && $mod === 'grupos'; },
        ]);
        self::assertSame('permitido', $r3['estado']);
    }

    /**
     * Integração central↔distribuído da decisão: o middleware central alimenta a resposta
     * que o guardião distribuído consome, provando que a decisão nasce no central e é acatada.
     */
    public function testMiddlewareCentralAlimentaGuardiaoDistribuido(): void
    {
        $secret = 'e2e-secret';

        // Transporte que roda o PRÓPRIO middleware central (com verificadores injetados)
        // e responde no formato de api_response_success — como o handler HTTP faria.
        $transporte = function ($url, $corpo, array $headers) use ($secret) {
            $assinatura = '';
            foreach ($headers as $h) {
                if (stripos($h, 'X-C2F-Signature:') === 0) {
                    $assinatura = trim(substr($h, strlen('X-C2F-Signature:')));
                }
            }
            if (!modulo_distribuido_verificar_assinatura($corpo, $assinatura, $secret)) {
                return json_encode(['status' => 'error', 'message' => 'Assinatura inválida']);
            }
            $payload = json_decode($corpo, true);
            $resultado = modulo_distribuido_middleware_central($payload['token'] ?? '', $payload['modulo'] ?? '', [
                'resolver_usuario'    => function ($tk) { return $tk === 'valido' ? 42 : null; },
                'verificar_permissao' => function ($id, $mod) { return true; },
            ]);
            return json_encode(['status' => 'success', 'data' => $resultado]);
        };

        $config = [
            'endpoint'    => 'https://central.test/_api',
            'central-url' => 'https://central.test/',
            'slug'        => 'modulos-grupos-distribuido',
            'secret'      => $secret,
            'transporte'  => $transporte,
        ];

        $g = modulo_distribuido_guardiao($config, ['token' => 'valido']);
        self::assertSame('iframe', $g['estado']);
        self::assertNotNull($g['iframe_url']);

        $g2 = modulo_distribuido_guardiao($config, ['token' => 'invalido']);
        self::assertSame('login', $g2['estado']);
    }

    // ============================ Resolução de config e orquestração (refatoração)

    public function testConfigGetDotNotation(): void
    {
        global $_CONFIG;
        $backup = $_CONFIG;
        $_CONFIG['modulo-distribuido'] = [
            'secret'   => 'top',
            'secrets'  => ['grupos' => 'grupo-secret'],
            'endpoint' => 'https://c.test/_api',
        ];

        self::assertSame('top', modulo_distribuido_config_get('modulo-distribuido.secret'));
        self::assertSame('grupo-secret', modulo_distribuido_config_get('modulo-distribuido.secrets.grupos'));
        self::assertNull(modulo_distribuido_config_get('modulo-distribuido.inexistente'));
        self::assertSame('def', modulo_distribuido_config_get('a.b.c', 'def'));

        $_CONFIG = $backup;
    }

    public function testCanalCentralResolveDoManifestoEConfig(): void
    {
        global $_CONFIG;
        $backup = $_CONFIG;
        $_CONFIG['modulo-distribuido'] = [
            'endpoints' => ['modulos-grupos-distribuido' => 'https://site.test/_api'],
            'secrets'   => ['modulos-grupos-distribuido' => 'seg-grupos'],
        ];

        $manifesto = [
            'distributed' => [
                'target-slug'    => 'modulos-grupos-distribuido',
                'endpoint-config' => 'modulo-distribuido.endpoints.modulos-grupos-distribuido',
                'secret-config'   => 'modulo-distribuido.secrets.modulos-grupos-distribuido',
            ],
        ];
        $config = modulo_distribuido_canal_central($manifesto);

        self::assertSame('modulos-grupos-distribuido', $config['slug']);
        self::assertSame('https://site.test/_api', $config['endpoint']);
        self::assertSame('seg-grupos', $config['secret']);
        self::assertSame('db', $config['acao']);

        $_CONFIG = $backup;
    }

    public function testCanalDistribuidoResolveCentralUrlEEndpoint(): void
    {
        global $_CONFIG;
        $backup = $_CONFIG;
        $_CONFIG['modulo-distribuido'] = [
            'central-url' => 'https://conn2flow.test/',
            'secret'      => 'seg',
        ];

        $manifesto = ['distributed' => ['central-slug' => 'modulos-grupos-distribuido']];
        $config = modulo_distribuido_canal_distribuido($manifesto);

        self::assertSame('modulos-grupos-distribuido', $config['slug']);
        self::assertSame('https://conn2flow.test/', $config['central-url']);
        self::assertSame('https://conn2flow.test/_api', $config['endpoint']);
        self::assertSame('seg', $config['secret']);

        $_CONFIG = $backup;
    }

    public function testComCanalAtivaEFinalizaContexto(): void
    {
        self::assertFalse(banco_distribuido_ativo());
        $ret = modulo_distribuido_com_canal(function () {
            return banco_distribuido_ativo() ? 'ativo' : 'inativo';
        }, ['slug' => 'x', 'endpoint' => 'https://x/_api', 'secret' => 's']);
        self::assertSame('ativo', $ret);
        // Finaliza mesmo após o retorno (garantido por finally).
        self::assertFalse(banco_distribuido_ativo());
    }

    public function testRenderEstadoMantemBlocoDoEstadoERemoveOsDemais(): void
    {
        global $_GESTOR;
        $backupPagina = $_GESTOR['pagina'] ?? null;

        $_GESTOR['pagina'] = ''
            . '<!-- login < -->TELA_LOGIN<!-- login > -->'
            . '<!-- sem-permissao < -->TELA_SEMPERM<!-- sem-permissao > -->'
            . '<!-- iframe < --><iframe src="#iframe-src#"></iframe><!-- iframe > -->';

        // Estado iframe: mantém o bloco iframe (com src trocado), remove os outros.
        modulo_distribuido_render_estado('iframe', 'https://c.test/mod/?embed=1');
        self::assertStringContainsString('<iframe src="https://c.test/mod/?embed=1">', $_GESTOR['pagina']);
        self::assertStringNotContainsString('TELA_LOGIN', $_GESTOR['pagina']);
        self::assertStringNotContainsString('TELA_SEMPERM', $_GESTOR['pagina']);
        self::assertStringNotContainsString('<!-- iframe', $_GESTOR['pagina']);
        self::assertSame('iframe', $_GESTOR['javascript-vars']['moduloDistribuidoEstado']);

        // Estado sem-permissao: mantém só o bloco de sem-permissão.
        $_GESTOR['pagina'] = ''
            . '<!-- login < -->TELA_LOGIN<!-- login > -->'
            . '<!-- sem-permissao < -->TELA_SEMPERM<!-- sem-permissao > -->'
            . '<!-- iframe < -->TELA_IFRAME<!-- iframe > -->';
        modulo_distribuido_render_estado('sem-permissao');
        self::assertStringContainsString('TELA_SEMPERM', $_GESTOR['pagina']);
        self::assertStringNotContainsString('TELA_LOGIN', $_GESTOR['pagina']);
        self::assertStringNotContainsString('TELA_IFRAME', $_GESTOR['pagina']);

        if ($backupPagina === null) { unset($_GESTOR['pagina']); } else { $_GESTOR['pagina'] = $backupPagina; }
    }

    public function testAppInjetaComponenteNaPaginaPorEstado(): void
    {
        global $_GESTOR;
        $backupPagina = $_GESTOR['pagina'] ?? null;
        $secret = 'app-secret';

        // A página do módulo tem apenas o placeholder; o componente é injetado no lugar.
        $_GESTOR['pagina'] = 'ANTES #modulo-distribuido-app# DEPOIS';
        $componente = ''
            . '<!-- login < -->LOGIN<!-- login > -->'
            . '<!-- sem-permissao < -->NOPERM<!-- sem-permissao > -->'
            . '<!-- iframe < --><iframe src="#iframe-src#"></iframe><!-- iframe > -->';

        $config = [
            'endpoint'    => 'https://central.test/_api',
            'central-url' => 'https://central.test/',
            'slug'        => 'modulos-grupos-distribuido',
            'secret'      => $secret,
            'transporte'  => $this->transportePermissao($secret, 'permitido'),
        ];

        $g = modulo_distribuido_app($config, ['token' => 'tok', 'opcao' => 'listar', 'html' => $componente]);

        self::assertSame('iframe', $g['estado']);
        self::assertStringContainsString('<iframe src="https://central.test/modulos-grupos-distribuido/', $_GESTOR['pagina']);
        self::assertStringContainsString('ANTES ', $_GESTOR['pagina']);
        self::assertStringContainsString(' DEPOIS', $_GESTOR['pagina']);
        self::assertStringNotContainsString('#modulo-distribuido-app#', $_GESTOR['pagina']);
        self::assertStringNotContainsString('LOGIN', $_GESTOR['pagina']);
        self::assertStringNotContainsString('NOPERM', $_GESTOR['pagina']);

        if ($backupPagina === null) { unset($_GESTOR['pagina']); } else { $_GESTOR['pagina'] = $backupPagina; }
    }

    /**
     * Transporte que atende tanto 'signin' (retorna tokens) quanto 'permissao' (retorna estado),
     * roteando pela ação presente na URL. Valida a assinatura HMAC do canal.
     */
    private function transporteSigninEPermissao(string $secret, string $estadoCentral, string $accessToken): callable
    {
        return function ($url, $corpo, array $headers) use ($secret, $estadoCentral, $accessToken) {
            $assinatura = '';
            foreach ($headers as $h) {
                if (stripos($h, 'X-C2F-Signature:') === 0) {
                    $assinatura = trim(substr($h, strlen('X-C2F-Signature:')));
                }
            }
            if (!modulo_distribuido_verificar_assinatura($corpo, $assinatura, $secret)) {
                return json_encode(['status' => 'error', 'message' => 'Assinatura inválida']);
            }
            if (strpos($url, '/signin') !== false) {
                if ($accessToken === '') {
                    return json_encode(['status' => 'error', 'message' => 'Credenciais inválidas']);
                }
                return json_encode(['status' => 'success', 'data' => ['access_token' => $accessToken, 'refresh_token' => 'r-tok']]);
            }
            if (strpos($url, '/permissao') !== false) {
                // Fiel ao central: token ausente/vazio => nao-autenticado.
                $p = json_decode($corpo, true);
                $estado = empty($p['token']) ? 'nao-autenticado' : $estadoCentral;
                return json_encode(['status' => 'success', 'data' => ['estado' => $estado]]);
            }
            return json_encode(['status' => 'error', 'message' => 'acao desconhecida']);
        };
    }

    private function componenteComBlocos(): string
    {
        return ''
            . '<div data-c2f-md-state="#c2f-md-state#">'
            . '<!-- login < --><div class="err">#c2f-md-login-error#</div>LOGIN<!-- login > -->'
            . '<!-- sem-permissao < -->NOPERM<!-- sem-permissao > -->'
            . '<!-- iframe < --><iframe src="#iframe-src#"></iframe><!-- iframe > -->'
            . '</div>';
    }

    public function testSigninRetornaTokensComCredenciaisValidas(): void
    {
        $secret = 'signin-secret';
        $config = ['endpoint' => 'https://c.test/_api', 'slug' => 'grupos', 'secret' => $secret,
            'transporte' => $this->transporteSigninEPermissao($secret, 'permitido', 'TOK-OK')];
        $tokens = modulo_distribuido_signin($config, 'admin', 'senha');
        self::assertIsArray($tokens);
        self::assertSame('TOK-OK', $tokens['access_token']);

        // Credenciais inválidas (transporte devolve erro) => false.
        $config2 = ['endpoint' => 'https://c.test/_api', 'slug' => 'grupos', 'secret' => $secret,
            'transporte' => $this->transporteSigninEPermissao($secret, 'permitido', '')];
        self::assertFalse(modulo_distribuido_signin($config2, 'admin', 'errada'));
    }

    public function testAppFluxoLoginAutenticaEUsaTokenNaMesmaRequisicao(): void
    {
        global $_GESTOR;
        $backupPagina = $_GESTOR['pagina'] ?? null;
        $secret = 'flow-secret';

        // Simula o POST do form de login.
        $_REQUEST['c2f-md-signin'] = '1';
        $_REQUEST['usuario'] = 'admin';
        $_REQUEST['senha'] = 'senha';

        $_GESTOR['pagina'] = '#modulo-distribuido-app#';
        $config = [
            'endpoint'    => 'https://c.test/_api',
            'central-url' => 'https://c.test/',
            'slug'        => 'modulos-grupos-distribuido',
            'secret'      => $secret,
            'transporte'  => $this->transporteSigninEPermissao($secret, 'permitido', 'TOK-123'),
        ];

        // persistir-token no-op evita a sessão/banco no ambiente de teste.
        $guardado = [];
        $g = modulo_distribuido_app($config, [
            'html' => $this->componenteComBlocos(),
            'persistir-token' => function ($chave, $tokens) use (&$guardado) { $guardado[$chave] = $tokens; },
        ]);

        // Login OK → token usado imediatamente → guardião permitido → iframe.
        self::assertSame('iframe', $g['estado']);
        self::assertSame('TOK-123', $g['token']);
        self::assertSame('TOK-123', $guardado['modulos-grupos-distribuido-token']['access_token']);
        self::assertStringContainsString('<iframe src="https://c.test/modulos-grupos-distribuido/', $_GESTOR['pagina']);

        unset($_REQUEST['c2f-md-signin'], $_REQUEST['usuario'], $_REQUEST['senha']);
        if ($backupPagina === null) { unset($_GESTOR['pagina']); } else { $_GESTOR['pagina'] = $backupPagina; }
    }

    public function testAppFluxoLoginInvalidoExibeMensagemDeErro(): void
    {
        global $_GESTOR;
        $backupPagina = $_GESTOR['pagina'] ?? null;
        $secret = 'flow-secret';

        $_REQUEST['c2f-md-signin'] = '1';
        $_REQUEST['usuario'] = 'admin';
        $_REQUEST['senha'] = 'errada';

        $_GESTOR['pagina'] = '#modulo-distribuido-app#';
        $config = [
            'endpoint'    => 'https://c.test/_api',
            'central-url' => 'https://c.test/',
            'slug'        => 'modulos-grupos-distribuido',
            'secret'      => $secret,
            // access_token vazio => signin falha.
            'transporte'  => $this->transporteSigninEPermissao($secret, 'permitido', ''),
        ];

        // token '' explícito simula "sem token na sessão" sem tocar sessão/banco.
        $g = modulo_distribuido_app($config, ['html' => $this->componenteComBlocos(), 'token' => '']);

        // Sem token → tela de login com a mensagem de erro preenchida.
        self::assertSame('login', $g['estado']);
        self::assertStringContainsString('Usuário ou senha inválidos.', $_GESTOR['pagina']);

        unset($_REQUEST['c2f-md-signin'], $_REQUEST['usuario'], $_REQUEST['senha']);
        if ($backupPagina === null) { unset($_GESTOR['pagina']); } else { $_GESTOR['pagina'] = $backupPagina; }
    }

    public function testTextosI18n(): void
    {
        $pt = modulo_distribuido_textos('pt-br');
        self::assertSame('Ativar acesso', $pt['c2f-md-login-title']);

        $en = modulo_distribuido_textos('en');
        self::assertSame('Activate access', $en['c2f-md-login-title']);

        // Idioma desconhecido cai para pt-br; override tem precedência.
        $ov = modulo_distribuido_textos('xx', ['c2f-md-login-title' => 'Custom']);
        self::assertSame('Custom', $ov['c2f-md-login-title']);
    }

    public function testRenderComponentePreencheTextosEstadoEBlocos(): void
    {
        $html = ''
            . '<div data-c2f-md-state="#c2f-md-state#">'
            . '<!-- login < --><h2>#c2f-md-login-title#</h2><!-- login > -->'
            . '<!-- sem-permissao < --><h2>#c2f-md-noperm-title#</h2><!-- sem-permissao > -->'
            . '<!-- iframe < --><iframe src="#iframe-src#"></iframe><!-- iframe > -->'
            . '</div>';
        $textos = modulo_distribuido_textos('pt-br');

        // Estado login: mantém só o bloco de login, com o texto preenchido.
        $out = modulo_distribuido_render_componente($html, 'login', null, $textos);
        self::assertStringContainsString('data-c2f-md-state="login"', $out);
        self::assertStringContainsString('<h2>Ativar acesso</h2>', $out);
        self::assertStringNotContainsString('Sem permissão', $out);
        self::assertStringNotContainsString('<iframe', $out);

        // Estado iframe: mantém só o iframe com src trocado.
        $out2 = modulo_distribuido_render_componente($html, 'iframe', 'https://x/mod/?embed=1', $textos);
        self::assertStringContainsString('<iframe src="https://x/mod/?embed=1">', $out2);
        self::assertStringNotContainsString('Ativar acesso', $out2);
        self::assertStringNotContainsString('#iframe-src#', $out2);
    }
}
