<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPublisherIdToPaginasTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('paginas');
        $table->addColumn('publisher_id', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'id'])
              ->update();
    }
}
