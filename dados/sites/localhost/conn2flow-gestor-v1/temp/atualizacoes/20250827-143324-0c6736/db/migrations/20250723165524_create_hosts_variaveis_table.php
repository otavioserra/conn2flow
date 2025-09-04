<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsVariaveisTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
    $table = $this->table('hosts_variaveis', ['id' => 'id_hosts_variaveis']);
      $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
          // Padronização: id_usuarios default 1
          ->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => 1])
              ->addColumn('linguagem_codigo', 'string', ['limit' => 10, 'null' => true, 'default' => null])
              ->addColumn('modulo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('valor', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('tipo', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('grupo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->create();
    }
}