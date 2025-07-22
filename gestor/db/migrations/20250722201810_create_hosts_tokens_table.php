<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsTokensTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_tokens', ['id' => false, 'primary_key' => ['id_hosts_tokens']]);
        $table->addColumn('id_hosts_tokens', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('pubID', 'string', ['null' => true, 'limit' => 150])
              ->addColumn('expiration', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->create();
    }
}