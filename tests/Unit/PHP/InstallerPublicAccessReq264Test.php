<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/gestor-instalador/src/Installer.php';

final class InstallerPublicAccessReq264Test extends TestCase
{
    private function installer(array $data = []): Installer
    {
        $reflection = new ReflectionClass(Installer::class);
        $installer = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('data')->setValue($installer, $data + ['domain' => 'meusite.local', 'ssl_enabled' => '1', 'url_raiz' => '/']);
        return $installer;
    }

    public function testEmptySuccessfulResponseIsNotAFunctionalInstallation(): void
    {
        $result = $this->installer()->publicAccessReport(static fn() => ['http_status' => 200, 'body' => " \n"]);
        self::assertSame(['status' => 'failed', 'http_status' => 200, 'bytes' => 0], $result);
    }

    public function testContentIsCheckedAtTheConfiguredPublicRoot(): void
    {
        $result = $this->installer(['url_raiz' => '/tenant/'])->publicAccessReport(static function ($url) {
            self::assertSame('https://meusite.local/tenant/', $url);
            return ['http_status' => 200, 'body' => '<main>Ready</main>'];
        });
        self::assertSame('ok', $result['status']);
        self::assertGreaterThan(0, $result['bytes']);
    }

    public function testServerErrorsAndUnreachableHostsAreFailures(): void
    {
        foreach ([0, 404, 500, 503] as $status) {
            self::assertSame('failed', $this->installer()->publicAccessReport(static fn() => ['http_status' => $status, 'body' => 'error'])['status']);
        }
    }

    public function testInvalidDomainNeverCallsTransport(): void
    {
        self::assertSame(['status' => 'unavailable'], $this->installer(['domain' => 'localhost/path'])->publicAccessReport(static function () {
            self::fail('Invalid domain was contacted.');
        }));
    }
}
