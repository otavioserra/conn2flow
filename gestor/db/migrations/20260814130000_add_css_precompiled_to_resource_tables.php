<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class AddCssPrecompiledToResourceTables extends AbstractMigration
{
    /**
     * req-114 — camada Tailwind gerada offline pelo release/deploy.
     *
     * O campo fica separado de `css` (autoral) e `css_compiled` (editor online).
     */
    public function up(): void
    {
        foreach (['paginas', 'layouts', 'componentes', 'templates'] as $tableName) {
            if (!$this->hasTable($tableName)) {
                continue;
            }

            $table = $this->table($tableName);
            if (!$table->hasColumn('css_precompiled')) {
                $table->addColumn('css_precompiled', 'text', [
                    'limit' => MysqlAdapter::TEXT_MEDIUM,
                    'null' => true,
                    'default' => null,
                    'after' => 'css',
                ])->update();
            }
        }
    }

    /**
     * A remoção automática seria destrutiva para CSS publicado. O rollback do código tolera
     * a coluna extra; uma eventual remoção deve ser feita por migração dedicada e auditada.
     */
    public function down(): void
    {
    }
}
