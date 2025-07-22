<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosGestoresPerfisModulosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('usuarios_gestores_perfis_modulos', ['id' => false, 'primary_key' => ['id_usuarios_gestores_perfis_modulos']]);
        $table->addColumn('id_usuarios_gestores_perfis_modulos', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('perfil', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('modulo', 'string', ['null' => true, 'limit' => 255])
              ->create();
    }
}