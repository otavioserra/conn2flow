<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsAgendamentosDatasTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_agendamentos_datas', ['id' => 'id_hosts_agendamentos_datas']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data', 'date', ['null' => true, 'default' => null])
              ->addColumn('total', 'integer', ['null' => true, 'default' => null])
              ->addColumn('status', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->create();
    }
}