<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsAgendamentosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_agendamentos', ['id' => 'id_hosts_agendamentos']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data', 'date', ['null' => true, 'default' => null])
              ->addColumn('acompanhantes', 'integer', ['null' => true, 'default' => null])
              ->addColumn('senha', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('status', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('pubID', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              ->create();
    }
}