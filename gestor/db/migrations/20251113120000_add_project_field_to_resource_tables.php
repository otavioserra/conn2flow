<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProjectFieldToResourceTables extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // Adicionar campo 'project' VARCHAR(255) NULL às tabelas especificadas
        $tables = ['componentes', 'layouts', 'paginas', 'variaveis', 'templates'];

        foreach ($tables as $tableName) {
            $table = $this->table($tableName);
            $table->addColumn('project', 'string', ['limit' => 255, 'null' => true])
                  ->update();
        }
    }
}