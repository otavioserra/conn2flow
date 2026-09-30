<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProfileHomeAndPageLayoutMapping extends AbstractMigration
{
    public function change(): void
    {
        $this->table('usuarios_perfis')
            ->addColumn('pagina_inicial', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->update();

        $this->table('paginas')
            ->addColumn('layouts_users_profiles', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG, 'null' => true, 'default' => null])
            ->update();
    }
}
