<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsVariaveisTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_variaveis', ['id' => false, 'primary_key' => ['id_hosts_variaveis']]);
        $table->addColumn('id_hosts_variaveis', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('linguagem_codigo', 'string', ['null' => true, 'limit' => 10])
              ->addColumn('modulo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('valor', 'text', ['null' => true])
              ->addColumn('tipo', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('grupo', 'string', ['null' => true, 'limit' => 255])
              ->create();
    }
}