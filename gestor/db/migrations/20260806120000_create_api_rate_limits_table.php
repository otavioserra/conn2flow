<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateApiRateLimitsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('api_rate_limits', ['id' => 'id_api_rate_limits']);
        $table->addColumn('route', 'string', ['limit' => 190, 'null' => false])
              ->addColumn('subject', 'string', ['limit' => 190, 'null' => false])
              ->addColumn('window_start', 'biginteger', ['null' => false])
              ->addColumn('request_count', 'integer', ['null' => false, 'default' => 0])
              ->addColumn('updated_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['route', 'subject', 'window_start'], [
                  'unique' => true,
                  'name' => 'uq_api_rate_limits_bucket',
              ])
              ->create();
    }
}
