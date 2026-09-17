<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-163 (BATCH-168) — Acesso Restrito ao Site.
 *
 * As decisões (está ligado? a rota é isenta? o perfil entra?) vivem em funções PURAS da biblioteca
 * `gestor`. O porteiro do roteador e a tela do `admin-environment` são verificados pelo contrato do
 * código-fonte: executá-los exige sessão, JWT e banco.
 */
final class SiteAcessoRestritoReq163Test extends TestCase
{
    private $configOriginal;

    protected function setUp(): void
    {
        global $_CONFIG;
        $this->configOriginal = $_CONFIG ?? null;
    }

    protected function tearDown(): void
    {
        global $_CONFIG;
        $_CONFIG = $this->configOriginal;
        unset($_ENV['SITE_RESTRICTED_ACCESS'], $_ENV['SITE_RESTRICTED_PROFILES']);
    }

    private static function fonte(string $relativo): string
    {
        return (string)file_get_contents(dirname(__DIR__, 3) . '/' . $relativo);
    }

    // ===== Flag

    public function testDesligadoPorPadrao(): void
    {
        global $_CONFIG;
        $_CONFIG = [];
        unset($_ENV['SITE_RESTRICTED_ACCESS']);
        putenv('SITE_RESTRICTED_ACCESS');

        self::assertFalse(gestor_site_acesso_restrito_ativo());
    }

    public function testLeDoConfigQuandoPopulado(): void
    {
        global $_CONFIG;
        $_CONFIG = ['site-restricted-access' => true];

        self::assertTrue(gestor_site_acesso_restrito_ativo());

        $_CONFIG = ['site-restricted-access' => false];
        $_ENV['SITE_RESTRICTED_ACCESS'] = 'true';

        self::assertFalse(gestor_site_acesso_restrito_ativo(), 'O $_CONFIG populado é a autoridade.');
    }

    public function testCaiParaEnvQuandoConfigNaoTemAChave(): void
    {
        global $_CONFIG;
        $_CONFIG = [];
        $_ENV['SITE_RESTRICTED_ACCESS'] = 'true';

        self::assertTrue(gestor_site_acesso_restrito_ativo());
    }

    // ===== Perfis

    public function testNormalizaListaDePerfis(): void
    {
        self::assertSame(['1', '3', '12'], gestor_site_acesso_restrito_perfis(' 1, 3,,abc,12;3 0 -2 '));
        self::assertSame([], gestor_site_acesso_restrito_perfis(''));
        self::assertSame(['4', '5'], gestor_site_acesso_restrito_perfis(['4', '5']));
    }

    public function testListaVaziaLiberaQualquerUsuarioLogado(): void
    {
        self::assertTrue(gestor_site_acesso_restrito_perfil_autorizado('7', []));
    }

    public function testPerfilForaDaListaEhRecusado(): void
    {
        self::assertTrue(gestor_site_acesso_restrito_perfil_autorizado('3', ['1', '3']));
        self::assertTrue(gestor_site_acesso_restrito_perfil_autorizado(3, ['1', '3']));
        self::assertFalse(gestor_site_acesso_restrito_perfil_autorizado('2', ['1', '3']));
    }

    public function testAnonimoNuncaEhAutorizado(): void
    {
        self::assertFalse(gestor_site_acesso_restrito_perfil_autorizado('', []));
        self::assertFalse(gestor_site_acesso_restrito_perfil_autorizado('0', []));
    }

    // ===== Rotas isentas

    public function testRotasDeIdentidadeSaoIsentas(): void
    {
        foreach (['signin/', '/signin', 'signin-2fa/', 'signout/', 'forgot-password/', 'redefine-password/', 'validate-user/', 'oauth-callback/', 'cookies-is-mandatory/', '_gestor-cookie-verify/'] as $rota) {
            self::assertTrue(gestor_site_acesso_restrito_rota_isenta($rota), 'Deveria ser isenta: ' . $rota);
        }
    }

    public function testApisEGatewaysSaoIsentos(): void
    {
        foreach (['_api/', '_api/v1/pages/', 'api/', '_gateways/paypal/webhook/'] as $rota) {
            self::assertTrue(gestor_site_acesso_restrito_rota_isenta($rota), 'Deveria ser isenta: ' . $rota);
        }
    }

