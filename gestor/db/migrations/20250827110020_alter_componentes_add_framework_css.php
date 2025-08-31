<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona coluna framework_css na tabela componentes.
 */
final class AlterComponentesAddFrameworkCss extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('componentes');
        if (!$table->hasColumn('framework_css')) {
            $after = $table->hasColumn('css_updated') ? 'css_updated' : 'css';
            $table->addColumn('framework_css', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null,
                'after' => $after,
                'comment' => 'Framework CSS do componente'
            ])->save();
        }
    }

    public function down(): void
    {
        $table = $this->table('componentes');
        if ($table->hasColumn('framework_css')) {
            $table->removeColumn('framework_css')->save();
        }
    }
}
