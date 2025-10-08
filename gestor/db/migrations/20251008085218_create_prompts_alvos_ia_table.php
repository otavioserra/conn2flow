<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePromptsAlvosIaTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('prompts_alvos_ia', ['id' => 'id_prompts_alvos_ia']);
        $table->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('id', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
            ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A']) // A=Ativo, I=Inativo
            ->addColumn('versao', 'integer', ['null' => true, 'default' => 1])
            ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
            ->addIndex(['id', 'language'], ['unique' => true])
            ->addIndex(['status'])
            ->create();
    }
}