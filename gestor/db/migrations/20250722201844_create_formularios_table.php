<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateFormulariosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('formularios', ['id' => false, 'primary_key' => ['id_formularios']]);
        $table->addColumn('id_formularios', 'integer')
              ->addColumn('tipo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('email', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('telefone', 'string', ['null' => true, 'limit' => 30])
              ->addColumn('mensagem', 'text', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}