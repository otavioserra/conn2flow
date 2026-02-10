<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateFormsSubmissionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('forms_submissions', ['id' => 'id_forms_submissions']);
        $table->addColumn('form_id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('submission_id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
            ->addColumn('fields_values', 'json', ['null' => true])
            // Hybrid system fields
            ->addIndex(['form_id', 'language'], ['unique' => true])
            ->addIndex(['submission_id'])
            ->addIndex(['language'])
            ->create();
    }
}