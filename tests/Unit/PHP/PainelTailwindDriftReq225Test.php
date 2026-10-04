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

    public function testSitePanelPagesDoNotContainLegacyMarkup(): void
    {
        $site = getenv('CONN2FLOW_SITE_ROOT') ?: dirname(CONN2FLOW_ROOT) . '/conn2flow-site';
        if (!is_dir($site)) self::markTestSkipped('Set CONN2FLOW_SITE_ROOT to audit the site checkout.');
        $this->assertRepository($site, 'site');
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
