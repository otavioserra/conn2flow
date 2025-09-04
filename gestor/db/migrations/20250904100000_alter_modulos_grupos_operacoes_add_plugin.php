<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona coluna `plugin` às tabelas modulos_grupos e modulos_operacoes
 * para rastrear origem de dados de plugins e permitir futura remoção limpa.
 *
 * Regras:
 *  - Coluna string(255) null default null adicionada após coluna lógica `id`.
 *  - Índices compostos para evitar colisão entre diferentes plugins mantendo unicidade lógica.
 *  - Idempotente: verifica se coluna já existe.
 */
final class AlterModulosGruposOperacoesAddPlugin extends AbstractMigration
{
    public function change(): void
    {
        // modulos_grupos: índice plugin+id
        if ($this->hasTable('modulos_grupos')) {
            $t = $this->table('modulos_grupos');
            if (!$t->hasColumn('plugin')) {
                // Inserir após coluna 'id' se existir, senão após 'nome'
                $after = $t->hasColumn('id') ? 'id' : ($t->hasColumn('nome') ? 'nome' : null);
                $opts = ['limit'=>255,'null'=>true,'default'=>null];
                if ($after) $opts['after'] = $after;
                $t->addColumn('plugin','string',$opts);
            }
            // índice composto plugin+id se não existir
            $idxName = 'idx_modulos_grupos_plugin_id';
            if (!$t->hasIndexByName($idxName) && $t->hasColumn('plugin') && $t->hasColumn('id')) {
                $t->addIndex(['plugin','id'], ['name'=>$idxName]);
            }
            $t->update();
        }

        // modulos_operacoes: índices plugin+operacao e plugin+id_modulos+operacao
        if ($this->hasTable('modulos_operacoes')) {
            $t = $this->table('modulos_operacoes');
            if (!$t->hasColumn('plugin')) {
                $after = $t->hasColumn('id') ? 'id' : ($t->hasColumn('nome') ? 'nome' : null);
                $opts = ['limit'=>255,'null'=>true,'default'=>null];
                if ($after) $opts['after'] = $after;
                $t->addColumn('plugin','string',$opts);
            }
            if ($t->hasColumn('plugin')) {
                if (!$t->hasIndexByName('idx_modulos_operacoes_plugin_operacao') && $t->hasColumn('operacao')) {
                    $t->addIndex(['plugin','operacao'], ['name'=>'idx_modulos_operacoes_plugin_operacao']);
                }
                if (!$t->hasIndexByName('idx_modulos_operacoes_plugin_modulo_operacao') && $t->hasColumn('id_modulos') && $t->hasColumn('operacao')) {
                    $t->addIndex(['plugin','id_modulos','operacao'], ['name'=>'idx_modulos_operacoes_plugin_modulo_operacao']);
                }
            }
            $t->update();
        }
    }
}
