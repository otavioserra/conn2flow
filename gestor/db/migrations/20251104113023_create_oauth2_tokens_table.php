<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOauth2TokensTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('oauth2_tokens', ['id' => 'id_oauth2_tokens']);
        $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('pubID', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('pubIDValidation', 'string', ['limit' => 150, 'null' => true, 'default' => null])
              ->addColumn('expiration', 'integer', ['null' => true, 'default' => null])
              ->addColumn('ip', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('user_agent', 'text', ['null' => true])
              ->addColumn('tipo', 'string', ['limit' => 50, 'null' => false, 'default' => 'access'])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->create();
    }
}