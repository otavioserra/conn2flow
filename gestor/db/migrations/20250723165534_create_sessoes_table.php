<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSessoesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('sessoes', ['id' => 'id_sessoes']);
        $table->addColumn('id', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('acesso', 'integer', ['null' => true, 'default' => null, 'signed' => false])
              ->create();
    }
}