<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsUsuariosPerfisModulosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_usuarios_perfis_modulos', ['id' => 'id_hosts_usuarios_perfis_modulos']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('perfil', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('modulo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->create();
    }
}