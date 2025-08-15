<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosTokensTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('usuarios_tokens', ['id' => 'id_usuarios_tokens']);
        $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('pubID', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('pubIDValidation', 'string', ['limit' => 150, 'null' => true, 'default' => null])
              ->addColumn('fingerprint', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('expiration', 'integer', ['null' => true, 'default' => null])
              ->addColumn('ip', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('user_agent', 'text', ['null' => true])
              ->addColumn('origem', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('senha_incorreta_tentativas', 'integer', ['null' => true, 'default' => null])
              ->create();
    }
}