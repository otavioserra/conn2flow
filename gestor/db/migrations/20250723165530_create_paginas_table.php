<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePaginasTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('paginas', ['id' => 'id_paginas']);
        $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_layouts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('tipo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('modulo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('opcao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('raiz', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('sem_permissao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('css', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('data_modificacao', 'datetime', ['null' => true, 'default' => null])
              ->create();
    }
}