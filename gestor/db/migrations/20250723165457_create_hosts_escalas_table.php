<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsEscalasTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_escalas', ['id' => 'id_hosts_escalas']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('mes', 'integer', ['null' => true, 'default' => null])
              ->addColumn('ano', 'integer', ['null' => true, 'default' => null])
              ->addColumn('status', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('pubID', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              ->addColumn('data_confirmacao', 'datetime', ['null' => true, 'default' => null])
              ->create();
    }
}