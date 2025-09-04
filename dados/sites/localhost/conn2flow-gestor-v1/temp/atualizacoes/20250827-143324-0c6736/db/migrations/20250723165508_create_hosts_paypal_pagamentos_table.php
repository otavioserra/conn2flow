<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPaypalPagamentosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_paypal_pagamentos', ['id' => 'id_hosts_paypal_pagamentos']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_pedidos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('pay_id', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('final_id', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('parcelas', 'integer', ['null' => true, 'default' => null])
              ->addColumn('pagador_primeiro_nome', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('pagador_ultimo_nome', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('pagador_email', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('pagador_telefone', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('pagador_cpf', 'string', ['limit' => 30, 'null' => true, 'default' => null])
              ->addColumn('pagador_cnpj', 'string', ['limit' => 30, 'null' => true, 'default' => null])
              ->addColumn('pagador_selecionou_cnpj', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              ->addColumn('live', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('status', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->create();
    }
}