<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosGestoresHostsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('usuarios_gestores_hosts', ['id' => false, 'primary_key' => ['id_usuarios_gestores_hosts']]);
        $table->addColumn('id_usuarios_gestores_hosts', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('privilegios_admin', 'boolean', ['null' => true])
              ->create();
    }
}