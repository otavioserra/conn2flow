<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsArquivosCategoriasTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_arquivos_categorias', ['id' => 'id_hosts_arquivos_categorias']);
        $table->addColumn('id_hosts_arquivos', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_categorias', 'integer', ['null' => true, 'default' => null])
              ->create();
    }
}