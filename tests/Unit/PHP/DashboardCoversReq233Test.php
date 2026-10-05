<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DashboardCoversReq233Test extends TestCase
{
    public function testCoverHeadersUseRequestedHeightsAndFloatingDragHandleInBothLanguages(): void
    {
        foreach (['pt-br', 'en'] as $language) {
            $path = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
                . 'dashboard' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $language
                . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'dashboard-cards-tailwind'
                . DIRECTORY_SEPARATOR . 'dashboard-cards-tailwind.css';
            $css = (string)file_get_contents($path);

            $htmlPath = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
                . 'dashboard' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $language
                . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'dashboard-cards-tailwind'
                . DIRECTORY_SEPARATOR . 'dashboard-cards-tailwind.html';
            $html = (string)file_get_contents($htmlPath);

            // Alturas das capas conforme req-237.
            self::assertMatchesRegularExpression(
                '/#dashboard-sortable-cards\.density-m \.dashboard-card-header\s*\{\s*height:\s*13\.2rem/s',
                $css,
                "Densidade M deve ter altura de 13.2rem em {$language}."
            );
            self::assertMatchesRegularExpression(
                '/#dashboard-sortable-cards\.density-g \.dashboard-card-header\s*\{\s*height:\s*17\.5rem/s',
                $css,
                "Densidade G deve ter altura de 17.5rem em {$language}."
            );

            // Enquadramento de cover
            self::assertStringContainsString('object-fit: cover', $css);
            self::assertStringContainsString('object-position: center -20px !important', $css);
            self::assertStringContainsString('height: calc(100% + 20px)', $css);

            // Drag handle flutuante não intrusivo
            self::assertStringContainsString('top: 0.625rem', $css, "Alça de arrasto deve ter top 0.625rem (top-2.5) em {$language}.");
            self::assertStringContainsString('left: 0.625rem', $css, "Alça de arrasto deve ter left 0.625rem (left-2.5) em {$language}.");
            self::assertStringContainsString('backdrop-filter', $css, "Alça de arrasto deve ser translúcida em {$language}.");
            self::assertStringContainsString('top-2.5 left-2.5', $html, "Alça no HTML deve ter classes top-2.5 left-2.5 em {$language}.");
        }
    }
}
