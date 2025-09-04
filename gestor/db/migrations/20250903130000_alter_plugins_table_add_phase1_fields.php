<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterPluginsTableAddPhase1Fields extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('plugins')) {
            return; // tabela ainda não criada
        }

        $table = $this->table('plugins');

        // Adiciona colunas somente se não existirem (idempotência)
        if (!$table->hasColumn('origem_tipo')) {
            $table->addColumn('origem_tipo', 'string', ['limit' => 50, 'null' => true, 'default' => null]);
        }
        if (!$table->hasColumn('origem_referencia')) {
            $table->addColumn('origem_referencia', 'string', ['limit' => 255, 'null' => true, 'default' => null]);
        }
        if (!$table->hasColumn('origem_branch_tag')) {
            $table->addColumn('origem_branch_tag', 'string', ['limit' => 255, 'null' => true, 'default' => null]);
        }
        if (!$table->hasColumn('origem_credencial_ref')) {
            $table->addColumn('origem_credencial_ref', 'string', ['limit' => 255, 'null' => true, 'default' => null]);
        }
        if (!$table->hasColumn('versao_instalada')) {
            $table->addColumn('versao_instalada', 'string', ['limit' => 50, 'null' => true, 'default' => null]);
        }
        if (!$table->hasColumn('checksum_pacote')) {
            $table->addColumn('checksum_pacote', 'string', ['limit' => 128, 'null' => true, 'default' => null]);
        }
        if (!$table->hasColumn('manifest_json')) {
            $table->addColumn('manifest_json', 'text', ['null' => true, 'default' => null]);
        }
        if (!$table->hasColumn('status_execucao')) {
            $table->addColumn('status_execucao', 'string', ['limit' => 30, 'null' => false, 'default' => 'idle']);
        }
        if (!$table->hasColumn('data_instalacao')) {
            $table->addColumn('data_instalacao', 'datetime', ['null' => true, 'default' => null]);
        }
        if (!$table->hasColumn('data_ultima_atualizacao')) {
            $table->addColumn('data_ultima_atualizacao', 'datetime', ['null' => true, 'default' => null]);
        }

        // Índices básicos
        if (!$table->hasIndex(['id'])) {
            $table->addIndex(['id'], ['unique' => false]);
        }
        if (!$table->hasIndex(['origem_tipo'])) {
            $table->addIndex(['origem_tipo']);
        }

        $table->update();
    }
}
