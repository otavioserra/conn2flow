<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsServicosLotesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_servicos_lotes', ['id' => 'id_hosts_servicos_lotes']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_servicos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('visibilidade', 'string', ['limit' => 20, 'null' => true, 'default' => null])
              ->addColumn('dataInicio', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('dataFim', 'datetime', ['null' => true, 'default' => null])
              ->create();
    }
}