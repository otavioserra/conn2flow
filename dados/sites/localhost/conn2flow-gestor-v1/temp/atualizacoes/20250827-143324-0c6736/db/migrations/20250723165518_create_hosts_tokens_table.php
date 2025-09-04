<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsTokensTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_tokens', ['id' => 'id_hosts_tokens']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('pubID', 'string', ['limit' => 150, 'null' => true, 'default' => null])
              ->addColumn('expiration', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->create();
    }
}