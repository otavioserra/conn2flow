<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSessoesVariaveisTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sessoes_variaveis', ['id' => false, 'primary_key' => ['id_sessoes_variaveis']]);
        $table->addColumn('id_sessoes_variaveis', 'integer')
              ->addColumn('id_sessoes', 'integer', ['null' => true])
              ->addColumn('variavel', 'text', ['null' => true])
              ->addColumn('valor', 'string', ['null' => true])
              ->create();
    }
}