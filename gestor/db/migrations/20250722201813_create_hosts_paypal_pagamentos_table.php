<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPaypalPagamentosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_paypal_pagamentos', ['id' => false, 'primary_key' => ['id_hosts_paypal_pagamentos']]);
        $table->addColumn('id_hosts_paypal_pagamentos', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_pedidos', 'integer', ['null' => true])
              ->addColumn('pay_id', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('final_id', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('parcelas', 'integer', ['null' => true])
              ->addColumn('pagador_primeiro_nome', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('pagador_ultimo_nome', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('pagador_email', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('pagador_telefone', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('pagador_cpf', 'string', ['null' => true, 'limit' => 30])
              ->addColumn('pagador_cnpj', 'string', ['null' => true, 'limit' => 30])
              ->addColumn('pagador_selecionou_cnpj', 'boolean', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->addColumn('live', 'boolean', ['null' => true])
              ->addColumn('status', 'string', ['null' => true, 'limit' => 100])
              ->create();
    }
}