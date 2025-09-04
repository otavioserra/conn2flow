<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

/**
 * Adiciona campos user_modified, system_updated, value_updated na tabela variaveis após descricao.
 */
final class AlterVariaveisAddUpdatedFlags extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('variaveis');
        if (!$table->hasColumn('user_modified')) {
            $table->addColumn('user_modified', 'integer', [
                'limit' => MysqlAdapter::INT_TINY,
                'default' => 0,
                'after' => 'descricao'
            ]);
        }
        if (!$table->hasColumn('system_updated')) {
            $table->addColumn('system_updated', 'integer', [
                'limit' => MysqlAdapter::INT_TINY,
                'default' => 0,
                'after' => 'user_modified'
            ]);
        }
        if (!$table->hasColumn('value_updated')) {
            $table->addColumn('value_updated', 'text', [
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'null' => true,
                'after' => 'system_updated'
            ]);
        }
        $table->save();
    }

    public function down(): void
    {
        $table = $this->table('variaveis');
        foreach (['value_updated','system_updated','user_modified'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->save();
    }
}
