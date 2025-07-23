<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsCarrinhoTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_carrinho', ['id' => 'id_hosts_carrinho']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('sessao_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('data_modificacao', 'datetime', ['null' => true, 'default' => null])
              ->create();
    }
}