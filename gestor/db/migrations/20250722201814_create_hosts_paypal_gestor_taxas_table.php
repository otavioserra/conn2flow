<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPaypalGestorTaxasTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_paypal_gestor_taxas', ['id' => false, 'primary_key' => ['id_hosts_paypal_gestor_taxas']]);
        $table->addColumn('id_hosts_paypal_gestor_taxas', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_pedidos', 'integer', ['null' => true])
              ->addColumn('pay_id', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('data', 'datetime', ['null' => true])
              ->addColumn('valor', 'float', ['null' => true])
              ->addColumn('live', 'boolean', ['null' => true])
              ->create();
    }
}