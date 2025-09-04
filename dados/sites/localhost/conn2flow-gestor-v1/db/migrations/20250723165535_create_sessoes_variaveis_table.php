<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSessoesVariaveisTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('sessoes_variaveis', ['id' => 'id_sessoes_variaveis']);
        $table->addColumn('id_sessoes', 'integer', ['null' => true, 'default' => null])
              ->addColumn('variavel', 'text', ['null' => true])
              ->addColumn('valor', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->create();
    }
}