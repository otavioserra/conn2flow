<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProjectToFormsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('forms');
        $table->addColumn('project', 'string', ['limit' => 255, 'null' => true])
              ->update();
    }
}