<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class CreateHooksTable extends AbstractMigration
{
    /**
     * Cria a tabela 'hooks' para o sistema de hooks do Conn2Flow.
     *
     * Armazena os registros de hooks (actions e filters) sincronizados
     * a partir dos arquivos JSON de módulos e do projeto.
     * 
     * A fonte de verdade é sempre o JSON do módulo/projeto.
     * Esta tabela é reconstruída a cada sincronização.
     */
    public function change(): void
    {
        if ($this->hasTable('hooks')) {
            return;
        }

        $table = $this->table('hooks', ['id' => 'id_hooks']);
        $table
            ->addColumn('modulo',           'string',   ['limit' => 255, 'null' => true,  'default' => null,  'comment' => 'ID do módulo que registrou o hook (NULL = projeto)'])
            ->addColumn('plugin',           'string',   ['limit' => 255, 'null' => true,  'default' => null,  'comment' => 'ID do plugin, se o módulo for de plugin'])
            ->addColumn('namespace',        'string',   ['limit' => 255, 'null' => false,                     'comment' => 'Namespace alvo (ex: paginas, global, *)'])
            ->addColumn('evento',           'string',   ['limit' => 255, 'null' => false,                     'comment' => 'Evento específico (ex: editar, adicionar)'])
            ->addColumn('callback',         'string',   ['limit' => 500, 'null' => false,                     'comment' => 'Nome da função PHP a ser chamada'])
            ->addColumn('tipo',             'string',   ['limit' => 10,  'null' => false,  'default' => 'action', 'comment' => 'action ou filter'])
            ->addColumn('prioridade',       'integer',  ['limit' => MysqlAdapter::INT_SMALL, 'null' => false, 'default' => 10, 'comment' => 'Ordem de execução (menor = primeiro)'])
            ->addColumn('habilitado',       'integer',  ['limit' => MysqlAdapter::INT_TINY,  'null' => true,  'default' => 1,  'comment' => '1 = ativo, NULL/0 = desativado'])
            ->addColumn('projeto',          'integer',  ['limit' => MysqlAdapter::INT_TINY,  'null' => true,  'default' => null, 'comment' => '1 = veio do project/hooks/hooks.json'])
            ->addColumn('status',           'char',     ['limit' => 1,   'null' => true,   'default' => 'A',  'comment' => 'A = ativo, I = inativo'])
            ->addColumn('data_criacao',     'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            // Índice principal de lookup (substitui cache de arquivo)
            ->addIndex(['namespace', 'evento', 'tipo', 'status', 'habilitado'], ['name' => 'idx_hooks_lookup'])
            ->addIndex(['modulo', 'status'],    ['name' => 'idx_hooks_modulo'])
            ->addIndex(['prioridade'],          ['name' => 'idx_hooks_prioridade'])
            ->addIndex(['projeto'],             ['name' => 'idx_hooks_projeto'])
            ->create();
    }
}
