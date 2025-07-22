<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePluginsHostsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('plugins_hosts', ['id' => false, 'primary_key' => ['id_plugins_hosts']]);
        $table->addColumn('id_plugins_hosts', 'integer')
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('publico', 'boolean', ['null' => true])
              ->addColumn('diretorio', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('git', 'boolean', ['null' => true])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}