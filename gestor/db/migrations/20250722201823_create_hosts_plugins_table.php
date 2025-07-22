<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPluginsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_plugins', ['id' => false, 'primary_key' => ['id_hosts_plugins']]);
        $table->addColumn('id_hosts_plugins', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('plugin', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('versao', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('versao_num', 'integer', ['null' => true])
              ->addColumn('habilitado', 'boolean', ['null' => true])
              ->addColumn('versao_config', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}