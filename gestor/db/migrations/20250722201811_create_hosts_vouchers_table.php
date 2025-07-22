<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsVouchersTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_vouchers', ['id' => false, 'primary_key' => ['id_hosts_vouchers']]);
        $table->addColumn('id_hosts_vouchers', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_pedidos', 'integer', ['null' => true])
              ->addColumn('id_hosts_servicos', 'integer', ['null' => true])
              ->addColumn('id_hosts_servicos_variacoes', 'integer', ['null' => true])
              ->addColumn('codigo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('documento', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('telefone', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('status', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('jwt_bd', 'text', ['null' => true])
              ->addColumn('loteVariacao', 'boolean', ['null' => true])
              ->addColumn('data_uso', 'datetime', ['null' => true])
              ->create();
    }
}