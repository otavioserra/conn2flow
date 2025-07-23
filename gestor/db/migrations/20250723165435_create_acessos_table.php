<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAcessosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('acessos', ['id' => 'id_acessos']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('ip', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('quantidade', 'integer', ['null' => true, 'default' => null])
              ->addColumn('bloqueios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('tipo', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('tempo_bloqueio', 'integer', ['null' => true, 'default' => null])
              ->addColumn('tempo_modificacao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('status', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->create();
    }
}