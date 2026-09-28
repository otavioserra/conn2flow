<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-191 — o deploy por API (`/_api/project/update`) roda a sincronização de banco na requisição web e
 * estourava o `memory_limit` de 128 MB. `api_memoria_minima()` eleva o limite sem nunca reduzi-lo.
 *
 * O `api.php` roteia ao ser incluído, então só a função é extraída do fonte.
 */
final class ApiMemoriaMinimaTest extends TestCase
{
    private string $limiteAntes = '';

    public static function setUpBeforeClass(): void
    {
        if (function_exists('api_memoria_minima')) return;
        $fonte = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/api/api.php');
        self::assertSame(1, preg_match('/function api_memoria_minima.*?\n}\n/s', $fonte, $m));
        eval($m[0]);
    }

    protected function setUp(): void { $this->limiteAntes = (string) ini_get('memory_limit'); }
    protected function tearDown(): void { ini_set('memory_limit', $this->limiteAntes); }

    public function testElevaLimiteMenor(): void
    {
        ini_set('memory_limit', '128M');
        self::assertSame('1024M', api_memoria_minima('1024M'));
    }

    public function testNaoReduzLimiteMaior(): void
    {
        ini_set('memory_limit', '2G');
        self::assertSame('2G', api_memoria_minima('1024M'));
    }

    public function testIlimitadoFicaIlimitado(): void
    {
        ini_set('memory_limit', '-1');
        self::assertSame('-1', api_memoria_minima('1024M'));
    }
}
