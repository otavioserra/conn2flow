<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateArquivosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
    $table = $this->table('arquivos', ['id' => 'id_arquivos']);
    // Padronização: id_usuarios default 1
    $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => 1])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('tipo', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('caminho_mini', 'text', ['null' => true])
              ->addColumn('permissao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              ->create();
    }
}