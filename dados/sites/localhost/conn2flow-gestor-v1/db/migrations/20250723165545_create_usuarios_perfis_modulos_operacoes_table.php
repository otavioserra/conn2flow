<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosPerfisModulosOperacoesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('usuarios_perfis_modulos_operacoes', ['id' => 'id_usuarios_perfis_modulos_operacoes']);
        $table->addColumn('perfil', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('operacao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->create();
    }
}