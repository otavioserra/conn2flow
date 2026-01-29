<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePublisherPagesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('publisher_pages', ['id' => 'id_publisher_pages']);
        $table->addColumn('page_id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('publisher_id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
            ->addColumn('fields_values', 'json', ['null' => true])
            ->addColumn('html_template', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
            // Hybrid system fields
            ->addIndex(['page_id', 'language'], ['unique' => true])
            ->addIndex(['publisher_id'])
            ->addIndex(['language'])
            ->create();
    }
}
