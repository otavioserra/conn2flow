<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateArquivosCategoriasTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('arquivos_categorias', ['id' => false, 'primary_key' => ['id_arquivos_categorias']]);
        $table->addColumn('id_arquivos_categorias', 'integer')
              ->addColumn('id_arquivos', 'integer', ['null' => true])
              ->addColumn('id_categorias', 'integer', ['null' => true])
              ->create();
    }
}