<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateModulosOperacoesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('modulos_operacoes', ['id' => 'id_modulos_operacoes']);
        $table->addColumn('id_modulos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => '1'])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('operacao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              ->create();
    }
}