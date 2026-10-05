<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DashboardWidgetsReq233Test extends TestCase
{
    public function testWidgetGridUses12ColumnsAndModularSnappingWithout8pxRows(): void
    {
        $dashboard = CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard';
        $script = (string)file_get_contents($dashboard . '/dashboard.js');

        // Validações do JavaScript (resize com snap e seletor evidente)
        self::assertStringContainsString('dashboard-widget-resize-handle', $script);
        self::assertStringContainsString('dashboard-widget-switch-btn', $script);
        self::assertStringContainsString('widgets-registros', $script);
        self::assertStringContainsString('instance_id', $script);
        self::assertStringContainsString('pointerdown', $script);
        self::assertStringContainsString('pointermove', $script);
        self::assertStringContainsString('pointerup', $script);
        self::assertStringContainsString('resize.cols', $script);
        self::assertStringContainsString('resize.rows', $script);
        self::assertStringNotContainsString('gridAutoRows', $script, 'Cálculo de linhas por gridAutoRows proibido pela req-233.');

        foreach (['pt-br', 'en'] as $language) {
            $resources = $dashboard . '/resources/' . $language . '/components/dashboard-cards-tailwind';
            $css = (string)file_get_contents($resources . '/dashboard-cards-tailwind.css');
            $html = (string)file_get_contents($resources . '/dashboard-cards-tailwind.html');

            // Proibição de malha de 24 colunas ou grid-auto-rows de 8px / 0.5rem
            self::assertStringNotContainsString('repeat(24', $css, "Malha de 24 colunas proibida na req-233 ({$language}).");
            self::assertStringNotContainsString('grid-auto-rows', $css, "grid-auto-rows proibido na req-233 ({$language}).");

            // Exigências da req-233: 12 colunas, min-h-[220px], snap em 4, 6, 8, 12 e 1x/2x
            self::assertStringContainsString('grid-template-columns: repeat(12, minmax(0, 1fr))', $css, $language);
            self::assertStringContainsString('min-height: 220px', $css, $language);
            self::assertStringContainsString('min-height: 460px', $css, $language);
            self::assertStringContainsString('col-span-4', $css, $language);
            self::assertStringContainsString('col-span-6', $css, $language);
            self::assertStringContainsString('col-span-8', $css, $language);
            self::assertStringContainsString('col-span-12', $css, $language);
            self::assertStringContainsString('dashboard-widget-resize-handle', $css, $language);
            self::assertStringContainsString('cursor: nwse-resize', $css, $language);

            // Labels e atributos no HTML
            self::assertStringContainsString('data-label-switch="@[[widgets-label-switch]]@"', $html, $language);
            self::assertStringContainsString('data-label-active="@[[widgets-label-active]]@"', $html, $language);
            self::assertStringContainsString('data-label-available="@[[widgets-label-available]]@"', $html, $language);
            self::assertStringContainsString('data-label-add="@[[widgets-label-add]]@"', $html, $language);
            self::assertStringContainsString('data-label-select="@[[widgets-label-select]]@"', $html, $language);
            self::assertStringContainsString('data-label-resize="@[[widgets-label-resize]]@"', $html, $language);
        }
    }
}
