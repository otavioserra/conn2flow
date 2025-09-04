<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

/**
 * Adiciona campos system_updated, html_updated, css_updated na tabela paginas após user_modified.
 * Campos:
 *  - system_updated TINYINT (default 0)
 *  - html_updated MEDIUMTEXT nullable
 *  - css_updated MEDIUMTEXT nullable
 * Reversível: remove os campos no down().
 */
final class AlterPaginasAddUpdatedFlags extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('paginas');
        if (!$table->hasColumn('system_updated')) {
            $table->addColumn('system_updated', 'integer', [
                'limit' => MysqlAdapter::INT_TINY,
                'default' => 0,
                'after' => 'user_modified',
                'comment' => 'Flag de atualização gerenciada pelo sistema'
            ]);
        }
        if (!$table->hasColumn('html_updated')) {
            $table->addColumn('html_updated', 'text', [
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'null' => true,
                'after' => 'system_updated',
                'comment' => 'Snapshot/HTML atualizado via rotina de atualização'
            ]);
        }
        if (!$table->hasColumn('css_updated')) {
            $table->addColumn('css_updated', 'text', [
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'null' => true,
                'after' => 'html_updated',
                'comment' => 'Snapshot/CSS atualizado via rotina de atualização'
            ]);
        }
        $table->save();
    }

    public function down(): void
    {
        $table = $this->table('paginas');
        foreach (['css_updated','html_updated','system_updated'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->save();
    }
}
