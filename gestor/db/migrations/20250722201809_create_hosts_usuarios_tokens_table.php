<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsUsuariosTokensTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_usuarios_tokens', ['id' => false, 'primary_key' => ['id_hosts_usuarios_tokens']]);
        $table->addColumn('id_hosts_usuarios_tokens', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true])
              ->addColumn('pubID', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('expiration', 'integer', ['null' => true])
              ->addColumn('ip', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('user_agent', 'text', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->create();
    }
}