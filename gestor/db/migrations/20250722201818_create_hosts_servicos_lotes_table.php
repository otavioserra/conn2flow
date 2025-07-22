<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsServicosLotesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_servicos_lotes', ['id' => false, 'primary_key' => ['id_hosts_servicos_lotes']]);
        $table->addColumn('id_hosts_servicos_lotes', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('id_hosts_servicos', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('visibilidade', 'string', ['null' => true, 'limit' => 20])
              ->addColumn('dataInicio', 'datetime', ['null' => true])
              ->addColumn('dataFim', 'datetime', ['null' => true])
              ->create();
    }
}