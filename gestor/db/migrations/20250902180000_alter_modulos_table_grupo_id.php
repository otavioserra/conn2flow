<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterModulosTableGrupoId extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // Remove coluna numérica antiga
        if ($this->table('modulos')->hasColumn('id_modulos_grupos')) {
            $this->table('modulos')->removeColumn('id_modulos_grupos')->update();
        }
        // Adiciona nova coluna textual para referência
        $this->table('modulos')
            ->addColumn('modulo_grupo_id', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'id_usuarios'])
            ->update();
    }
}
