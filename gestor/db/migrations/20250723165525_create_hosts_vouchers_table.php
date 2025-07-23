<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsVouchersTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_vouchers', ['id' => 'id_hosts_vouchers']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_pedidos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_servicos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_servicos_variacoes', 'integer', ['null' => true, 'default' => null])
              ->addColumn('codigo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('documento', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('telefone', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('status', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('jwt_bd', 'text', ['null' => true])
              ->addColumn('loteVariacao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('data_uso', 'datetime', ['null' => true, 'default' => null])
              ->create();
    }
}