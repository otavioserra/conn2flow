<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/modulo-distribuido.php';

/**
 * req-218: imagens estáticas do layout (logo do portal) dentro do prefixo do painel distribuído voltam ao
 * próprio endereço, em vez de cair no roteador e dar 404.
 */
final class ModuloDistribuidoEstaticosReq218Test extends TestCase
{
    private array $antes = [];

    protected function setUp(): void
    {
        global $_GESTOR;
        $this->antes = $_GESTOR ?? [];
    }

    protected function tearDown(): void
    {
        global $_GESTOR;
        $_GESTOR = $this->antes;
    }

    public function testStaticFoldersAreListed(): void
    {
        self::assertSame(['vendor', 'favicon', 'images'], MODULO_DISTRIBUIDO_PASTAS_ESTATICAS);
    }

    public function testModuleRouteUnderThePrefixKeepsThePrefixedRoot(): void
    {
        global $_GESTOR;
        $id = str_repeat('a', 64);
        $_GESTOR['caminho'] = ['_distributed', 'run', $id, 'marketplace'];
        $_GESTOR['caminho-total'] = '_distributed/run/' . $id . '/marketplace/';
        $_GESTOR['url-raiz'] = '/';
        modulo_distribuido_prefixo_normalizar();
        self::assertSame('/_distributed/run/' . $id . '/', $_GESTOR['url-raiz']);
        self::assertSame('marketplace/', $_GESTOR['caminho-total']);
    }

    public function testRedirectRuleCoversTheLogoExtensionsWithoutTraversal(): void
    {
        $fonte = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/modulo-distribuido-protocolo.php');
        self::assertStringContainsString("in_array(\$_GESTOR['caminho'][0] ?? '', MODULO_DISTRIBUIDO_PASTAS_ESTATICAS, true)", $fonte);
        self::assertStringContainsString("!in_array('..', \$_GESTOR['caminho'], true)", $fonte);
        self::assertMatchesRegularExpression("/\\['css', 'js', 'woff', 'woff2', 'ttf', 'eot', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp'\\]/", $fonte);
    }
}
