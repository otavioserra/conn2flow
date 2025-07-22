<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePaginasTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('paginas', ['id' => false, 'primary_key' => ['id_paginas']]);
        $table->addColumn('id_paginas', 'integer')
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('id_layouts', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('caminho', 'text', ['null' => true])
              ->addColumn('tipo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('modulo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('opcao', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('raiz', 'boolean', ['null' => true])
              ->addColumn('sem_permissao', 'boolean', ['null' => true])
              ->addColumn('html', 'string', ['null' => true])
              ->addColumn('css', 'string', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->create();
    }
}