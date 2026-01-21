<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPathPrefixToPublisherTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('publisher');
        $table->addColumn('path_prefix', 'string', ['limit' => 255, 'null' => true, 'after' => 'id'])
              ->update();
    }
}