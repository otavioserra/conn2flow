<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsCarrinhoTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_carrinho', ['id' => false, 'primary_key' => ['id_hosts_carrinho']]);
        $table->addColumn('id_hosts_carrinho', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('sessao_id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}