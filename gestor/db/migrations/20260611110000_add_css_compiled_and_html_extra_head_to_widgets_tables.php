<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona as colunas css_compiled e html_extra_head às tabelas dos módulos de widget
 * (publisher_highlights, menus, galleries) em bancos de dados já existentes (req-028 / DEC-041).
 *
 * As migrações originais de criação dessas tabelas (20260701xxxxxx) já incluem essas
 * colunas para instalações limpas; esta migração cobre ambientes em execução criados antes
 * da alteração. Os guards hasTable()/hasColumn() tornam a operação idempotente e segura
 * independentemente da ordem em que o Phinx aplicar as migrações.
 */
final class AddCssCompiledAndHtmlExtraHeadToWidgetsTables extends AbstractMigration
{
    private array $tabelas = ['publisher_highlights', 'menus', 'galleries'];

    public function up(): void
    {
        foreach($this->tabelas as $nome){
            if(!$this->hasTable($nome)) continue;

            $table = $this->table($nome);
            $alterou = false;

            if(!$table->hasColumn('css_compiled')){
                $table->addColumn('css_compiled', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true, 'default' => null, 'after' => 'css']);
                $alterou = true;
            }

            if(!$table->hasColumn('html_extra_head')){
                $table->addColumn('html_extra_head', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true, 'default' => null, 'after' => 'css_compiled']);
                $alterou = true;
            }

            if($alterou) $table->update();
        }
    }

    public function down(): void
    {
        foreach($this->tabelas as $nome){
            if(!$this->hasTable($nome)) continue;

            $table = $this->table($nome);
            $alterou = false;

            if($table->hasColumn('html_extra_head')){
                $table->removeColumn('html_extra_head');
                $alterou = true;
            }

            if($table->hasColumn('css_compiled')){
                $table->removeColumn('css_compiled');
                $alterou = true;
            }

            if($alterou) $table->update();
        }
    }
}
