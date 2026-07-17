<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

/**
 * BATCH-088 (req-088): tabelas dos novos módulos de busca de páginas.
 *
 * - forms_search             espelha `forms` (com html/css/css_compiled/html_extra_head já
 *                            incorporados), usada pelo módulo/widget de formulário de busca.
 * - forms_search_submissions espelha `forms_submissions`, log dos termos pesquisados (analytics).
 * - pages_index              espelha `publisher_index`, porém SEM a coluna `publisher_id` — o
 *                            módulo consulta diretamente a tabela `paginas`.
 */
final class CreateFormsSearchAndPagesIndexTables extends AbstractMigration
{
    public function up(): void
    {
        // ===== forms_search (espelha forms)
        if (!$this->hasTable('forms_search')) {
            $table = $this->table('forms_search', ['id' => 'id_forms_search']);
            $table->addColumn('id_users', 'integer', ['null' => true, 'default' => 1])
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('id', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('template_id', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('fields_schema', 'json', ['null' => true])
                ->addColumn('module', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addColumn('plugin', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
                ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
                ->addColumn('version', 'integer', ['null' => true, 'default' => 1])
                ->addColumn('html', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('css', 'text', ['null' => true])
                ->addColumn('css_compiled', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('html_extra_head', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
                // Hybrid system fields
                ->addColumn('user_modified', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0])
                ->addColumn('system_updated', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0])
                ->addColumn('project', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addIndex(['id', 'language'], ['unique' => true])
                ->addIndex(['plugin'])
                ->addIndex(['language'])
                ->create();
        }

        // ===== forms_search_submissions (espelha forms_submissions — log de buscas)
        if (!$this->hasTable('forms_search_submissions')) {
            $table = $this->table('forms_search_submissions', ['id' => 'id_forms_search_submissions']);
            $table->addColumn('form_id', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('id', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('fields_values', 'json', ['null' => true])
                ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
                ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
                ->addColumn('version', 'integer', ['null' => true, 'default' => 1])
                ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
                ->addIndex(['id', 'language'], ['unique' => true])
                ->addIndex(['form_id'])
                ->addIndex(['language'])
                ->create();
        }

        // ===== pages_index (espelha publisher_index, SEM publisher_id)
        if (!$this->hasTable('pages_index')) {
            $table = $this->table('pages_index', ['id' => 'id_pages_index']);
            $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => 1])
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('id', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('fields_schema', 'json', ['null' => true])
                ->addColumn('html', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('css', 'text', ['null' => true])
                ->addColumn('css_compiled', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('html_extra_head', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('plugin', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
                ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
                ->addColumn('versao', 'integer', ['null' => true, 'default' => 1])
                ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
                // Hybrid system fields
                ->addColumn('user_modified', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0])
                ->addColumn('system_updated', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0])
                ->addColumn('project', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addIndex(['id', 'language'], ['unique' => true])
                ->addIndex(['plugin'])
                ->addIndex(['language'])
                ->create();
        }
    }

    public function down(): void
    {
        foreach (['pages_index', 'forms_search_submissions', 'forms_search'] as $tabela) {
            if ($this->hasTable($tabela)) {
                $this->table($tabela)->drop()->save();
            }
        }
    }
}
