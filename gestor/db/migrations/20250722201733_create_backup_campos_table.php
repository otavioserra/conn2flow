<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBackupCamposTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('backup_campos', ['id' => false, 'primary_key' => ['id_backup_campos']]);
        $table->addColumn('id_backup_campos', 'integer')
              ->addColumn('modulo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'integer', ['null' => true])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('campo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('valor', 'text', ['null' => true])
              ->addColumn('data', 'datetime', ['null' => true])
              ->create();
    }
}