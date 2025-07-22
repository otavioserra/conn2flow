<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsEscalasDatasTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_escalas_datas', ['id' => false, 'primary_key' => ['id_hosts_escalas_datas']]);
        $table->addColumn('id_hosts_escalas_datas', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_escalas', 'integer', ['null' => true])
              ->addColumn('data', 'date', ['null' => true])
              ->addColumn('status', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('selecionada', 'boolean', ['null' => true])
              ->addColumn('selecionada_inscricao', 'boolean', ['null' => true])
              ->addColumn('selecionada_confirmacao', 'boolean', ['null' => true])
              ->create();
    }
}