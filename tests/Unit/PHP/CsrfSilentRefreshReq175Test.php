<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'seguranca.php';

/**
 * req-175 (BATCH-180) — renovação silenciosa de CSRF.
 *
 * As decisões (corpo da recusa, isenção da rota, resposta do endpoint, caminho de retorno) vivem
 * em funções PURAS de `seguranca.php`. O roteador e a recusa de `gestor/gestor.php` são verificados
 * pelo contrato do código-fonte: executá-los exige sessão, JWT e banco, e o arquivo chama
 * `gestor_start()` ao ser incluído.
 */
final class CsrfSilentRefreshReq175Test extends TestCase
{
    private $servidorOriginal;
    private $gestorOriginal;
    private $configOriginal;
    private $cookieOriginal;

    protected function setUp(): void
    {
        global $_GESTOR, $_CONFIG;
        $this->servidorOriginal = $_SERVER;
        $this->gestorOriginal = $_GESTOR;
        $this->configOriginal = $_CONFIG;
        $this->cookieOriginal = $_COOKIE;
    }

    protected function tearDown(): void
    {
        global $_GESTOR, $_CONFIG;
        $_SERVER = $this->servidorOriginal;
        $_GESTOR = $this->gestorOriginal;
        $_CONFIG = $this->configOriginal;
        $_COOKIE = $this->cookieOriginal;
    }

    private static function fonte(string $relativo): string
    {
        return (string)file_get_contents(dirname(__DIR__, 3) . '/' . $relativo);
    }

    // ===== Código de máquina da recusa

    public function testCorpoDaRecusaTrazCodigoPadronizadoSemPerderOContratoAnterior(): void
    {
        $corpo = seguranca_csrf_resposta_invalida_corpo('Token CSRF inválido ou ausente.');

        self::assertSame('error', $corpo['status']);
        self::assertSame('CSRF_INVALID_OR_EXPIRED', $corpo['code']);
        self::assertSame('Token CSRF inválido ou ausente.', $corpo['message']);
        self::assertSame('CSRF_INVALID_OR_EXPIRED', SEGURANCA_CSRF_ERRO_CODIGO);
    }

    public function testRecusaEmiteCabecalhoECorpoPadronizados(): void
    {
        $fonte = self::fonte('gestor/gestor.php');
        $inicio = strpos($fonte, 'function gestor_csrf_resposta_invalida(){');
        $fim = strpos($fonte, 'function gestor_start(){', $inicio);
        $trecho = substr($fonte, $inicio, $fim - $inicio);

        // Cabeçalho ANTES da bifurcação JSON/HTML: vale para os dois ramos.
        $cabecalho = strpos($trecho, "header('X-Gestor-Csrf-Error: '.SEGURANCA_CSRF_ERRO_CODIGO);");
        $bifurcacao = strpos($trecho, 'if($requisicaoAjax){');
        self::assertNotFalse($cabecalho);
        self::assertNotFalse($bifurcacao);
        self::assertLessThan($bifurcacao, $cabecalho);

        self::assertStringContainsString('seguranca_csrf_resposta_invalida_corpo($mensagem)', $trecho);
        self::assertStringContainsString('http_response_code(403);', $trecho);
    }

    // ===== Isenção

    public function testRotaDeRenovacaoEhIsenta(): void
    {
        self::assertTrue(seguranca_csrf_rota_isenta(['_gestor-csrf-token']));
        self::assertTrue(seguranca_csrf_rota_isenta(['_api']));
        self::assertFalse(seguranca_csrf_rota_isenta(['admin-paginas', 'editar']));
        self::assertFalse(seguranca_csrf_rota_isenta(['_gestor-csrf-token-falso']));
        self::assertFalse(seguranca_csrf_rota_isenta([]));
    }

    public function testPostAutenticadoSemTokenNaRotaDeRenovacaoPassaNaValidacao(): void
    {
        global $_GESTOR, $_CONFIG;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GESTOR['caminho'] = ['_gestor-csrf-token'];
        $_CONFIG['cookie-authname'] = 'c2f_auth_teste';
        $_COOKIE['c2f_auth_teste'] = 'jwt-qualquer';
        unset($_SERVER['HTTP_X_CSRF_TOKEN'], $_POST['_csrf_token'], $_REQUEST['_csrf_token']);

        self::assertTrue(seguranca_csrf_requisicao_validar());
    }

    // ===== Resposta do endpoint

    public function testVisitanteRecebeTokenSemAutenticacao(): void
    {
        $r = seguranca_csrf_token_resposta([
            'token' => 'tok-visitante',
            'tem_cookie_auth' => false,
            'autenticado' => false,
            'url_raiz' => '/site/',
        ]);

        self::assertSame(200, $r['http']);
        self::assertSame(['status' => 'success', 'token' => 'tok-visitante', 'authenticated' => false], $r['corpo']);
        self::assertArrayNotHasKey('X-Gestor-Auth-Redirect', $r['headers']);
    }

    public function testUsuarioLogadoRecebeTokenAutenticado(): void
    {
        $r = seguranca_csrf_token_resposta([
            'token' => 'tok-logado',
            'tem_cookie_auth' => true,
            'autenticado' => true,
            'url_raiz' => '/site/',
        ]);

        self::assertSame(200, $r['http']);
        self::assertSame('success', $r['corpo']['status']);
        self::assertSame('tok-logado', $r['corpo']['token']);
        self::assertTrue($r['corpo']['authenticated']);
    }

