<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsServicosVariacoesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_servicos_variacoes', ['id' => false, 'primary_key' => ['id_hosts_servicos_variacoes']]);
        $table->addColumn('id_hosts_servicos_variacoes', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('id_hosts_servicos', 'integer', ['null' => true])
              ->addColumn('id_hosts_servicos_lotes', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('preco', 'float', ['null' => true])
              ->addColumn('quantidade', 'integer', ['null' => true])
              ->addColumn('quantidade_carrinhos', 'integer', ['null' => true])
              ->addColumn('quantidade_pedidos_pendentes', 'integer', ['null' => true])
              ->addColumn('quantidade_pedidos', 'integer', ['null' => true])
              ->addColumn('gratuito', 'boolean', ['null' => true])
              ->create();
    }
}