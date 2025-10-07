<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLogsTestesIaTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('logs_testes_ia', ['id' => 'id_logs_testes_ia']);
        $table->addColumn('id_servidores_ia', 'integer', ['null' => false])
            ->addColumn('data_teste', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('sucesso', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => false, 'default' => 0])
            ->addColumn('mensagem_erro', 'text', ['null' => true])
            ->addColumn('tempo_resposta', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true]) // em segundos
            ->addIndex(['id_servidores_ia'])
            ->addIndex(['data_teste'])
            ->addIndex(['sucesso'])
            ->create();
    }
}