<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateFormsSubmissionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('forms_submissions', ['id' => 'id_forms_submissions']);
        $table->addColumn('form_id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('fields_values', 'json', ['null' => true])
            ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
            ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
            ->addColumn('version', 'integer', ['null' => true, 'default' => 1])
            ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
            // Hybrid system fields
            ->addIndex(['id', 'language'], ['unique' => true])
            ->addIndex(['form_id'])
            ->addIndex(['language'])
            ->create();
    }
}