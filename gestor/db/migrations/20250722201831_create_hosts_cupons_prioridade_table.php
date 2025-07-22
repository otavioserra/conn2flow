<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsCuponsPrioridadeTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_cupons_prioridade', ['id' => false, 'primary_key' => ['id_hosts_cupons_prioridade']]);
        $table->addColumn('id_hosts_cupons_prioridade', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_conjunto_cupons_prioridade', 'integer', ['null' => true])
              ->addColumn('id_hosts_agendamentos', 'integer', ['null' => true])
              ->addColumn('codigo', 'string', ['null' => true, 'limit' => 255])
              ->create();
    }
}