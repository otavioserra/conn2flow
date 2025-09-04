<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsServicosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_servicos', ['id' => 'id_hosts_servicos']);
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
              ->addColumn('preco', 'float', ['null' => true, 'default' => null])
              ->addColumn('quantidade', 'integer', ['null' => true, 'default' => null])
              ->addColumn('quantidade_carrinhos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('quantidade_pedidos_pendentes', 'integer', ['null' => true, 'default' => null])
              ->addColumn('quantidade_pedidos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('descricao', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('lotesVariacoes', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('gratuito', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->create();
    }
}