<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsAgendamentosAcompanhantesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_agendamentos_acompanhantes', ['id' => 'id_hosts_agendamentos_acompanhantes']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_agendamentos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->create();
    }
}