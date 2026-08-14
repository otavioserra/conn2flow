<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class GestorComponenteTest extends TestCase
{
    public function testAgrupaIdsParaOsDemaisFiltrosValeremParaTodoOLote(): void
    {
        $escape = static fn(string $value): string => str_replace("'", "\\'", $value);

        self::assertSame(
            "(id='menu' OR id='item')",
            gestor_componente_ids_condicao(['menu', 'item'], $escape)
        );
    }

    public function testEscapaRemoveDuplicadosEIgnoraIdsInvalidos(): void
    {
        $escape = static fn(string $value): string => str_replace("'", "\\'", $value);

        self::assertSame(
            "(id='menu\\'principal' OR id='0')",
            gestor_componente_ids_condicao(["menu'principal", '', null, [], 0, "menu'principal"], $escape)
        );
    }

    public function testListaVaziaNaoGeraCondicaoAberta(): void
    {
        self::assertSame('', gestor_componente_ids_condicao([], static fn(string $value): string => $value));
    }
}
