<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsLayoutsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_layouts', ['id' => false, 'primary_key' => ['id_hosts_layouts']]);
        $table->addColumn('id_hosts_layouts', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('html', 'string', ['null' => true])
              ->addColumn('css', 'string', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->addColumn('template_padrao', 'boolean', ['null' => true])
              ->addColumn('template_categoria', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('template_id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('template_modificado', 'boolean', ['null' => true])
              ->addColumn('template_versao', 'integer', ['null' => true])
              ->create();
    }
}