<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * REQ-297: assentos de sub-usuários do perfil. 0 = sem equipe (padrão), -1 = sem limite.
 */
final class AddMaxSubUsersToUsuariosPerfis extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('usuarios_perfis');

        if (!$table->hasColumn('max_sub_users')) {
            $table->addColumn('max_sub_users', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'REQ-297: 0 = sem equipe, -1 = sem limite, N = assentos',
            ])->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('usuarios_perfis');

        if ($table->hasColumn('max_sub_users')) {
            $table->removeColumn('max_sub_users')->save();
        }
    }
}
