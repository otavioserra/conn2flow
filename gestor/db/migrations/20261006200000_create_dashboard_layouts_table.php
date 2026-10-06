<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

/**
 * REQ-247: layout da área de widgets do Dashboard publicado por perfil de usuário.
 * `perfil` guarda o identificador do perfil, ou `*` para o layout de todos os perfis.
 */
final class CreateDashboardLayoutsTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('dashboard_layouts')) {
            return;
        }

        $this->table('dashboard_layouts', ['id' => 'id_dashboard_layouts'])
            ->addColumn('perfil', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('layout', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
            ->addColumn('id_usuarios', 'integer', ['null' => true])
            ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['perfil'], ['unique' => true])
            ->create();
    }
}
