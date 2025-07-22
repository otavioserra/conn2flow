<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsUsuariosPerfisModulosOperacoesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_usuarios_perfis_modulos_operacoes', ['id' => false, 'primary_key' => ['id_hosts_usuarios_perfis_modulos_operacoes']]);
        $table->addColumn('id_hosts_usuarios_perfis_modulos_operacoes', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('perfil', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('operacao', 'string', ['null' => true, 'limit' => 255])
              ->create();
    }
}