    public function testPaginasComunsNaoSaoIsentas(): void
    {
        foreach (['/', '', 'contato/', 'dashboard/', 'signin-falso/', 'apis/', 'blog/signin/'] as $rota) {
            self::assertFalse(gestor_site_acesso_restrito_rota_isenta($rota), 'Não deveria ser isenta: ' . $rota);
        }
    }

    // ===== Contrato do roteador

    public function testPorteiroRodaAntesDaConsultaDaPagina(): void
    {
        $fonte = self::fonte('gestor/gestor.php');
        $inicio = strpos($fonte, 'function gestor_roteador(){');
        self::assertNotFalse($inicio);

        $chamada = strpos($fonte, 'gestor_roteador_acesso_restrito();', $inicio);
        $consulta = strpos($fonte, '"paginas",', $inicio);

        self::assertNotFalse($chamada, 'gestor_roteador() precisa chamar o porteiro.');
        self::assertLessThan($consulta, $chamada, 'O porteiro deve rodar antes de consultar a página (sem_permissao não pode abrir brecha).');
    }

    public function testPorteiroNaoConfiaNoCookieDePerfil(): void
    {
        $fonte = self::fonte('gestor/gestor.php');
        $inicio = strpos($fonte, 'function gestor_roteador_acesso_restrito(){');
        $fim = strpos($fonte, 'function gestor_roteador_acesso_restrito_negado(', $inicio);
        $corpo = substr($fonte, $inicio, $fim - $inicio);

        self::assertStringContainsString('gestor_permissao_token()', $corpo);
        self::assertStringContainsString('gestor_usuario()', $corpo);
        self::assertStringNotContainsString('gestor_usuario_perfil()', $corpo, 'O cookie authprofile não é assinado.');
        self::assertStringContainsString("'redirect' => 'signin/'", $corpo);
        self::assertStringContainsString('X-Robots-Tag: noindex, nofollow', $corpo);
    }

    public function testNoindexForcadoNoHeadQuandoAtivo(): void
    {
        $fonte = self::fonte('gestor/gestor.php');
        $inicio = strpos($fonte, 'function gestor_pagina_extra_head_e_javascript(){');
        $corpo = substr($fonte, $inicio, 6000);

        self::assertMatchesRegularExpression('/if\(gestor_site_acesso_restrito_ativo\(\)[^\n]*\)\{\s*\n\s*\$_GESTOR\[\'html-extra-head\'\]\[\] = \'<meta name="robots" content="noindex, nofollow">\'/', $corpo);
    }

    // ===== Contrato da tela

    public function testAdminEnvironmentPersisteAsDuasChaves(): void
    {
        $php = self::fonte('gestor/modulos/admin-environment/admin-environment.php');

        self::assertStringContainsString("\$data['SITE_RESTRICTED_ACCESS']", $php);
        self::assertStringContainsString("\$data['SITE_RESTRICTED_PROFILES']", $php);
        self::assertStringContainsString("case 'buscar-perfis':", $php);
        self::assertStringContainsString("'site-restricted-lockout'", $php);
    }

    public function testSecaoVemAntesDoHtmlEntregueNosDoisIdiomas(): void
    {
        $ptbr = self::fonte('gestor/modulos/admin-environment/resources/pt-br/pages/admin-environment/admin-environment.html');
        $restrito = strpos($ptbr, 'Acesso Restrito ao Site');
        $html = strpos($ptbr, 'HTML entregue ao navegador');
        self::assertNotFalse($restrito);
        self::assertLessThan($html, $restrito);

        $en = self::fonte('gestor/modulos/admin-environment/resources/en/pages/admin-environment/admin-environment.html');
        self::assertStringContainsString('Restricted Site Access', $en);

        foreach ([$ptbr, $en] as $template) {
            foreach (['#site-restricted-access-checked#', '#site-restricted-profiles#', '#site-restricted-profiles-badges#'] as $marcador) {
                self::assertStringContainsString($marcador, $template);
            }
        }
    }

    public function testVariaveisDeRecusaExistemNosDoisIdiomas(): void
    {
        foreach (['pt-br', 'en'] as $idioma) {
            $variaveis = json_decode(self::fonte('gestor/resources/' . $idioma . '/variables.json'), true);
            $ids = array_column($variaveis, 'id');
            foreach (['site-restricted-denied-title', 'site-restricted-denied-text', 'site-restricted-denied-signout'] as $id) {
                self::assertContains($id, $ids, $idioma . ': ' . $id);
            }
        }
    }
}
