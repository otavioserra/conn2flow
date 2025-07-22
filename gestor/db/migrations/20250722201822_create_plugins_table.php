<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePluginsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('plugins', ['id' => false, 'primary_key' => ['id_plugins']]);
        $table->addColumn('id_plugins', 'integer')
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}