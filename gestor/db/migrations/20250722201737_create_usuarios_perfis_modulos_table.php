<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosPerfisModulosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('usuarios_perfis_modulos', ['id' => false, 'primary_key' => ['id_usuarios_perfis_modulos']]);
        $table->addColumn('id_usuarios_perfis_modulos', 'integer')
              ->addColumn('perfil', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('modulo', 'string', ['null' => true, 'limit' => 255])
              ->create();
    }
}