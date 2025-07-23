<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateVariaveisTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('variaveis', ['id' => 'id_variaveis']);
        $table->addColumn('linguagem_codigo', 'string', ['limit' => 10, 'null' => true, 'default' => null])
              ->addColumn('modulo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('valor', 'text', ['null' => true])
              ->addColumn('tipo', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('grupo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('descricao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->create();
    }
}