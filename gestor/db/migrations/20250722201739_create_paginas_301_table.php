<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePaginas301Table extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('paginas_301', ['id' => false, 'primary_key' => ['id_paginas_301']]);
        $table->addColumn('id_paginas_301', 'integer')
              ->addColumn('id_paginas', 'integer', ['null' => true])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->create();
    }
}