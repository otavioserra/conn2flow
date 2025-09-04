<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsCarrinhoServicosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_carrinho_servicos', ['id' => 'id_hosts_carrinho_servicos']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_carrinho', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_servicos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_arquivos_Imagem', 'integer', ['null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('preco', 'float', ['null' => true, 'default' => null])
              ->addColumn('quantidade', 'integer', ['null' => true, 'default' => null])
              ->addColumn('gratuito', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->create();
    }
}