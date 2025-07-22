<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsAgendamentosAcompanhantesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_agendamentos_acompanhantes', ['id' => false, 'primary_key' => ['id_hosts_agendamentos_acompanhantes']]);
        $table->addColumn('id_hosts_agendamentos_acompanhantes', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_agendamentos', 'integer', ['null' => true])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->create();
    }
}