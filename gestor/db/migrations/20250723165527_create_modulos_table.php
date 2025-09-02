<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateModulosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('modulos', ['id' => 'id_modulos']);
        $table->addColumn('id_modulos_grupos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => '1'])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('titulo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('icone', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('icone2', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('nao_menu_principal', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('plugin', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('host', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              ->create();
    }
}