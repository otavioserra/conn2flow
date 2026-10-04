<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/sdd/validation/req225-inventory.php';

final class PainelTailwindDriftReq225Test extends TestCase
{
    public function testDetectorRejectsLegacyMarkup(): void
    {
        foreach ([
            '<div class="ui form">', "<div class='ui form'>",
            '<button title="Edit">', "<button\n title='Edit'>", '<button title=Edit>',
            '<button data-label="a > b" title="Edit">',
        ] as $html) {
            self::assertNotEmpty(Req225Inventory::markupViolations($html), $html);
        }
        self::assertSame([], Req225Inventory::markupViolations('<div class="c2fc-campo"><button data-c2f-dica="Edit">'));
        self::assertSame([], Req225Inventory::markupViolations('<!-- <div class="ui form"> --><input title="Allowed">'));
        // DEC-132 permits legacy hooks after the visual c2fc class.
        self::assertSame([], Req225Inventory::markupViolations('<div class="c2fc-cobertura ui dimmer">'));
    }

    public function testCorePanelPagesDoNotContainLegacyMarkup(): void
    {
        $this->assertRepository(getenv('CONN2FLOW_CORE_AUDIT_ROOT') ?: CONN2FLOW_ROOT, 'core');
    }

    public function testSharedPanelLayoutDoesNotContainNativeButtonTips(): void
    {
        foreach (['pt-br', 'en'] as $language) {
            $path = CONN2FLOW_ROOT . '/gestor/resources/' . $language
                . '/layouts/layout-administrativo-tailwind/layout-administrativo-tailwind.html';
            self::assertSame([], Req225Inventory::markupViolations((string)file_get_contents($path)), $language);
        }
    }

    public function testTailwindComponentButtonsUsePanelTips(): void
    {
        $site = getenv('CONN2FLOW_SITE_ROOT') ?: dirname(CONN2FLOW_ROOT) . '/conn2flow-site';
        foreach ([CONN2FLOW_ROOT, $site] as $root) {
            $files = array_merge(
                glob($root . '/gestor/resources/*/components/*/*tailwind.html'),
                glob($root . '/gestor/modulos/*/resources/*/components/*/*tailwind.html')
            );
            foreach ($files as $path) {
                $issues = array_filter(Req225Inventory::markupViolations((string)file_get_contents($path)),
                    static fn(string $issue): bool => str_starts_with($issue, 'button title:'));
                self::assertSame([], $issues, $path);
            }
        }
    }

    public function testSitePanelPagesDoNotContainLegacyMarkup(): void
    {
        $site = getenv('CONN2FLOW_SITE_ROOT') ?: dirname(CONN2FLOW_ROOT) . '/conn2flow-site';
        if (!is_dir($site)) self::markTestSkipped('Set CONN2FLOW_SITE_ROOT to audit the site checkout.');
        $this->assertRepository($site, 'site');
    }

    public function testPanelBundlesResolveAllDeclaredDependencies(): void
    {
        require_once CONN2FLOW_ROOT . '/gestor/controladores/agents/arquitetura/tailwind-recursos.php';
        $keys = ['GESTOR_DIR', 'SYSTEM_PATH', 'CLI_ARGS'];
        $saved = [];
        foreach ($keys as $key) $saved[$key] = [array_key_exists($key, $GLOBALS), $GLOBALS[$key] ?? null];
        $site = getenv('CONN2FLOW_SITE_ROOT') ?: dirname(CONN2FLOW_ROOT) . '/conn2flow-site';
        $roots = [CONN2FLOW_ROOT];
        if (is_dir($site)) $roots[] = $site;
        try {
            foreach ($roots as $root) {
                $GLOBALS['GESTOR_DIR'] = $root . '/gestor';
                $GLOBALS['SYSTEM_PATH'] = CONN2FLOW_ROOT . '/';
                $GLOBALS['CLI_ARGS'] = $root === CONN2FLOW_ROOT ? [] : ['project-path' => $root . '/gestor'];
                foreach (glob($root . '/gestor/modulos/*/*.json') as $manifest) {
                    $module = basename(dirname($manifest));
                    if (basename($manifest) !== $module . '.json') continue;
                    $data = json_decode((string)file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
                    foreach ($data['resources'] ?? [] as $language => $resources) {
                        foreach ($resources['pages'] ?? [] as $page) {
                            if (($page['layout'] ?? '') !== 'layout-administrativo-tailwind') continue;
                            self::assertTrue($page['tailwind_bundle'] ?? false, $module . '/' . $page['id']);
                            $resolved = tailwind_recursos_dependencies($page, 'module', $module, $language, 'pages');
                            self::assertContains('admin-topbar-tailwind', array_map(fn($path) => basename($path, '.html'), $resolved));
                        }
                    }
                }
            }
        } finally {
            foreach ($saved as $key => [$exists, $value]) {
                if ($exists) $GLOBALS[$key] = $value;
                else unset($GLOBALS[$key]);
            }
        }
    }

    private function assertRepository(string $root, string $project): void
    {
        $rows = Req225Inventory::scan($root, $project);
        self::assertNotEmpty($rows, $project . ': no panel pages discovered');
        $issues = [];
        foreach ($rows as $row) {
            foreach ($row['violations'] as $issue) {
                // Bundle uniformity is audited separately; CA-3 guards markup.
                if ($issue === 'tailwind_bundle absent') continue;
                $issues[] = $row['source'] . ': ' . $issue;
            }
        }
        self::assertCount(0, $issues, implode("\n", array_slice($issues, 0, 12)) . "\nTotal: " . count($issues));
    }
}
