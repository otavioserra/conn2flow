<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminTopbarReq233Test extends TestCase
{
    public function testTopbarExposesResponsiveControlsAndFavoriteOrdering(): void
    {
        $gestor = CONN2FLOW_GESTOR_ROOT;
        $script = (string)file_get_contents($gestor . '/assets/global/admin-topbar.js');

        self::assertStringContainsString('admin-topbar-ordenar', $script);
        self::assertStringContainsString('data-topbar-drag-handle', $script);
        self::assertStringContainsString("addEventListener('dragstart'", $script);
        self::assertStringContainsString("setAttribute('data-c2f-dica-pos', 'bottom')", $script);

        $library = (string)file_get_contents($gestor . '/bibliotecas/admin-topbar.php');
        self::assertStringContainsString("'ordem ASC, '", $library);

        foreach (['pt-br', 'en'] as $language) {
            $html = (string)file_get_contents($gestor . '/resources/' . $language
                . '/components/admin-topbar-tailwind/admin-topbar-tailwind.html');
            self::assertStringContainsString('h-11', $html, $language);
            self::assertStringContainsString('max-w-[190px]', $html, $language);
            self::assertStringContainsString('w-80', $html, $language);
            self::assertStringContainsString('data-topbar-favorite-order', $html, $language);
            self::assertStringContainsString('data-c2f-dica-pos="bottom"', $html, $language);
            self::assertStringContainsString('data-topbar-choices class="max-h-[50vh] space-y-2', $html, $language);
        }
    }

    public function testFavoriteOrderingHasSchemaSupport(): void
    {
        $gestor = CONN2FLOW_GESTOR_ROOT;
        $migrations = glob($gestor . '/db/migrations/*add_ordem_to_usuarios_topbar_favoritos.php');

        self::assertNotFalse($migrations);
        self::assertCount(1, $migrations);
        self::assertStringContainsString('addColumn(\'ordem\'', (string)file_get_contents($migrations[0]));
    }

    public function testContentWidthPreferenceIsConnectedAcrossLayoutAndTopbar(): void
    {
        $gestor = CONN2FLOW_GESTOR_ROOT;
        $library = (string)file_get_contents($gestor . '/bibliotecas/admin-topbar.php');
        $topbarScript = (string)file_get_contents($gestor . '/assets/global/admin-topbar.js');
        $layoutScript = (string)file_get_contents($gestor . '/assets/global/admin-tailwind.js');

        self::assertStringContainsString('admin_content_width', $library);
        self::assertStringContainsString('admin-topbar-largura', $library);
        self::assertStringContainsString('data-topbar-content-width', $topbarScript);
        self::assertStringContainsString('data-admin-max-width', $layoutScript);

        foreach (['pt-br', 'en'] as $language) {
            $layout = (string)file_get_contents($gestor . '/resources/' . $language
                . '/layouts/layout-administrativo-tailwind/layout-administrativo-tailwind.html');
            $topbar = (string)file_get_contents($gestor . '/resources/' . $language
                . '/components/admin-topbar-tailwind/admin-topbar-tailwind.html');
            self::assertStringContainsString('data-admin-max-width="normal"', $layout, $language);
            self::assertStringContainsString('data-topbar-content-width', $topbar, $language);
        }
    }
}
