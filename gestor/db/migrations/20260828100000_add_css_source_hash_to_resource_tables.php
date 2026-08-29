<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCssSourceHashToResourceTables extends AbstractMigration
{
    /**
     * req-141 / CR-002 — procedência do CSS derivado.
     *
     * `html` e `css` são autoria; `css_precompiled` e `css_compiled` são derivados dela. Esta coluna
     * guarda a assinatura das entradas que geraram o derivado, para que o sistema saiba quando ele
     * deixou de corresponder ao HTML — em vez de servir um híbrido em silêncio.
     *
     * Nasce NULL em todo o acervo existente, e `gestor_css_procedencia_valida()` trata ausência como
     * stale de propósito: o CSS já gravado realmente não tem procedência conhecida.
     */
    public function up(): void
    {
        foreach (['paginas', 'layouts', 'componentes', 'templates'] as $tableName) {
            if (!$this->hasTable($tableName)) {
                continue;
            }

            $table = $this->table($tableName);
            if (!$table->hasColumn('css_source_hash')) {
                $table->addColumn('css_source_hash', 'string', [
                    'limit' => 64,
                    'null' => true,
                    'default' => null,
                    'after' => 'css_compiled',
                ])->update();
            }
        }
    }

    /**
     * Remover a coluna descartaria a procedência de todo o acervo já assinado e devolveria o sistema
     * ao estado em que o híbrido era invisível. O rollback do código tolera a coluna extra.
     */
    public function down(): void
    {
    }
}
