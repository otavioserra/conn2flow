<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsAgendamentosDatasTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_agendamentos_datas', ['id' => false, 'primary_key' => ['id_hosts_agendamentos_datas']]);
        $table->addColumn('id_hosts_agendamentos_datas', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('data', 'date', ['null' => true])
              ->addColumn('total', 'integer', ['null' => true])
              ->addColumn('status', 'string', ['null' => true, 'limit' => 255])
              ->create();
    }
}