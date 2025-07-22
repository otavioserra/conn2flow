<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsuariosTokensTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('usuarios_tokens', ['id' => false, 'primary_key' => ['id_usuarios_tokens']]);
        $table->addColumn('id_usuarios_tokens', 'integer')
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('pubID', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('pubIDValidation', 'string', ['null' => true, 'limit' => 150])
              ->addColumn('fingerprint', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('expiration', 'integer', ['null' => true])
              ->addColumn('ip', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('user_agent', 'text', ['null' => true])
              ->addColumn('origem', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('senha_incorreta_tentativas', 'integer', ['null' => true])
              ->create();
    }
}