<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** REQ-221: controls in the panel and simulation data consumed by the template iframe. */
final class PublisherEditorTailwindReq221Test extends TestCase
{
    private const IDS = [
        'html-editor-publisher-controls',
        'html-editor-publisher-simulation',
        'html-editor-publisher-highlights-simulation',
    ];

    public function testControlsPreserveTemplateAndJavascriptContractsInBothLanguages(): void
    {
        foreach (['pt-br', 'en'] as $language) {
            $base = CONN2FLOW_GESTOR_ROOT . '/resources/' . $language . '/components/';
            $id = self::IDS[0];
            $original = (string)file_get_contents($base . $id . '/' . $id . '.html');
            $variant = (string)file_get_contents($base . $id . '-tailwind/' . $id . '-tailwind.html');
            self::assertStringNotContainsString('class="ui ', $variant);
            self::assertStringNotContainsString('data-tooltip=', $variant);
            foreach ([
                '/<!--\s*([\w-]+\s*[<>])\s*-->/',
                '/(#[a-z][\w-]*#)/',
                '/\sid="([^"]+)"/',
                '/\sdata-(?:type|id|value)="([^"]+)"/',
            ] as $pattern) {
                preg_match_all($pattern, $original, $contract);
                foreach (array_unique($contract[0]) as $token) {
                    self::assertStringContainsString($token, $variant, $language . ': ' . $token);
                }
            }
            foreach ([
                'hep-all-found-msg', 'hep-some-missing-msg', 'hep-all-missing-msg',
                'remove-all-variables', 'publisher-design-mode-variables', 'hep-variables-table',
                'copy-to-clipboard', 'hep-val-found-check', 'hep-val-found-times',
                'hep-val-options-buttons', 'hep-val-options-ok', 'add-variable-skeleton',
                'add-variable-ai', 'remove-variable-skeleton', 'hep-initially-hidden',
            ] as $hook) {
                self::assertMatchesRegularExpression('/class="[^"]*\b' . preg_quote($hook, '/') . '\b/', $variant);
            }
            self::assertStringContainsString('overflow-x-auto', $variant);
            $css = (string)file_get_contents($base . $id . '-tailwind/' . $id . '-tailwind.css');
            self::assertMatchesRegularExpression('/\.hep-initially-hidden\s*\{\s*display:\s*none\s*!important/', $css);
        }
    }

    public function testSimulationBucketsKeepTheOriginalIframeData(): void
    {
        foreach (['pt-br', 'en'] as $language) {
            foreach (array_slice(self::IDS, 1) as $id) {
                $base = CONN2FLOW_GESTOR_ROOT . '/resources/' . $language . '/components/';
                $original = (string)file_get_contents($base . $id . '/' . $id . '.html');
                $variant = (string)file_get_contents($base . $id . '-tailwind/' . $id . '-tailwind.html');
                // The wrapper is hidden panel data. Fomantic classes inside its buckets belong to
                // the simulated Fomantic template iframe; changing them would break that preview.
                self::assertSame(str_replace("\r\n", "\n", $original), str_replace("\r\n", "\n", $variant));
                self::assertStringContainsString('html-editor-publisher-simulation-wrapper" style="display: none;"', $variant);
                foreach (['text', 'textarea', 'image'] as $type) {
                    self::assertStringContainsString('hep-simulation-' . $type, $variant);
                }
                self::assertStringContainsString('class="item"', $variant);
            }
        }
    }

    public function testVariantsHaveTailwindMetadataAndGuardedRuntimeSelection(): void
    {
        foreach (['pt-br', 'en'] as $language) {
            $metadata = json_decode((string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/resources/' . $language . '/components.json'), true);
            $byId = array_column($metadata, null, 'id');
            foreach (self::IDS as $id) {
                self::assertArrayHasKey($id . '-tailwind', $byId);
                self::assertSame('tailwindcss', $byId[$id . '-tailwind']['framework_css']);
                self::assertNotEmpty($byId[$id . '-tailwind']['version']);
            }
        }
        $php = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/html-editor.php');
        self::assertStringContainsString("interface_componente_variante('html-editor-publisher-controls')", $php);
        self::assertStringContainsString('interface_componente_variante($simulation_component_id)', $php);
    }
}
