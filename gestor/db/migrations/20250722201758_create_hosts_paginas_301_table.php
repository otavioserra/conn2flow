<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPaginas301Table extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_paginas_301', ['id' => false, 'primary_key' => ['id_hosts_paginas_301']]);
        $table->addColumn('id_hosts_paginas_301', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_paginas', 'integer', ['null' => true])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->create();
    }
}