<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFormStatusToFormsSubmissions extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('forms_submissions');
        $table->addColumn('form_status', 'string', ['limit' => 100, 'null' => false, 'default' => 'new', 'after' => 'fields_values'])
            ->addIndex(['form_status'])
            ->update();
    }
}
