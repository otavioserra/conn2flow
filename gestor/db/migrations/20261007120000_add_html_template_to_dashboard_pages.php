<?php
declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

/**
 * REQ-254: o HTML que o editor guarda para cada página de lousa (com o lugar da lousa e o do título),
 * como `publisher_pages.html_template`. A página publicada em `paginas.html` sai dele mais os controles.
 */
final class AddHtmlTemplateToDashboardPages extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('dashboard_pages');
        if ($table->hasColumn('html_template')) {
            return;
        }
        $table->addColumn('html_template', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])->update();
    }
}
