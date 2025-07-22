<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsEscalasCronTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_escalas_cron', ['id' => false, 'primary_key' => ['id_hosts_escalas_cron']]);
        $table->addColumn('id_hosts_escalas_cron', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('mes', 'integer', ['null' => true])
              ->addColumn('ano', 'integer', ['null' => true])
              ->addColumn('status', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}