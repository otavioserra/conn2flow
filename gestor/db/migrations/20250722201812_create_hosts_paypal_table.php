<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPaypalTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_paypal', ['id' => false, 'primary_key' => ['id_hosts_paypal']]);
        $table->addColumn('id_hosts_paypal', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('app_installed', 'boolean', ['null' => true])
              ->addColumn('app_active', 'boolean', ['null' => true])
              ->addColumn('app_live', 'boolean', ['null' => true])
              ->addColumn('app_code', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('app_secret', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('app_token', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('app_token_time', 'integer', ['null' => true])
              ->addColumn('app_expires_in', 'integer', ['null' => true])
              ->addColumn('app_webhook_id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('app_sandbox_code', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('app_sandbox_secret', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('app_sandbox_token', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('app_sandbox_token_time', 'integer', ['null' => true])
              ->addColumn('app_sandbox_expires_in', 'integer', ['null' => true])
              ->addColumn('app_sandbox_webhook_id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('reference_installed', 'boolean', ['null' => true])
              ->addColumn('reference_id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('reference_cancel_url', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('paypal_plus_inactive', 'boolean', ['null' => true])
              ->create();
    }
}