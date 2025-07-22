<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateVariaveisTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('variaveis', ['id' => false, 'primary_key' => ['id_variaveis']]);
        $table->addColumn('id_variaveis', 'integer')
              ->addColumn('linguagem_codigo', 'string', ['null' => true, 'limit' => 10])
              ->addColumn('modulo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('valor', 'text', ['null' => true])
              ->addColumn('tipo', 'string', ['null' => true, 'limit' => 100])
              ->addColumn('grupo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('descricao', 'string', ['null' => true, 'limit' => 255])
              ->create();
    }
}