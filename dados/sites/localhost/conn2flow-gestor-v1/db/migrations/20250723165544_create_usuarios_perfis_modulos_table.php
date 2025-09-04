<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosPerfisModulosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('usuarios_perfis_modulos', ['id' => 'id_usuarios_perfis_modulos']);
        $table->addColumn('perfil', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('modulo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->create();
    }
}