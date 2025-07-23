<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsLayoutsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_layouts', ['id' => 'id_hosts_layouts']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('css', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('data_modificacao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('template_padrao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('template_categoria', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('template_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('template_modificado', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('template_versao', 'integer', ['null' => true, 'default' => null])
              ->create();
    }
}