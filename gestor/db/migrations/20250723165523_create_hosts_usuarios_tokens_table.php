<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsUsuariosTokensTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_usuarios_tokens', ['id' => 'id_hosts_usuarios_tokens']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('pubID', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('expiration', 'integer', ['null' => true, 'default' => null])
              ->addColumn('ip', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('user_agent', 'text', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true, 'default' => null])
              ->create();
    }
}