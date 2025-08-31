<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona coluna framework_css na tabela layouts para identificar o framework CSS do recurso.
 * Valores previstos inicialmente: 'fomantic-ui', 'tailwindcss'. Fallback de aplicação: 'fomantic-ui'.
 */
final class AlterLayoutsAddFrameworkCss extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('layouts');
        if (!$table->hasColumn('framework_css')) {
            // Inserimos após a coluna 'css_updated' se existir, senão após 'css'
            $after = $table->hasColumn('css_updated') ? 'css_updated' : 'css';
            $table->addColumn('framework_css', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null,
                'after' => $after,
                'comment' => 'Framework CSS do recurso (ex: fomantic-ui, tailwindcss)'
            ])->save();
        }
    }

    public function down(): void
    {
        $table = $this->table('layouts');
        if ($table->hasColumn('framework_css')) {
            $table->removeColumn('framework_css')->save();
        }
    }
}
