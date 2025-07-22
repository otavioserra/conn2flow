<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsConfiguracoesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_configuracoes', ['id' => false, 'primary_key' => ['id_hosts_configuracoes']]);
        $table->addColumn('id_hosts_configuracoes', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('modulo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}