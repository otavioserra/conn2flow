<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFormsSecurityTables extends AbstractMigration
{
    public function change(): void
    {
        // Create table forms_logs
        $logsTable = $this->table('forms_logs', ['id' => 'id_forms_logs']);
        $logsTable->addColumn('form_id', 'string', ['limit' => 100, 'null' => true, 'default' => null])
                  ->addColumn('ip', 'string', ['limit' => 100, 'null' => true, 'default' => null])
                  ->addColumn('fingerprint', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                  ->addColumn('created_at', 'datetime', ['null' => true, 'default' => null])
                  ->addColumn('success', 'boolean', ['null' => true, 'default' => null])
                  ->create();

        // Create table forms_blocks
        $blocksTable = $this->table('forms_blocks', ['id' => 'id_forms_blocks']);
        $blocksTable->addColumn('ip', 'string', ['limit' => 100, 'null' => true, 'default' => null])
                    ->addColumn('fingerprint', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                    ->addColumn('unblock_at', 'datetime', ['null' => true, 'default' => null])
                    ->create();
    }
}