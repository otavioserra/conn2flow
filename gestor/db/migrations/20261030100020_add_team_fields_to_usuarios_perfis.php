<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * REQ-297: chave de sub-usuários do perfil e dono dos perfis criados por um titular.
 *
 * Perfil com `id_usuarios_dono` pertence à equipe de uma conta e não aparece nas listas do sistema.
 * A coluna copia o sinal de `usuarios.id_usuarios` pelo mesmo motivo de `id_usuarios_pai`.
 */
final class AddTeamFieldsToUsuariosPerfis extends AbstractMigration
{
    private const FK = 'fk_usuarios_perfis_id_usuarios_dono';
    private const INDICE = 'idx_usuarios_perfis_id_usuarios_dono';

    public function up(): void
    {
        $chave = $this->fetchRow("SHOW COLUMNS FROM usuarios LIKE 'id_usuarios'");
        $unsigned = stripos((string)($chave['Type'] ?? ''), 'unsigned') !== false;

        $table = $this->table('usuarios_perfis');

        if (!$table->hasColumn('sub_users_active')) {
            $table->addColumn('sub_users_active', 'boolean', [
                'null' => false,
                'default' => false,
                'comment' => 'REQ-297: liga os sub-usuários das contas deste perfil',
            ])->update();
        }

        if (!$this->table('usuarios_perfis')->hasColumn('id_usuarios_dono')) {
            $this->table('usuarios_perfis')->addColumn('id_usuarios_dono', 'integer', [
                'null' => true,
                'default' => null,
                'signed' => !$unsigned,
                'comment' => 'REQ-297: titular dono do perfil de equipe; NULL = perfil do sistema',
            ])->update();
        }

        if (!$this->table('usuarios_perfis')->hasIndexByName(self::INDICE)) {
            $this->table('usuarios_perfis')->addIndex(['id_usuarios_dono'], ['name' => self::INDICE])->update();
        }

        if (!$this->table('usuarios_perfis')->hasForeignKey('id_usuarios_dono', self::FK)) {
            $this->table('usuarios_perfis')->addForeignKey('id_usuarios_dono', 'usuarios', 'id_usuarios', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => self::FK,
            ])->update();
        }
    }

    public function down(): void
    {
        if ($this->table('usuarios_perfis')->hasForeignKey('id_usuarios_dono', self::FK)) {
            $this->table('usuarios_perfis')->dropForeignKey('id_usuarios_dono', self::FK)->save();
        }

        if ($this->table('usuarios_perfis')->hasIndexByName(self::INDICE)) {
            $this->table('usuarios_perfis')->removeIndexByName(self::INDICE)->save();
        }

        foreach (['id_usuarios_dono', 'sub_users_active'] as $coluna) {
            if ($this->table('usuarios_perfis')->hasColumn($coluna)) {
                $this->table('usuarios_perfis')->removeColumn($coluna)->save();
            }
        }
    }
}
