<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosPlanosUsuariosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('usuarios_planos_usuarios', ['id' => 'id_usuarios_planos_usuarios']);
        $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_usuarios_planos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('data_aprovacao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('data_finalizacao', 'datetime', ['null' => true, 'default' => null])
              ->create();
    }
}