<?php
declare(strict_types=1);

namespace Req092RateLimit;

use PHPUnit\Framework\TestCase;

function api_rate_limit_subject() { return 'isolated-fixture'; }
function api_rate_limit_contabilizar($route, $subject, $windowStart) {
    $GLOBALS['req092_rate_route'] = $route;
    return $GLOBALS['req092_rate_count'];
}

final class ModuloDistribuidoRateLimitTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $source = str_replace("\r\n", "\n", file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/api/api.php'));
        self::assertSame(1, preg_match('/function api_rate_limit_check.*?\n}\n/s', $source, $match));
        eval('namespace Req092RateLimit; ' . $match[0]);
    }

    public function testCrudBurstHasSeparateBudgetAndGeneralApiKeepsItsLimit(): void
    {
        $before = $GLOBALS['_CONFIG'] ?? [];
        try {
            $GLOBALS['_CONFIG'] = ['api' => ['rate-limit-max' => 100, 'rate-limit-window' => 3600]];
            $GLOBALS['req092_rate_count'] = 101;
            self::assertFalse(api_rate_limit_check('project'));
            self::assertTrue(api_rate_limit_check('modulo-distribuido'));
            self::assertStringEndsWith(':modulo-distribuido', $GLOBALS['req092_rate_route']);
            $GLOBALS['req092_rate_count'] = 1001;
            self::assertFalse(api_rate_limit_check('modulo-distribuido'));
            $GLOBALS['req092_rate_count'] = null;
            self::assertNull(api_rate_limit_check('modulo-distribuido'));
        } finally {
            $GLOBALS['_CONFIG'] = $before;
            unset($GLOBALS['req092_rate_count'], $GLOBALS['req092_rate_route']);
        }
    }
}
