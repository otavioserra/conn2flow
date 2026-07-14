<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ExpandPublisherPagesIdentifiers extends AbstractMigration
{
    public function up(): void
    {
        $this->table('publisher_pages')
            ->changeColumn('page_id', 'string', ['limit' => 255, 'null' => false])
            ->changeColumn('publisher_id', 'string', ['limit' => 255, 'null' => false])
            ->update();
    }

    public function down(): void
    {
        throw new \Phinx\Migration\IrreversibleMigrationException(
            'Reducing publisher_pages identifiers to 100 characters can truncate imported page IDs.'
        );
    }
}
