<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSessoesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sessoes', ['id' => false, 'primary_key' => ['id_sessoes']]);
        $table->addColumn('id_sessoes', 'integer')
              ->addColumn('id', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('acesso', 'integer', ['null' => true])
              ->create();
    }
}