<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosGestoresHostsTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('usuarios_gestores_hosts', ['id' => 'id_usuarios_gestores_hosts']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('privilegios_admin', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->create();
    }
}