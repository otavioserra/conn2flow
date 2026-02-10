<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateFormsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('forms', ['id' => 'id_forms']);
        $table->addColumn('id_users', 'integer', ['null' => true, 'default' => 1])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('template_id', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('path_success', 'text', ['null' => true])
            ->addColumn('path_error', 'text', ['null' => true])
            ->addColumn('fields_schema', 'json', ['null' => true])
            ->addColumn('plugin', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
            ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
            ->addColumn('version', 'integer', ['null' => true, 'default' => 1])
            ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
            // Hybrid system fields
            ->addColumn('user_modified', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
            ->addColumn('system_updated', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
            ->addIndex(['id', 'language'], ['unique' => true])
            ->addIndex(['plugin'])
            ->addIndex(['language'])
            ->create();
    }
}