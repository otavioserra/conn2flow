<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTokensTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
    $table = $this->table('tokens', ['id' => 'id_tokens']);
    // Padronização: id_usuarios default 1
    $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => 1])
              ->addColumn('id', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('pubID', 'string', ['limit' => 150, 'null' => true, 'default' => null])
              ->addColumn('expiration', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->create();
    }
}