<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHtmlExtraHeadToTables extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // Adicionar campo html_extra_head na tabela paginas
        $table = $this->table('paginas');
        $table->addColumn('html_extra_head', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true, 'default' => null, 'after' => 'html'])
              ->update();

        // Adicionar campo html_extra_head na tabela componentes
        $table = $this->table('componentes');
        $table->addColumn('html_extra_head', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true, 'default' => null, 'after' => 'html'])
              ->update();
    }
}