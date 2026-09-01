<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela `cron_tarefas` (REQ-032 / BATCH-026).
 *
 * Registro central das rotinas automáticas do Gestor. Cada linha descreve UMA tarefa agendada,
 * com a frequência em que deve ser despachada e o callback PHP que a executa.
 *
 * Duas origens alimentam a tabela:
 *  - `modulo`: a tarefa é declarada na chave "cron" da raiz de `<modulo>.json` e compilada em
 *    db/data/CronTarefasData.json pelo atualizacao-dados-recursos.php, então sincronizada pelo
 *    atualizacoes-banco-de-dados.php (tabela registrada em resources/tables_config.json,
 *    strategy natural_key [id]).
 *  - `manual`: a tarefa é criada pelo painel /admin-cron/ e não tem contraparte em disco.
 *
 * `frequencia`, `ultimo_status` e `origem` são VARCHAR e não ENUM: a suíte de regressão roda em
 * SQLite (que não implementa ENUM) e o MariaDB de produção trata ENUM com semântica de coerção
 * silenciosa. O domínio de valores é validado na aplicação por cron_frequencias_validas() e
 * admin_cron_status_validos() — ver D-037.
 *
 * O estado operacional (`ativo`, `expressao_cron`, `parametros`) é preservado no UPSERT quando
 * `user_modified` = 1: pausar uma tarefa no painel não pode ser revertido pelo próximo
 * resources:sync (D-036).
 *
 * A coluna `project` nasce junto para que o UPSERT de deploy de projeto não falhe com
 * "Unknown column 'project'".
 */
final class CreateCronTarefasTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('cron_tarefas')) {
            return;
        }

        $table = $this->table('cron_tarefas', ['id' => 'id_cron_tarefas']);
        // Identificador lógico (slug) da tarefa, único no sistema (ex.: 'expiracao-trials').
        $table->addColumn('id', 'string', ['limit' => 100, 'null' => false])
            // Rótulo amigável exibido no painel.
            ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('descricao', 'text', ['null' => true])
            // Módulo dono da tarefa ('host-manager', 'subscriptions', 'sistema').
            ->addColumn('modulo', 'string', ['limit' => 100, 'null' => true])
            // minutario | horario | diario | mensal | customizado
            ->addColumn('frequencia', 'string', ['limit' => 20, 'null' => false, 'default' => 'diario'])
            // Expressão cron de 5 campos; obrigatória apenas quando frequencia = 'customizado'.
            ->addColumn('expressao_cron', 'string', ['limit' => 50, 'null' => true])
            // Nome da função PHP invocada pela engine.
            ->addColumn('funcao_callback', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('parametros', 'json', ['null' => true])
            ->addColumn('ativo', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 1])
            ->addColumn('ultimo_disparo', 'datetime', ['null' => true])
            ->addColumn('ultima_duracao_ms', 'integer', ['null' => true])
            // sucesso | erro | aviso
            ->addColumn('ultimo_status', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('ultimo_log', 'text', ['null' => true])
            // modulo | manual
            ->addColumn('origem', 'string', ['limit' => 20, 'null' => false, 'default' => 'modulo'])
            ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
            ->addColumn('versao', 'integer', ['null' => true, 'default' => 1])
            ->addColumn('checksum', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
            // Hybrid system fields
            ->addColumn('user_modified', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
            ->addColumn('system_updated', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
            // Integridade de deploy de projetos (marcação do projeto dono no UPSERT).
            ->addColumn('project', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['id'], ['unique' => true])
            // Índice do despacho: a engine filtra por frequencia + ativo a cada tick.
            ->addIndex(['frequencia', 'ativo'])
            ->addIndex(['modulo'])
            ->create();
    }
}
