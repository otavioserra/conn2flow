<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-274 (BATCH-283) — passe de navegador confiável para acesso anônimo com o site restrito.
 *
 * Emissão e validação são funções PURAS da biblioteca `gestor` (assinatura, salt, validade). O
 * porteiro do roteador e a tela do `admin-environment` são verificados pelo contrato do código-fonte,
 * como na req-163: executá-los exige sessão, JWT e banco.
 */
final class SiteAcessoRestritoBypassReq274Test extends TestCase
{
    private const SEGREDO = 'segredo-de-teste';
    private const AGORA = 1_800_000_000;

    private $configOriginal;
    private $cookieOriginal;

    protected function setUp(): void
    {
        global $_CONFIG;
        $this->configOriginal = $_CONFIG ?? null;
        $this->cookieOriginal = $_COOKIE;
    }

    protected function tearDown(): void
    {
        global $_CONFIG;
        $_CONFIG = $this->configOriginal;
        $_COOKIE = $this->cookieOriginal;
        unset($_ENV['SITE_RESTRICTED_BYPASS_SALT'], $_ENV['USUARIO_HASH_PASSWORD']);
    }

    private static function fonte(string $relativo): string
    {
        return (string)file_get_contents(dirname(__DIR__, 3) . '/' . $relativo);
    }

    private static function emitir(string $salt = 'salt-a', int $dias = 30): array
    {
        $passe = gestor_site_acesso_restrito_bypass_gerar($dias, $salt, self::AGORA, self::SEGREDO);
        self::assertIsArray($passe);
        return $passe;
    }

    // ===== Emissão e validação

    public function testPasseEmitidoEhValidoComOMesmoSalt(): void
    {
        $passe = self::emitir();

        self::assertSame(self::AGORA + 30 * 86400, $passe['expira']);
        self::assertSame(
            ['criado' => self::AGORA, 'expira' => $passe['expira']],
            gestor_site_acesso_restrito_bypass_validar($passe['token'], 'salt-a', self::AGORA + 60, self::SEGREDO)
        );
    }

    public function testDoisPassesSaoDiferentes(): void
    {
        self::assertNotSame(self::emitir()['token'], self::emitir()['token']);
    }

    public function testAssinaturaAdulteradaEhRecusada(): void
    {
        $token = self::emitir()['token'];
        $ultimo = substr($token, -1) === 'a' ? 'b' : 'a';

        self::assertFalse(gestor_site_acesso_restrito_bypass_validar(substr($token, 0, -1) . $ultimo, 'salt-a', self::AGORA, self::SEGREDO));
    }

    public function testPayloadTrocadoEhRecusado(): void
    {
        [, $assinatura] = explode('.', self::emitir()['token']);
        $falso = rtrim(strtr(base64_encode('v1|' . self::AGORA . '|' . (self::AGORA + 999 * 86400) . '|0000000000000000'), '+/', '-_'), '=');

        self::assertFalse(gestor_site_acesso_restrito_bypass_validar($falso . '.' . $assinatura, 'salt-a', self::AGORA, self::SEGREDO));
    }

    public function testOutroSegredoNaoValida(): void
    {
        self::assertFalse(gestor_site_acesso_restrito_bypass_validar(self::emitir()['token'], 'salt-a', self::AGORA, 'outro-segredo'));
    }

    public function testTrocarOSaltRevogaTodosOsPasses(): void
    {
        $token = self::emitir('salt-a')['token'];

        self::assertFalse(gestor_site_acesso_restrito_bypass_validar($token, 'salt-novo', self::AGORA, self::SEGREDO));
    }

    public function testPasseExpiradoEhRecusado(): void
    {
        $passe = self::emitir('salt-a', 1);

        self::assertIsArray(gestor_site_acesso_restrito_bypass_validar($passe['token'], 'salt-a', $passe['expira'] - 1, self::SEGREDO));
        self::assertFalse(gestor_site_acesso_restrito_bypass_validar($passe['token'], 'salt-a', $passe['expira'], self::SEGREDO));
    }

    public function testValidadeFicaEntreUmE365Dias(): void
    {
        self::assertSame(self::AGORA + 86400, self::emitir('s', 0)['expira']);
        self::assertSame(self::AGORA + 365 * 86400, self::emitir('s', 9999)['expira']);
    }

    public function testValoresInvalidosSaoRecusados(): void
    {
        foreach ([null, 123, '', 'abc', 'a.b', 'x.' . str_repeat('g', 64), str_repeat('a', 600)] as $lixo) {
            self::assertFalse(gestor_site_acesso_restrito_bypass_validar($lixo, 'salt-a', self::AGORA, self::SEGREDO));
        }
    }

    public function testSemSegredoNaoHaPasse(): void
    {
        self::assertFalse(gestor_site_acesso_restrito_bypass_gerar(30, 'salt-a', self::AGORA, ''));
        self::assertFalse(gestor_site_acesso_restrito_bypass_validar(self::emitir()['token'], 'salt-a', self::AGORA, ''));

        global $_CONFIG;
        $_CONFIG = ['usuario-hash-password' => ''];
        self::assertSame('', gestor_site_acesso_restrito_bypass_segredo());
    }

    public function testSegredoEhDerivadoENaoOBruto(): void
    {
        global $_CONFIG;
        $_CONFIG = ['usuario-hash-password' => 'bruto'];

        $segredo = gestor_site_acesso_restrito_bypass_segredo();
        self::assertNotSame('bruto', $segredo);
        self::assertSame(64, strlen($segredo));
    }

    // ===== Configuração e cookie

