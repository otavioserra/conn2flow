<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsConjuntoCuponsPrioridadeTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_conjunto_cupons_prioridade', ['id' => false, 'primary_key' => ['id_hosts_conjunto_cupons_prioridade']]);
        $table->addColumn('id_hosts_conjunto_cupons_prioridade', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('quantidade', 'integer', ['null' => true])
              ->addColumn('valido_de', 'date', ['null' => true])
              ->addColumn('valido_ate', 'date', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}