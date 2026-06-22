<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class AddHtmlAndCssToFormsTable extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('forms')) {
            return;
        }

        $table = $this->table('forms');

        if (!$table->hasColumn('html')) {
            $table->addColumn('html', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true]);
        }
        if (!$table->hasColumn('css')) {
            $table->addColumn('css', 'text', ['null' => true]);
        }
        if (!$table->hasColumn('css_compiled')) {
            $table->addColumn('css_compiled', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true]);
        }
        if (!$table->hasColumn('html_extra_head')) {
            $table->addColumn('html_extra_head', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true]);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('forms')) {
            return;
        }

        $table = $this->table('forms');
        foreach (['html', 'css', 'css_compiled', 'html_extra_head'] as $column) {
            if ($table->hasColumn($column)) {
                $table->removeColumn($column);
            }
        }
        $table->update();
    }
}
