<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * REQ-253: vínculo das páginas de lousa. A página mora em `paginas`; aqui ficam a lousa, o modelo e os
 * controles de exibição dela, como `publisher_pages` faz para as páginas de publicador.
 */
final class CreateDashboardPagesTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('dashboard_pages')) {
            return;
        }
        $this->table('dashboard_pages', ['id' => 'id_dashboard_pages'])
            ->addColumn('page_id', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
            ->addColumn('board_id', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('template_id', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('fields_schema', 'text', ['null' => true])
            ->addIndex(['page_id', 'language'], ['unique' => true])
            ->addIndex(['board_id'])
            ->create();
    }
}
