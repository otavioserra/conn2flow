<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPublishWindowToPaginas extends AbstractMigration
{
    /**
     * Adiciona a janela de publicação (agendamento) à tabela `paginas`:
     *  - data_publicacao_inicio: a partir de quando a página fica visível (NULL = imediato).
     *  - data_publicacao_fim: até quando fica visível (NULL = sem expiração).
     *
     * As datas de criação/modificação (`data_criacao`/`data_modificacao`) já existem e
     * passam a ser editáveis retroativamente pelo CRUD (BATCH-075 / Meta 5).
     */
    public function up(): void
    {
        if (!$this->hasTable('paginas')) {
            return;
        }

        $table = $this->table('paginas');

        if (!$table->hasColumn('data_publicacao_inicio')) {
            $table->addColumn('data_publicacao_inicio', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'status',
            ]);
        }

        if (!$table->hasColumn('data_publicacao_fim')) {
            $table->addColumn('data_publicacao_fim', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'data_publicacao_inicio',
            ]);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('paginas')) {
            return;
        }

        $table = $this->table('paginas');

        if ($table->hasColumn('data_publicacao_inicio')) {
            $table->removeColumn('data_publicacao_inicio');
        }

        if ($table->hasColumn('data_publicacao_fim')) {
            $table->removeColumn('data_publicacao_fim');
        }

        $table->update();
    }
}
