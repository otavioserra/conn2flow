<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsAgendamentosPesosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_agendamentos_pesos', ['id' => false, 'primary_key' => ['id_hosts_agendamentos_pesos']]);
        $table->addColumn('id_hosts_agendamentos_pesos', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true])
              ->addColumn('peso', 'integer', ['null' => true])
              ->create();
    }
}