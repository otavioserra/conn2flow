<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPluginsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_plugins', ['id' => 'id_hosts_plugins']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('plugin', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('versao', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('versao_num', 'integer', ['null' => true, 'default' => null])
              ->addColumn('habilitado', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('versao_config', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('data_modificacao', 'datetime', ['null' => true, 'default' => null])
              ->create();
    }
}