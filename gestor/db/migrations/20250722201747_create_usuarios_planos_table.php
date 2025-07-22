<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosPlanosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('usuarios_planos', ['id' => false, 'primary_key' => ['id_usuarios_planos']]);
        $table->addColumn('id_usuarios_planos', 'integer')
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('cpanel_plano', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('ordem', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('publico', 'boolean', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}