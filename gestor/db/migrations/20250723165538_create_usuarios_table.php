<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('usuarios', ['id' => 'id_usuarios']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_usuarios_perfis', 'integer', ['null' => true, 'default' => null])
              ->addColumn('nome_conta', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('usuario', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('senha', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('email', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('primeiro_nome', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('ultimo_nome', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('nome_do_meio', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('data_modificacao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('email_confirmado', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('gestor', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('gestor_perfil', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->create();
    }
}