<?php
// Example migration for Phinx: creates a table 'example_plugin_table'
use Phinx\Migration\AbstractMigration;

class CreateExampleTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('example_plugin_table', ['id' => 'id_example_plugin_table']);
        $table
            ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
            ->create();
    }
}
