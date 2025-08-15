<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPaginasTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
    $table = $this->table('hosts_paginas', ['id' => 'id_hosts_paginas']);
      $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
          // Padronização: id_usuarios default 1
          ->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => 1])
              ->addColumn('id_hosts_layouts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('tipo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('modulo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('modulo_id_registro', 'integer', ['null' => true, 'default' => null])
              ->addColumn('sem_permissao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('css', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              ->addColumn('template_padrao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('template_categoria', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('template_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('template_modificado', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('template_versao', 'integer', ['null' => true, 'default' => null])
              ->create();
    }
}