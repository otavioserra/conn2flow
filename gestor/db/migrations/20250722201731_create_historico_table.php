<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHistoricoTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('historico', ['id' => false, 'primary_key' => ['id_historico']]);
        $table->addColumn('id_historico', 'integer')
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true])
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('modulo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'integer', ['null' => true])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('campo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('opcao', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('filtro', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('alteracao', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('alteracao_txt', 'text', ['null' => true])
              ->addColumn('valor_antes', 'text', ['null' => true])
              ->addColumn('valor_depois', 'text', ['null' => true])
              ->addColumn('tabela', 'text', ['null' => true])
              ->addColumn('data', 'datetime', ['null' => true])
              ->addColumn('controlador', 'string', ['null' => true, 'limit' => 255])
              ->create();
    }
}