<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterModulosGruposAddMenuLabel extends AbstractMigration
{
    /**
     * req-123: Customização de Menus Administrativos (labels e ordenação de grupos).
     */
    public function up(): void
    {
        if (!$this->hasTable('modulos_grupos')) {
            return;
        }

        $table = $this->table('modulos_grupos');

        if (!$table->hasColumn('menu_label')) {
            $table->addColumn('menu_label', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'after' => 'nome',
            ]);
        }

        if (!$table->hasColumn('ordemMenu')) {
            $table->addColumn('ordemMenu', 'integer', [
                'null' => true,
                'default' => null,
                'after' => 'data_modificacao',
            ]);
        }

        $table->update();
    }

    public function down(): void
    {
    }
}
