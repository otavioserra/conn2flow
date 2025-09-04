<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

/**
 * Adiciona campos system_updated, html_updated, css_updated na tabela layouts após user_modified.
 * Reversível removendo os campos.
 */
final class AlterLayoutsAddUpdatedFlags extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('layouts');
        if (!$table->hasColumn('system_updated')) {
            $table->addColumn('system_updated', 'integer', [
                'limit' => MysqlAdapter::INT_TINY,
                'default' => 0,
                'after' => 'user_modified'
            ]);
        }
        if (!$table->hasColumn('html_updated')) {
            $table->addColumn('html_updated', 'text', [
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'null' => true,
                'after' => 'system_updated'
            ]);
        }
        if (!$table->hasColumn('css_updated')) {
            $table->addColumn('css_updated', 'text', [
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'null' => true,
                'after' => 'html_updated'
            ]);
        }
        $table->save();
    }

    public function down(): void
    {
        $table = $this->table('layouts');
        foreach (['css_updated','html_updated','system_updated'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->save();
    }
}
