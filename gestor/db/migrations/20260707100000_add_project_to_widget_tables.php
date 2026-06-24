<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona a coluna `project` VARCHAR(255) NULL às tabelas de widget
 * (menus, galleries, publisher_highlights, publisher_index).
 *
 * Essas tabelas passaram a ser sincronizadas como Recursos Globais declarativos
 * (sync_resources / tables_config.json — req-056). A rotina de UPSERT
 * (atualizacoes-banco-de-dados.php) marca os registros com o id do projeto na
 * coluna `project` quando o deploy é de projeto e a tabela tem
 * preserve_on_user_modified; sem a coluna, o deploy falha com
 * "SQLSTATE[42S22]: Unknown column 'project'".
 *
 * Espelha 20251113120000_add_project_field_to_resource_tables.php (que cobriu
 * componentes/layouts/paginas/variaveis/templates) e
 * 20260216100000_add_project_to_forms_table.php. Idempotente via hasTable/hasColumn.
 */
final class AddProjectToWidgetTables extends AbstractMigration
{
    private const TABLES = ['menus', 'galleries', 'publisher_highlights', 'publisher_index'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (!$this->hasTable($tableName)) {
                continue;
            }
            $table = $this->table($tableName);
            if (!$table->hasColumn('project')) {
                $table->addColumn('project', 'string', ['limit' => 255, 'null' => true])
                      ->update();
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (!$this->hasTable($tableName)) {
                continue;
            }
            $table = $this->table($tableName);
            if ($table->hasColumn('project')) {
                $table->removeColumn('project')
                      ->update();
            }
        }
    }
}
