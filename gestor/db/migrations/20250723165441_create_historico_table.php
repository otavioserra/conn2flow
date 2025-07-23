<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHistoricoTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('historico', ['id' => 'id_historico']);
        $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('modulo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'integer', ['null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('campo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('opcao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('filtro', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('alteracao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('alteracao_txt', 'text', ['null' => true])
              ->addColumn('valor_antes', 'text', ['null' => true])
              ->addColumn('valor_depois', 'text', ['null' => true])
              ->addColumn('tabela', 'text', ['null' => true])
              ->addColumn('data', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('controlador', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->create();
    }
}