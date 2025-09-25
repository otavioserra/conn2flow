<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLanguageFieldToModulesTables extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        // Adicionar campo language na tabela modulos
        $table = $this->table('modulos');
        $table->addColumn('language', 'string', ['limit' => 10, 'null' => true, 'default' => 'pt-br', 'after' => 'versao'])
              ->update();

        // Adicionar campo language na tabela modulos_grupos
        $table = $this->table('modulos_grupos');
        $table->addColumn('language', 'string', ['limit' => 10, 'null' => true, 'default' => 'pt-br', 'after' => 'versao'])
              ->update();

        // Adicionar campo language na tabela modulos_operacoes
        $table = $this->table('modulos_operacoes');
        $table->addColumn('language', 'string', ['limit' => 10, 'null' => true, 'default' => 'pt-br', 'after' => 'versao'])
              ->update();

        // Adicionar campo language na tabela usuarios_perfis
        $table = $this->table('usuarios_perfis');
        $table->addColumn('language', 'string', ['limit' => 10, 'null' => true, 'default' => 'pt-br', 'after' => 'versao'])
              ->update();
    }
}