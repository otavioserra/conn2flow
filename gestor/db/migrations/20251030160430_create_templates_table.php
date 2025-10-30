<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTemplatesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
      $table = $this->table('templates', ['id' => 'id_templates']);
      $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => 1])
        ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
        ->addColumn('id', 'string', ['limit' => 255, 'null' => false])
        ->addColumn('target', 'string', ['limit' => 255, 'null' => false])
        ->addColumn('plugin', 'string', ['limit' => 255, 'null' => true])
        ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
        ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
        ->addColumn('html_extra_head', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
        ->addColumn('css', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
        ->addColumn('css_compiled', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
        ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
        ->addColumn('versao', 'integer', ['null' => true, 'default' => 1])
        ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
        ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
        // Hybrid system fields
        ->addColumn('user_modified', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
        ->addColumn('system_updated', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
        ->addColumn('html_updated', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
        ->addColumn('css_updated', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
        ->addColumn('framework_css', 'string', ['limit' => 50, 'null' => true])
        ->addColumn('file_version', 'string', ['limit' => 50, 'null' => true, 'default' => null])
        ->addColumn('checksum', 'text', ['null' => true, 'default' => null])
        ->addIndex(['id', 'language'], ['unique' => true])
        ->addIndex(['plugin'])
        ->addIndex(['language'])
        ->create();
    }
}