    public function testNomeDoCookieVemDaConfiguracao(): void
    {
        global $_CONFIG;
        $_CONFIG = ['cookie-restricted-bypass' => '_C2F_RESTRICTED_BYPASS_ABCD1234'];
        self::assertSame('_C2F_RESTRICTED_BYPASS_ABCD1234', gestor_site_acesso_restrito_bypass_cookie_nome());

        $_CONFIG = [];
        self::assertSame('_C2F_RESTRICTED_BYPASS', gestor_site_acesso_restrito_bypass_cookie_nome());
    }

    public function testSaltLeDoConfigECaiParaEnv(): void
    {
        global $_CONFIG;
        $_CONFIG = ['site-restricted-bypass-salt' => 'do-config'];
        self::assertSame('do-config', gestor_site_acesso_restrito_bypass_salt());

        $_CONFIG = [];
        $_ENV['SITE_RESTRICTED_BYPASS_SALT'] = 'do-env';
        self::assertSame('do-env', gestor_site_acesso_restrito_bypass_salt());
    }

    public function testNavegadorComCookieValidoTemPasse(): void
    {
        global $_CONFIG;
        $_CONFIG = ['usuario-hash-password' => 'bruto', 'site-restricted-bypass-salt' => 'salt-a', 'cookie-restricted-bypass' => '_C2F_RB'];
        $passe = gestor_site_acesso_restrito_bypass_gerar(30);

        $_COOKIE = [];
        self::assertFalse(gestor_site_acesso_restrito_bypass_atual(), 'Sem cookie, sem passe.');

        $_COOKIE['_C2F_RB'] = $passe['token'];
        self::assertIsArray(gestor_site_acesso_restrito_bypass_atual());

        $_CONFIG['site-restricted-bypass-salt'] = 'salt-novo';
        self::assertFalse(gestor_site_acesso_restrito_bypass_atual(), 'Revogação global vale na próxima requisição.');
    }

    public function testConfigDeclaraCookieSaltEValidade(): void
    {
        $config = self::fonte('gestor/config.php');

        self::assertStringContainsString("'cookie-restricted-bypass'", $config);
        self::assertMatchesRegularExpression("/'cookie-restricted-bypass'\\s*=>.*\\\$cookieHostSuffix/", $config);
        self::assertStringContainsString("'site-restricted-bypass-salt'", $config);
        self::assertStringContainsString("'site-restricted-bypass-days'", $config);
    }

    // ===== Porteiro

    public function testPorteiroLiberaVisitanteComPasseAntesDoRedirecionamento(): void
    {
        $fonte = self::fonte('gestor/gestor.php');
        $inicio = strpos($fonte, 'function gestor_roteador_acesso_restrito(){');
        $fim = strpos($fonte, 'function gestor_roteador_acesso_restrito_negado(');
        $corpo = substr($fonte, $inicio, $fim - $inicio);

        $noindex = strpos($corpo, "X-Robots-Tag: noindex, nofollow");
        $semSessao = strpos($corpo, 'if(!gestor_permissao_token()){');
        $passe = strpos($corpo, 'if(gestor_site_acesso_restrito_bypass_atual()) return;');
        $redirect = strpos($corpo, "'redirect' => 'signin/'");

        self::assertNotFalse($passe, 'O porteiro precisa consultar o passe.');
        self::assertTrue($noindex < $semSessao && $semSessao < $passe && $passe < $redirect,
            'O passe só vale para quem está sem sessão, depois do noindex e antes do envio ao signin/.');
    }

    // ===== admin-environment

    public function testAdminEnvironmentTemAsTresAcoes(): void
    {
        $php = self::fonte('gestor/modulos/admin-environment/admin-environment.php');
        foreach (['navegador-autorizar', 'navegador-revogar', 'navegador-revogar-todos'] as $acao) {
            self::assertStringContainsString("case '" . $acao . "'", $php);
        }
        self::assertStringContainsString("'SITE_RESTRICTED_BYPASS_SALT' => \$salt", $php);
        self::assertStringContainsString("'httponly' => true", $php);
        self::assertStringContainsString("'samesite' => 'Lax'", $php);

        $js = self::fonte('gestor/modulos/admin-environment/admin-environment.js');
        foreach (['btn-navegador-autorizar', 'btn-navegador-revogar', 'btn-navegador-revogar-todos'] as $id) {
            self::assertStringContainsString("'#" . $id . "'", $js);
        }
    }

    public function testTelaEVariaveisNosDoisIdiomas(): void
    {
        $modulo = json_decode(self::fonte('gestor/modulos/admin-environment/admin-environment.json'), true);

        foreach (['pt-br', 'en'] as $idioma) {
            $html = self::fonte('gestor/modulos/admin-environment/resources/' . $idioma . '/pages/admin-environment/admin-environment.html');
            foreach (['id="btn-navegador-autorizar"', 'id="btn-navegador-revogar"', 'id="btn-navegador-revogar-todos"', '#site-restricted-bypass-status#', '#site-restricted-bypass-state#'] as $marca) {
                self::assertStringContainsString($marca, $html, $idioma);
            }

            $ids = array_column($modulo['resources'][$idioma]['variables'], 'id');
            preg_match_all('/@\[\[(site-restricted-bypass-[a-z-]+)\]\]@/', $html, $usadas);
            foreach (array_unique(array_merge($usadas[1], ['site-restricted-bypass-active', 'site-restricted-bypass-inactive', 'site-restricted-bypass-authorized', 'site-restricted-bypass-revoked', 'site-restricted-bypass-revoked-all', 'site-restricted-bypass-no-secret'])) as $id) {
                self::assertContains($id, $ids, $idioma . ': ' . $id);
            }
        }
    }
}
