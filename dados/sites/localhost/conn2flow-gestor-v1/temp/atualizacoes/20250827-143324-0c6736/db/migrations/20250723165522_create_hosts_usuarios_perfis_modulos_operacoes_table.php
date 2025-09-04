<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsUsuariosPerfisModulosOperacoesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_usuarios_perfis_modulos_operacoes', ['id' => 'id_hosts_usuarios_perfis_modulos_operacoes']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('perfil', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('operacao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->create();
    }
}