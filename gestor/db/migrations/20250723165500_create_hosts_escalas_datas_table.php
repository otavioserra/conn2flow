<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsEscalasDatasTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_escalas_datas', ['id' => 'id_hosts_escalas_datas']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_escalas', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data', 'date', ['null' => true, 'default' => null])
              ->addColumn('status', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('selecionada', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('selecionada_inscricao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('selecionada_confirmacao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->create();
    }
}