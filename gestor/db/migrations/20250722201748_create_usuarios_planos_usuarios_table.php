<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosPlanosUsuariosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('usuarios_planos_usuarios', ['id' => false, 'primary_key' => ['id_usuarios_planos_usuarios']]);
        $table->addColumn('id_usuarios_planos_usuarios', 'integer')
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('id_usuarios_planos', 'integer', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_aprovacao', 'datetime', ['null' => true])
              ->addColumn('data_finalizacao', 'datetime', ['null' => true])
              ->create();
    }
}