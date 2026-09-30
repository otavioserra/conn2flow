<?php

declare(strict_types=1);

use Conn2Flow\Cli\Support\MigrationChecker;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/cli/src/Support/MigrationChecker.php';

/**
 * req-197 / BATCH-201: `c2f db:check-migrations` — versão e classe duplicadas entre origens.
 */
final class MigrationCheckerTest extends TestCase
{
    private string $raiz;

    protected function setUp(): void
    {
        $this->raiz = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-mchk-' . uniqid();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->raiz . '/*/*') ?: [] as $f) @unlink($f);
        foreach (glob($this->raiz . '/*') ?: [] as $d) @rmdir($d);
        @rmdir($this->raiz);
    }

    private function pasta(string $nome, string ...$arquivos): string
    {
        $dir = $this->raiz . DIRECTORY_SEPARATOR . $nome;
        @mkdir($dir, 0777, true);
        foreach ($arquivos as $a) file_put_contents($dir . DIRECTORY_SEPARATOR . $a, '<?php');
        return $dir;
    }

    public function testSemChoque(): void
    {
        $r = (new MigrationChecker())->check([
            'core' => $this->pasta('core', '20260101000000_create_a.php', '20260102000000_create_b.php'),
            'projeto' => $this->pasta('projeto', '20260103000000_create_c.php'),
            'ausente' => $this->raiz . '/nao-existe',
        ]);
        $this->assertSame(3, $r['files']);
        $this->assertSame([], $r['duplicates']);
    }

    public function testVersaoDuplicadaEntreOrigens(): void
    {
        $r = (new MigrationChecker())->check([
            'core' => $this->pasta('core', '20260929140000_create_affiliates_tables.php'),
            'projeto' => $this->pasta('projeto', '20260929140000_create_coupons_tables.php'),
        ]);
        $this->assertCount(1, $r['duplicates']);
        $this->assertSame('version', $r['duplicates'][0]['kind']);
        $this->assertSame('20260929140000', $r['duplicates'][0]['key']);
        $this->assertStringContainsString('(core)', implode(' ', $r['duplicates'][0]['files']));
    }

    public function testClasseDuplicada(): void
    {
        $r = (new MigrationChecker())->check([
            'projeto' => $this->pasta('projeto', '20260101000000_create_checkout_settings.php', '20260202000000_create_checkout_settings.php'),
        ]);
        $this->assertCount(1, $r['duplicates']);
        $this->assertSame('class', $r['duplicates'][0]['kind']);
        $this->assertSame('CreateCheckoutSettings', $r['duplicates'][0]['key']);
    }

    public function testMesmoArquivoEmDuasOrigensNaoEChoque(): void
    {
        $r = (new MigrationChecker())->check([
            'core' => $this->pasta('core', '20260101000000_create_a.php'),
            'espelho' => $this->pasta('espelho', '20260101000000_create_a.php'),
        ]);
        $this->assertSame([], $r['duplicates']);
    }

    public function testNomeForaDoPadraoAvisado(): void
    {
        $r = (new MigrationChecker())->check(['core' => $this->pasta('core', 'helper.php', '20260101000000_create_a.php')]);
        $this->assertSame(1, $r['files']);
        $this->assertSame(['core: helper.php'], $r['invalid']);
    }
}
