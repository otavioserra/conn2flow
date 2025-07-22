<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsCarrinhoServicoVariacoesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_carrinho_servico_variacoes', ['id' => false, 'primary_key' => ['id_hosts_carrinho_servico_variacoes']]);
        $table->addColumn('id_hosts_carrinho_servico_variacoes', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_carrinho', 'integer', ['null' => true])
              ->addColumn('id_hosts_servicos', 'integer', ['null' => true])
              ->addColumn('id_hosts_servicos_lotes', 'integer', ['null' => true])
              ->addColumn('id_hosts_servicos_variacoes', 'integer', ['null' => true])
              ->addColumn('id_hosts_arquivos_Imagem', 'integer', ['null' => true])
              ->addColumn('nome_servico', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('nome_lote', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('nome_variacao', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('preco', 'float', ['null' => true])
              ->addColumn('quantidade', 'integer', ['null' => true])
              ->addColumn('gratuito', 'boolean', ['null' => true])
              ->create();
    }
}