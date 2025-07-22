<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsAgendamentosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_agendamentos', ['id' => false, 'primary_key' => ['id_hosts_agendamentos']]);
        $table->addColumn('id_hosts_agendamentos', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true])
              ->addColumn('data', 'date', ['null' => true])
              ->addColumn('acompanhantes', 'integer', ['null' => true])
              ->addColumn('senha', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('status', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('pubID', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}