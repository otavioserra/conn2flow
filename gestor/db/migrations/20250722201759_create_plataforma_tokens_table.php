<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePlataformaTokensTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('plataforma_tokens', ['id' => false, 'primary_key' => ['id_plataforma_tokens']]);
        $table->addColumn('id_plataforma_tokens', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('pubID', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('pubIDValidation', 'string', ['null' => true, 'limit' => 150])
              ->addColumn('expiration', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('remoto', 'boolean', ['null' => true])
              ->create();
    }
}