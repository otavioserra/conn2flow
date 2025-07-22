<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTokensTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('tokens', ['id' => false, 'primary_key' => ['id_tokens']]);
        $table->addColumn('id_tokens', 'integer')
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('pubID', 'string', ['null' => true, 'limit' => 150])
              ->addColumn('expiration', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->create();
    }
}