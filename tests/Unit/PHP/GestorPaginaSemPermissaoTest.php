<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * req-268: `paginas.sem_permissao` só abre a página quando vale 1. O `0` vindo do banco como texto
 * (`"without_permission": false`) passava pelo `existe()` do roteador e deixava a página pública.
 */
final class GestorPaginaSemPermissaoTest extends TestCase
{
    #[DataProvider('publicasProvider')]
    public function testValorUmAbreAPagina(mixed $valor): void
    {
        self::assertTrue(gestor_pagina_sem_permissao($valor));
    }

    /** @return array<string,array{0:mixed}> */
    public static function publicasProvider(): array
    {
        return [
            'texto do banco' => ['1'],
            'inteiro' => [1],
            'booleano' => [true],
            'com espacos' => [' 1 '],
        ];
    }

    #[DataProvider('restritasProvider')]
    public function testDemaisValoresExigemLogin(mixed $valor): void
    {
        self::assertFalse(gestor_pagina_sem_permissao($valor));
    }

    /** @return array<string,array{0:mixed}> */
    public static function restritasProvider(): array
    {
        return [
            'zero do banco' => ['0'],
            'zero inteiro' => [0],
            'nulo' => [null],
            'vazio' => [''],
            'falso' => [false],
            'outro numero' => ['2'],
            'lista' => [['1']],
        ];
    }

    public function testRoteadorUsaAFuncaoNaChecagemDaPagina(): void
    {
        $fonte = str_replace("\r\n", "\n", (string)file_get_contents(dirname(__DIR__, 3) . '/gestor/gestor.php'));

        self::assertStringContainsString("if(!gestor_pagina_sem_permissao(\$paginas[0]['sem_permissao'] ?? null)){", $fonte);
        self::assertStringNotContainsString("if(!existe(\$paginas[0]['sem_permissao'])){", $fonte);
    }

    public function testTelasDeEdicaoNaoMarcamPaginaRestritaComoPublica(): void
    {
        foreach (['admin-paginas', 'publisher-pages'] as $modulo) {
            $fonte = (string)file_get_contents(dirname(__DIR__, 3) . "/gestor/modulos/$modulo/$modulo.php");
            self::assertStringNotContainsString("isset(\$retorno_bd['sem_permissao'])", $fonte, $modulo);
        }
    }
}
