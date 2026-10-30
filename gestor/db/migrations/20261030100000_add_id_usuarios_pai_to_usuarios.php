<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * REQ-297: titular (pai) de um sub-usuário. Nulo em toda conta que não é filha.
 *
 * A coluna copia o sinal de `usuarios.id_usuarios` em vez de fixá-lo: instalações existentes têm a
 * chave `int unsigned` e o MySQL recusa chave estrangeira entre tipos diferentes.
 */
final class AddIdUsuariosPaiToUsuarios extends AbstractMigration
{
    private const FK = 'fk_usuarios_id_usuarios_pai';

    public function up(): void
    {
        $chave = $this->fetchRow("SHOW COLUMNS FROM usuarios LIKE 'id_usuarios'");
        $unsigned = stripos((string)($chave['Type'] ?? ''), 'unsigned') !== false;

        $table = $this->table('usuarios');

        if (!$table->hasColumn('id_usuarios_pai')) {
            $table->addColumn('id_usuarios_pai', 'integer', [
                'null' => true,
                'default' => null,
                'signed' => !$unsigned,
                'after' => 'id_usuarios_perfis',
                'comment' => 'REQ-297: titular da organização; NULL = conta própria',
            ])->update();
        }

        if (!$this->hasIndex()) {
            $this->table('usuarios')->addIndex(['id_usuarios_pai'], ['name' => 'idx_usuarios_id_usuarios_pai'])->update();
        }

        if (!$this->hasForeignKey()) {
            $this->table('usuarios')->addForeignKey('id_usuarios_pai', 'usuarios', 'id_usuarios', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => self::FK,
            ])->update();
        }
    }

    public function down(): void
    {
        $table = $this->table('usuarios');

        if ($this->hasForeignKey()) {
            $table->dropForeignKey('id_usuarios_pai', self::FK)->save();
        }

        if ($this->hasIndex()) {
            $this->table('usuarios')->removeIndexByName('idx_usuarios_id_usuarios_pai')->save();
        }

        if ($this->table('usuarios')->hasColumn('id_usuarios_pai')) {
            $this->table('usuarios')->removeColumn('id_usuarios_pai')->save();
        }
    }

    private function hasIndex(): bool
    {
        return $this->table('usuarios')->hasIndexByName('idx_usuarios_id_usuarios_pai');
    }

    private function hasForeignKey(): bool
    {
        return $this->table('usuarios')->hasForeignKey('id_usuarios_pai', self::FK);
    }
}
