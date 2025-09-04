<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateArquivosCategoriasTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('arquivos_categorias', ['id' => 'id_arquivos_categorias']);
        $table->addColumn('id_arquivos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_categorias', 'integer', ['null' => true, 'default' => null])
              ->create();
    }
}