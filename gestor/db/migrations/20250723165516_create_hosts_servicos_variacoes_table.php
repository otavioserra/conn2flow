<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsServicosVariacoesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_servicos_variacoes', ['id' => 'id_hosts_servicos_variacoes']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_servicos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_servicos_lotes', 'integer', ['null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('preco', 'float', ['null' => true, 'default' => null])
              ->addColumn('quantidade', 'integer', ['null' => true, 'default' => null])
              ->addColumn('quantidade_carrinhos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('quantidade_pedidos_pendentes', 'integer', ['null' => true, 'default' => null])
              ->addColumn('quantidade_pedidos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('gratuito', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->create();
    }
}