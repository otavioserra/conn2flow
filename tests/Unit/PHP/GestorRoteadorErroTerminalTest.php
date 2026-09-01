<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GestorRoteadorErroTerminalTest extends TestCase
{
    #[DataProvider('rotas404Provider')]
    public function testRota404EhFallbackTerminal(string $caminho): void
    {
        self::assertTrue(gestor_roteador_erro_terminal(404, $caminho));
    }

    /** @return array<string,array{0:string}> */
    public static function rotas404Provider(): array
    {
        return [
            'canonica' => ['404/'],
            'sem barra' => ['404'],
            'com barra inicial' => ['/404/'],
            'com espacos' => [' /404/ '],
        ];
    }

    #[DataProvider('rotasNaoTerminaisProvider')]
    public function testOutrasRotasOuCodigosContinuamUsandoPaginaDeErro(int $codigo, mixed $caminho): void
    {
        self::assertFalse(gestor_roteador_erro_terminal($codigo, $caminho));
    }

    /** @return array<string,array{0:int,1:mixed}> */
    public static function rotasNaoTerminaisProvider(): array
    {
        return [
            'rota comum' => [404, 'signin/'],
            'raiz' => [404, '/'],
            'outro codigo' => [401, '404/'],
            'valor nao textual' => [404, ['404/']],
        ];
    }

    #[DataProvider('rotas404Provider')]
    public function testPagina404ExistentePreservaStatusHttp(string $caminho): void
    {
        self::assertSame(404, gestor_roteador_pagina_status_http($caminho));
    }

    #[DataProvider('paginasComStatusPadraoProvider')]
    public function testPaginasComunsNaoForcamStatusHttp(mixed $caminho): void
    {
        self::assertNull(gestor_roteador_pagina_status_http($caminho));
    }

    /** @return array<string,array{0:mixed}> */
    public static function paginasComStatusPadraoProvider(): array
    {
        return [
            'signin' => ['signin/'],
            'home' => [''],
            'segmento parecido' => ['404/detalhe/'],
            'valor nao textual' => [404],
        ];
    }
}
