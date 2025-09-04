<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePaginas301Table extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('paginas_301', ['id' => 'id_paginas_301']);
        $table->addColumn('id_paginas', 'integer', ['null' => true, 'default' => null])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->create();
    }
}