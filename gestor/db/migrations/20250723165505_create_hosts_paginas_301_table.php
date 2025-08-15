<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPaginas301Table extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_paginas_301', ['id' => 'id_hosts_paginas_301']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_paginas', 'integer', ['null' => true, 'default' => null])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->create();
    }
}