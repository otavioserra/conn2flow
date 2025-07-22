<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsServicosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_servicos', ['id' => false, 'primary_key' => ['id_hosts_servicos']]);
        $table->addColumn('id_hosts_servicos', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('id_hosts_paginas', 'integer', ['null' => true])
              ->addColumn('id_hosts_arquivos_Imagem', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->addColumn('template_id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('template_tipo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('preco', 'float', ['null' => true])
              ->addColumn('quantidade', 'integer', ['null' => true])
              ->addColumn('quantidade_carrinhos', 'integer', ['null' => true])
              ->addColumn('quantidade_pedidos_pendentes', 'integer', ['null' => true])
              ->addColumn('quantidade_pedidos', 'integer', ['null' => true])
              ->addColumn('descricao', 'string', ['null' => true])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('lotesVariacoes', 'boolean', ['null' => true])
              ->addColumn('gratuito', 'boolean', ['null' => true])
              ->create();
    }
}