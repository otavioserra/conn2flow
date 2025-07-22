<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsEscalasControleTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_escalas_controle', ['id' => false, 'primary_key' => ['id_hosts_escalas_controle']]);
        $table->addColumn('id_hosts_escalas_controle', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('data', 'date', ['null' => true])
              ->addColumn('total', 'integer', ['null' => true])
              ->addColumn('status', 'string', ['null' => true, 'limit' => 255])
              ->create();
    }
}