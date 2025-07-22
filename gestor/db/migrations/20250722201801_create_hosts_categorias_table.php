<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsCategoriasTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_categorias', ['id' => false, 'primary_key' => ['id_hosts_categorias']]);
        $table->addColumn('id_hosts_categorias', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('id_modulos', 'integer', ['null' => true])
              ->addColumn('id_hosts_categorias_pai', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}