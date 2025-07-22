<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosGestoresPerfisTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('usuarios_gestores_perfis', ['id' => false, 'primary_key' => ['id_usuarios_gestores_perfis']]);
        $table->addColumn('id_usuarios_gestores_perfis', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}