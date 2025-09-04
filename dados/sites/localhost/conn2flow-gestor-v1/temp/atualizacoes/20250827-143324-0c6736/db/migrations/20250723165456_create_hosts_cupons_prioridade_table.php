<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsCuponsPrioridadeTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_cupons_prioridade', ['id' => 'id_hosts_cupons_prioridade']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_conjunto_cupons_prioridade', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_agendamentos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('codigo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->create();
    }
}