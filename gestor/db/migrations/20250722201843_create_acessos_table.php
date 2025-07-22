<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAcessosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('acessos', ['id' => false, 'primary_key' => ['id_acessos']]);
        $table->addColumn('id_acessos', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('ip', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('quantidade', 'integer', ['null' => true])
              ->addColumn('bloqueios', 'integer', ['null' => true])
              ->addColumn('tipo', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('tempo_bloqueio', 'integer', ['null' => true])
              ->addColumn('tempo_modificacao', 'integer', ['null' => true])
              ->addColumn('status', 'string', ['null' => true, 'limit' => 100])
              ->create();
    }
}