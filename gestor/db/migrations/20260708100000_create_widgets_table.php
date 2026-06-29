<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela global `widgets` (req-066 / BATCH-066).
 *
 * Registro dinâmico dos widgets do sistema disponíveis para inserção no Editor HTML Visual.
 * Cada linha descreve uma CATEGORIA/tipo de widget (ex.: Destaques, Menus, Galerias, Índice),
 * apontando para a tabela de banco onde os registros daquele tipo estão armazenados.
 *
 * A população é feita pela esteira de Sincronização de Recursos: o recurso "widgets" é declarado
 * nos manifestos JSON dos módulos e compilado em db/data/WidgetsData.json pelo
 * atualizacao-dados-recursos.php, então sincronizado pelo atualizacoes-banco-de-dados.php
 * (tabela registrada em resources/tables_config.json, strategy natural_key [language, id]).
 *
 * A coluna `project` nasce junto (espelha 20260707100000_add_project_to_widget_tables.php) para
 * que o UPSERT de deploy de projeto não falhe com "Unknown column 'project'".
 *
 * Índice único [id, language] — mesmo padrão das demais tabelas de recurso.
 */
final class CreateWidgetsTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('widgets')) {
            return;
        }

        $table = $this->table('widgets', ['id' => 'id_widgets']);
        // Identificador lógico do widget / slug do módulo (ex.: 'publisher-highlights').
        $table->addColumn('id', 'string', ['limit' => 100, 'null' => false])
            // Rótulo amigável (label) da categoria (ex.: 'Destaques').
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            // Ícone CSS correspondente (ex.: 'star').
            ->addColumn('icon', 'string', ['limit' => 100, 'null' => false])
            // Tabela de banco onde os registros de widgets desse tipo estão armazenados.
            ->addColumn('tabela', 'string', ['limit' => 100, 'null' => false])
            // Opcional: coluna de filtro na tabela alvo quando o widget representa só um subconjunto.
            ->addColumn('coluna_where', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
            ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
            ->addColumn('versao', 'integer', ['null' => true, 'default' => 1])
            ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
            // Hybrid system fields
            ->addColumn('user_modified', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
            ->addColumn('system_updated', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
            // Integridade de deploy de projetos (marcação do projeto dono no UPSERT).
            ->addColumn('project', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['id', 'language'], ['unique' => true])
            ->addIndex(['language'])
            ->create();
    }
}
