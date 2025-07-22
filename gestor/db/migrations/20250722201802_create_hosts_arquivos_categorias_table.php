<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsArquivosCategoriasTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_arquivos_categorias', ['id' => false, 'primary_key' => ['id_hosts_arquivos_categorias']]);
        $table->addColumn('id_hosts_arquivos_categorias', 'integer')
              ->addColumn('id_hosts_arquivos', 'integer', ['null' => true])
              ->addColumn('id_hosts_categorias', 'integer', ['null' => true])
              ->create();
    }
}