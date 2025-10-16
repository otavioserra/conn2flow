<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateServidoresIaTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('servidores_ia', ['id' => 'id_servidores_ia']);
        $table->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('tipo', 'string', ['limit' => 50, 'null' => false]) // 'gemini', 'openai', etc.
            ->addColumn('padrao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => false, 'default' => 0]) // 0=Não, 1=Sim
            ->addColumn('chave_api', 'text', ['null' => false]) // será encriptada
            ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A']) // A=Ativo, I=Inativo
            ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
            ->addIndex(['tipo'])
            ->addIndex(['status'])
            ->create();
    }
}