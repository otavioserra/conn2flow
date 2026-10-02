<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDistributedExchangesTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('distributed_exchanges', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 64])
            ->addColumn('kind', 'string', ['limit' => 32])
            ->addColumn('payload', 'text')
            ->addColumn('expires_at', 'biginteger')
            ->addColumn('consumed', 'boolean', ['default' => false])
            ->addIndex(['expires_at'])
            ->create();
    }
}
