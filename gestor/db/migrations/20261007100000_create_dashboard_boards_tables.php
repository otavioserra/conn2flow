<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

/**
 * REQ-251: lousas nomeadas da área de widgets do Dashboard e o histórico de versões delas.
 * As colunas `id`, `name`, `language` e `status` seguem o formato das tabelas de registros de widget.
 */
final class CreateDashboardBoardsTables extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('dashboard_boards')) {
            $this->table('dashboard_boards', ['id' => 'id_dashboard_boards'])
                ->addColumn('id', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('name', 'string', ['limit' => 200, 'null' => false])
                ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
                ->addColumn('mode', 'string', ['limit' => 10, 'null' => false, 'default' => 'grade'])
                ->addColumn('layout', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('status', 'char', ['limit' => 1, 'null' => false, 'default' => 'A'])
                ->addColumn('versao', 'integer', ['null' => false, 'default' => 1])
                ->addColumn('id_usuarios', 'integer', ['null' => true])
                ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['id', 'language'], ['unique' => true])
                ->addIndex(['status'])
                ->create();
        }

        if (!$this->hasTable('dashboard_boards_versions')) {
            $this->table('dashboard_boards_versions', ['id' => 'id_dashboard_boards_versions'])
                ->addColumn('board_id', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
                ->addColumn('versao', 'integer', ['null' => false])
                ->addColumn('name', 'string', ['limit' => 200, 'null' => false])
                ->addColumn('mode', 'string', ['limit' => 10, 'null' => false, 'default' => 'grade'])
                ->addColumn('layout', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('id_usuarios', 'integer', ['null' => true])
                ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['board_id', 'language', 'versao'])
                ->create();
        }
    }
}
