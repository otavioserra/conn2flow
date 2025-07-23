<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsAgendamentosPesosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_agendamentos_pesos', ['id' => 'id_hosts_agendamentos_pesos']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('peso', 'integer', ['null' => true, 'default' => null])
              ->create();
    }
}