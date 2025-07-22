<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPedidosServicosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_pedidos_servicos', ['id' => false, 'primary_key' => ['id_hosts_pedidos_servicos']]);
        $table->addColumn('id_hosts_pedidos_servicos', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_pedidos', 'integer', ['null' => true])
              ->addColumn('id_hosts_servicos', 'integer', ['null' => true])
              ->addColumn('id_hosts_arquivos_Imagem', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('preco', 'float', ['null' => true])
              ->addColumn('quantidade', 'integer', ['null' => true])
              ->addColumn('gratuito', 'boolean', ['null' => true])
              ->create();
    }
}