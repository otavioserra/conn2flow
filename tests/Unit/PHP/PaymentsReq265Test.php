<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class PaymentsReq265Test extends TestCase
{
    public function testPaymentAndNotificationSafety(): void
    {
        exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__, 2).'/Fixtures/req265-payments.php').' 2>&1', $output, $status);
        self::assertSame(0, $status, implode("\n", $output));
    }
}
