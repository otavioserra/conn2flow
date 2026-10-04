<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddOrdemToUsuariosTopbarFavoritos extends AbstractMigration
{
    public function up(): void
    {
        $this->table('usuarios_topbar_favoritos')
            ->addColumn('ordem', 'integer', ['null' => false, 'default' => 0])
            ->update();

        $this->execute('UPDATE usuarios_topbar_favoritos SET ordem = id_usuarios_topbar_favoritos');
    }

    public function down(): void
    {
        $this->table('usuarios_topbar_favoritos')->removeColumn('ordem')->update();
    }
}
