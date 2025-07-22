<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsEscalasTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_escalas', ['id' => false, 'primary_key' => ['id_hosts_escalas']]);
        $table->addColumn('id_hosts_escalas', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true])
              ->addColumn('mes', 'integer', ['null' => true])
              ->addColumn('ano', 'integer', ['null' => true])
              ->addColumn('status', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('pubID', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->addColumn('data_confirmacao', 'datetime', ['null' => true])
              ->create();
    }
}