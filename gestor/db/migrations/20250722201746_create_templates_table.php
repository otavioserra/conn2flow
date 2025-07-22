<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTemplatesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('templates', ['id' => false, 'primary_key' => ['id_templates']]);
        $table->addColumn('id_templates', 'integer')
              ->addColumn('id_usuarios', 'integer', ['null' => true])
              ->addColumn('id_categorias_pai', 'integer', ['null' => true])
              ->addColumn('id_categorias', 'integer', ['null' => true])
              ->addColumn('id_arquivos_Imagem', 'integer', ['null' => true])
              ->addColumn('nome', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('html', 'string', ['null' => true])
              ->addColumn('css', 'string', ['null' => true])
              ->addColumn('status', 'char', ['null' => true, 'limit' => 1])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->addColumn('padrao', 'boolean', ['null' => true])
              ->create();
    }
}