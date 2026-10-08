<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-263 (BATCH-272) — índice de idioma + caminho em `paginas`.
 * A busca da página pelo caminho roda em toda requisição e precisa de um índice que comece pelo que ela filtra.
 */
final class PaginasIndiceCaminhoReq263Test extends TestCase
{
    private static function gestor(): string
    {
        return dirname(__DIR__, 3) . '/gestor/';
    }

    public function testMigracaoCriaOIndiceDeIdiomaECaminhoComPrefixo(): void
    {
        $migracao = (string)file_get_contents(self::gestor() . 'db/migrations/20261008100000_add_language_caminho_index_to_paginas.php');
        self::assertStringContainsString("'idx_paginas_language_caminho'", $migracao);
        // Idioma primeiro, caminho depois; `caminho` é TEXT e só entra em índice com prefixo.
        self::assertStringContainsString("addIndex(['language', 'caminho'], ['name' => self::INDICE, 'limit' => ['caminho' => 191]])", $migracao);
        // Pode rodar de novo, e em instalação sem a tabela, sem erro.
        self::assertStringContainsString('hasIndexByName(self::INDICE)', $migracao);
        self::assertStringContainsString("hasTable('paginas')", $migracao);
        self::assertStringContainsString('removeIndexByName(self::INDICE)', $migracao);
        // 191 caracteres em utf8mb4 mais o idioma cabem no limite de chave do InnoDB.
        self::assertLessThanOrEqual(3072, 191 * 4 + 10 * 4 + 4);
    }

    public function testAsBuscasDePaginaPorCaminhoFiltramIdiomaECaminho(): void
    {
        $gestor = (string)file_get_contents(self::gestor() . 'gestor.php');
        preg_match_all('/"WHERE caminho=\'"\.banco_escape_field\(\$caminho\)\."\'"(.{0,400})/s', $gestor, $buscas);
        self::assertGreaterThanOrEqual(3, count($buscas[0]));
        // O índice só serve à busca que filtra o caminho por igualdade e também o idioma.
        $comIdioma = array_filter($buscas[1], static fn ($resto) => str_contains($resto, 'language='));
        self::assertGreaterThanOrEqual(3, count($comIdioma));
    }
}
