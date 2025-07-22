<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateModulosGruposTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('modulos_grupos', ['id' => false, 'primary_key' => ['id_modulos_grupos']]);
        $table->addColumn('id_modulos_grupos', 'integer')
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('host', 'boolean', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->addColumn('ordemMenu', 'integer', ['null' => true])
              ->create();
    }
}