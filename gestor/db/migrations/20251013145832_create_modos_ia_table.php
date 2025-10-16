<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateModosIaTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('modos_ia', ['id' => 'id_modos_ia']);
        $table->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('id', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
            ->addColumn('alvo', 'string', ['limit' => 50, 'null' => false]) // 'paginas', 'layouts', etc.
            ->addColumn('padrao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => false, 'default' => 0]) // 0=Não, 1=Sim
            ->addColumn('prompt', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
            ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A']) // A=Ativo, I=Inativo
            ->addColumn('versao', 'integer', ['null' => true, 'default' => 1])
            ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
            ->addColumn('file_version', 'string', ['limit' => 50, 'null' => true, 'default' => null])
            ->addColumn('checksum', 'text', ['null' => true, 'default' => null])
            ->addIndex(['id', 'language'], ['unique' => true])
            ->addIndex(['alvo'])
            ->addIndex(['padrao'])
            ->addIndex(['status'])
            ->create();
    }
}