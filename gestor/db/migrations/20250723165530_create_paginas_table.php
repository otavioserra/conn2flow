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
      // id_usuarios agora padronizado para default = 1 (registro de sistema/base)
      $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => 1])
          // Substituição: removido id_layouts (integer) e adicionado layout_id (string) referenciando layouts.id
          ->addColumn('layout_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('tipo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('modulo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('opcao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('raiz', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('sem_permissao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('css', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => 1])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              // Hybrid system fields
              ->addColumn('user_modified', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
              ->addColumn('file_version', 'string', ['limit' => 50, 'null' => true, 'default' => null])
              ->addColumn('checksum', 'text', ['null' => true, 'default' => null])
              ->addIndex(['id', 'language'], ['unique' => true])
              ->addIndex(['language'])
              ->addIndex(['modulo'])
              ->addIndex(['tipo'])
              ->create();
    }
}