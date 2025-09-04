<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBackupCamposTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('backup_campos', ['id' => 'id_backup_campos']);
        $table->addColumn('modulo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'integer', ['null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('campo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('valor', 'text', ['null' => true])
              ->addColumn('data', 'datetime', ['null' => true, 'default' => null])
              ->create();
    }
}