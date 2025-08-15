<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPostagensTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_postagens', ['id' => 'id_hosts_postagens']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_paginas', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_arquivos_Imagem', 'integer', ['null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              ->addColumn('template_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('template_tipo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('descricao', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('caminho', 'text', ['null' => true])
              ->create();
    }
}