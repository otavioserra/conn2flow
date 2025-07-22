<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsCarrinhoServicosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_carrinho_servicos', ['id' => false, 'primary_key' => ['id_hosts_carrinho_servicos']]);
        $table->addColumn('id_hosts_carrinho_servicos', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_carrinho', 'integer', ['null' => true])
              ->addColumn('id_hosts_servicos', 'integer', ['null' => true])
              ->addColumn('id_hosts_arquivos_Imagem', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('preco', 'float', ['null' => true])
              ->addColumn('quantidade', 'integer', ['null' => true])
              ->addColumn('gratuito', 'boolean', ['null' => true])
              ->create();
    }
}