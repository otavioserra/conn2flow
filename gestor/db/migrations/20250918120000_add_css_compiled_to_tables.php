<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCssCompiledToTables extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // Adicionar campo css_compiled à tabela paginas
        $this->table('paginas')
            ->addColumn('css_compiled', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true, 'default' => null, 'after' => 'css'])
            ->update();

        // Adicionar campo css_compiled à tabela layouts
        $this->table('layouts')
            ->addColumn('css_compiled', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true, 'default' => null, 'after' => 'css'])
            ->update();

        // Adicionar campo css_compiled à tabela componentes
        $this->table('componentes')
            ->addColumn('css_compiled', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true, 'default' => null, 'after' => 'css'])
            ->update();
    }
}