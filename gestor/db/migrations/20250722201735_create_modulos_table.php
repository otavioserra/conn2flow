<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateModulosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('modulos', ['id' => false, 'primary_key' => ['id_modulos']]);
        $table->addColumn('id_modulos', 'integer')
              ->addColumn('id_modulos_grupos', 'integer', ['null' => true])
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('titulo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('icone', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('icone2', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('nao_menu_principal', 'boolean', ['null' => true])
              ->addColumn('plugin', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('host', 'boolean', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}