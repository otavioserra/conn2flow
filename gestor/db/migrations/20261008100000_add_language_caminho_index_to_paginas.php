<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * REQ-263: índice de idioma + caminho em `paginas`.
 *
 * Toda requisição começa procurando a página pelo caminho (`WHERE caminho=… AND language=…`, em `gestor.php`).
 * Sem índice no caminho, o banco usava só o de idioma, percorria todas as páginas do idioma e, como a consulta
 * pede o HTML e o CSS, carregava o conteúdo de todas para ficar com uma. Com a tabela maior que a memória do
 * banco, cada visita lia dezenas de megabytes do disco.
 *
 * `caminho` é TEXT, então o índice usa prefixo: 191 caracteres cabem no limite de chave com utf8mb4 e cobrem os
 * caminhos reais com folga.
 */
final class AddLanguageCaminhoIndexToPaginas extends AbstractMigration
{
    private const INDICE = 'idx_paginas_language_caminho';

    public function up(): void
    {
        if (!$this->hasTable('paginas')) {
            return;
        }
        $tabela = $this->table('paginas');
        if (!$tabela->hasColumn('language') || !$tabela->hasColumn('caminho') || $tabela->hasIndexByName(self::INDICE)) {
            return;
        }
        $tabela->addIndex(['language', 'caminho'], ['name' => self::INDICE, 'limit' => ['caminho' => 191]])->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('paginas')) {
            return;
        }
        $tabela = $this->table('paginas');
        if ($tabela->hasIndexByName(self::INDICE)) {
            $tabela->removeIndexByName(self::INDICE)->update();
        }
    }
}