    public function testAutenticadoSemCookieNuncaEhDeclarado(): void
    {
        $r = seguranca_csrf_token_resposta(['token' => 't', 'tem_cookie_auth' => false, 'autenticado' => true]);

        self::assertFalse($r['corpo']['authenticated']);
    }

    public function testLoginExpiradoResponde401ComRedirecionamentoESemToken(): void
    {
        $r = seguranca_csrf_token_resposta([
            'token' => 'nao-deve-sair',
            'tem_cookie_auth' => true,
            'autenticado' => false,
            'url_raiz' => '/site/',
        ]);

        self::assertSame(401, $r['http']);
        self::assertSame('AUTH_EXPIRED', $r['corpo']['code']);
        self::assertSame('error', $r['corpo']['status']);
        self::assertFalse($r['corpo']['authenticated']);
        self::assertArrayNotHasKey('token', $r['corpo']);
        self::assertSame('/site/signin/', $r['headers']['X-Gestor-Auth-Redirect']);
    }

    public function testCabecalhoDeRedirecionamentoNaoAceitaQuebraDeLinha(): void
    {
        $r = seguranca_csrf_token_resposta([
            'tem_cookie_auth' => true,
            'autenticado' => false,
            'url_raiz' => "/site/\r\nSet-Cookie: x=1",
        ]);

        self::assertStringNotContainsString("\n", $r['headers']['X-Gestor-Auth-Redirect']);
        self::assertStringNotContainsString("\r", $r['headers']['X-Gestor-Auth-Redirect']);
    }

    public function testRespostaNuncaEntraEmCache(): void
    {
        foreach ([true, false] as $autenticado) {
            $r = seguranca_csrf_token_resposta(['token' => 't', 'tem_cookie_auth' => true, 'autenticado' => $autenticado]);
            self::assertStringContainsString('no-store', $r['headers']['Cache-Control']);
            self::assertStringStartsWith('application/json', $r['headers']['Content-Type']);
        }
    }

    // ===== Caminho de retorno

    public function testRetornoAceitaCaminhoRelativo(): void
    {
        self::assertSame('admin-paginas/editar/', seguranca_csrf_retorno_normalizar('admin-paginas/editar/'));
        self::assertSame('dashboard/', seguranca_csrf_retorno_normalizar('/dashboard'));
        self::assertSame('busca/', seguranca_csrf_retorno_normalizar('busca/?q=teste'));
    }

    public function testRetornoRecusaDestinoExternoOuMalformado(): void
    {
        foreach ([
            'https://evil.test/',
            '//evil.test/x',
            'javascript:alert(1)',
            '../../etc/passwd',
            '%2e%2e/segredo',
            "admin/\r\nLocation: x",
            'a\\b',
            '',
            null,
            ['admin'],
            str_repeat('a', 600),
        ] as $valor) {
            self::assertSame('', seguranca_csrf_retorno_normalizar($valor), var_export($valor, true));
        }
    }

    // ===== Roteador

    public function testRotaEhAtendidaAntesDoPorteiroDoSiteRestrito(): void
    {
        $fonte = self::fonte('gestor/gestor.php');
        $inicio = strpos($fonte, 'function gestor_roteador(){');
        $caso = strpos($fonte, 'case SEGURANCA_CSRF_ROTA_TOKEN:', $inicio);
        $porteiro = strpos($fonte, 'gestor_roteador_acesso_restrito();', $inicio);

        self::assertNotFalse($caso);
        self::assertNotFalse($porteiro);
        self::assertLessThan($porteiro, $caso);
        self::assertSame('_gestor-csrf-token', SEGURANCA_CSRF_ROTA_TOKEN);
    }

    public function testHandlerDelegaAFuncaoPuraEGuardaORetorno(): void
    {
        $fonte = self::fonte('gestor/gestor.php');
        $inicio = strpos($fonte, 'function gestor_roteador_csrf_token(){');
        $fim = strpos($fonte, 'function gestor_roteador(){', $inicio);
        self::assertNotFalse($inicio);
        $trecho = substr($fonte, $inicio, $fim - $inicio);

        self::assertStringContainsString('seguranca_csrf_token_resposta(', $trecho);
        self::assertStringContainsString('gestor_permissao_token()', $trecho);
        self::assertStringContainsString("seguranca_csrf_retorno_normalizar(\$_REQUEST['retorno'] ?? '')", $trecho);
        self::assertStringContainsString("gestor_sessao_variavel('redirecionar-local', \$retorno)", $trecho);
        self::assertStringContainsString('exit;', $trecho);
    }

    public function testFrontendUsaOsMesmosIdentificadoresDoBackend(): void
    {
        $js = self::fonte('gestor/assets/global/global.js');

        self::assertStringContainsString("'" . SEGURANCA_CSRF_ERRO_CODIGO . "'", $js);
        self::assertStringContainsString("'" . SEGURANCA_CSRF_ROTA_TOKEN . "/'", $js);
        self::assertStringContainsString("'X-Gestor-Csrf-Error'", $js);
    }
}
