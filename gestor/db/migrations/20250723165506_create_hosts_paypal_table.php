<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPaypalTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_paypal', ['id' => 'id_hosts_paypal']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('app_installed', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('app_active', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('app_live', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('app_code', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('app_secret', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('app_token', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('app_token_time', 'integer', ['null' => true, 'default' => null])
              ->addColumn('app_expires_in', 'integer', ['null' => true, 'default' => null])
              ->addColumn('app_webhook_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('app_sandbox_code', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('app_sandbox_secret', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('app_sandbox_token', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('app_sandbox_token_time', 'integer', ['null' => true, 'default' => null])
              ->addColumn('app_sandbox_expires_in', 'integer', ['null' => true, 'default' => null])
              ->addColumn('app_sandbox_webhook_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('reference_installed', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('reference_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('reference_cancel_url', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('paypal_plus_inactive', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->create();
    }
}