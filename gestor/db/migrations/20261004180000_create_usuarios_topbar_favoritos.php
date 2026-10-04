<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosTopbarFavoritos extends AbstractMigration
{
    public function change(): void
    {
        // Preferência da conta, independente de idioma e de sessão. Não é uma semente de recursos.
        $this->table('usuarios_topbar_favoritos', ['id' => 'id_usuarios_topbar_favoritos'])
            ->addColumn('id_usuarios', 'integer', ['null' => false])
            ->addColumn('pagina_id', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('data_criacao', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['id_usuarios', 'pagina_id'], ['unique' => true])
            ->create();
    }
}
