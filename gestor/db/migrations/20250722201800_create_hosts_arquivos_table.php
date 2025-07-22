<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsArquivosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_arquivos', ['id' => false, 'primary_key' => ['id_hosts_arquivos']]);
        $table->addColumn('id_hosts_arquivos', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('tipo', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('caminho_mini', 'text', ['null' => true])
              ->addColumn('permissao', 'boolean', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}