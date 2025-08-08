<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMultilingualTables extends AbstractMigration
{
    /**
     * Migrate Up: Create multilingual tables for hybrid system
     */
    public function up(): void
    {
        // Create new multilingual layouts table
        $layouts = $this->table('layouts', ['id' => 'layout_id']);
        $layouts->addColumn('user_id', 'integer', ['null' => true, 'default' => null])
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('id', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
                ->addColumn('module', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG, 'null' => true])
                ->addColumn('css', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
                ->addColumn('version', 'integer', ['null' => true, 'default' => 1])
                ->addColumn('created_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
                // Hybrid system fields
                ->addColumn('user_modified', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
                ->addColumn('file_version', 'string', ['limit' => 50, 'null' => true, 'default' => null])
                ->addColumn('checksum', 'text', ['null' => true, 'default' => null])
                ->addIndex(['id', 'language'], ['unique' => true])
                ->addIndex(['language'])
                ->addIndex(['module'])
                ->create();

        // Create new multilingual pages table
        $pages = $this->table('pages', ['id' => 'page_id']);
        $pages->addColumn('user_id', 'integer', ['null' => true, 'default' => null])
              ->addColumn('layout_id', 'integer', ['null' => true, 'default' => null])
              ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
              ->addColumn('path', 'text', ['null' => true])
              ->addColumn('type', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('module', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('option', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('root', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('no_permission', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('css', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
              ->addColumn('version', 'integer', ['null' => true, 'default' => 1])
              ->addColumn('created_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              // Hybrid system fields
              ->addColumn('user_modified', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
              ->addColumn('file_version', 'string', ['limit' => 50, 'null' => true, 'default' => null])
              ->addColumn('checksum', 'text', ['null' => true, 'default' => null])
              ->addIndex(['id', 'language'], ['unique' => true])
              ->addIndex(['language'])
              ->addIndex(['module'])
              ->addIndex(['type'])
              ->create();

        // Create new multilingual components table
        $components = $this->table('components', ['id' => 'component_id']);
        $components->addColumn('user_id', 'integer', ['null' => true, 'default' => null])
                   ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
                   ->addColumn('id', 'string', ['limit' => 255, 'null' => false])
                   ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
                   ->addColumn('module', 'string', ['limit' => 255, 'null' => true, 'default' => null])
                   ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                   ->addColumn('css', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                   ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
                   ->addColumn('version', 'integer', ['null' => true, 'default' => 1])
                   ->addColumn('created_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
                   ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
                   // Hybrid system fields
                   ->addColumn('user_modified', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
                   ->addColumn('file_version', 'string', ['limit' => 50, 'null' => true, 'default' => null])
                   ->addColumn('checksum', 'text', ['null' => true, 'default' => null])
                   ->addIndex(['id', 'language'], ['unique' => true])
                   ->addIndex(['language'])
                   ->addIndex(['module'])
                   ->create();
    }

    /**
     * Migrate Down: Drop multilingual tables
     */
    public function down(): void
    {
        $this->table('layouts')->drop()->save();
        $this->table('pages')->drop()->save();
        $this->table('components')->drop()->save();
    }
}
