<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsEscalasPesosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_escalas_pesos', ['id' => false, 'primary_key' => ['id_hosts_escalas_pesos']]);
        $table->addColumn('id_hosts_escalas_pesos', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true])
              ->addColumn('peso', 'integer', ['null' => true])
              ->create();
    }
}