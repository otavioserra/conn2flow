<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosPerfisTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('usuarios_perfis', ['id' => false, 'primary_key' => ['id_usuarios_perfis']]);
        $table->addColumn('id_usuarios_perfis', 'integer')
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('padrao', 'boolean', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}