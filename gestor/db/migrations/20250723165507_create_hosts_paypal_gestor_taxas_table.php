<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPaypalGestorTaxasTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_paypal_gestor_taxas', ['id' => 'id_hosts_paypal_gestor_taxas']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_pedidos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('pay_id', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('data', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('valor', 'float', ['null' => true, 'default' => null])
              ->addColumn('live', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->create();
    }
}