<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateGalleriesTable extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de galerias de imagens (req-018 / DEC-026). Clonada da estrutura de
        // publisher_highlights/menus, porém SEM a coluna publisher_id: galerias são livres de
        // publicadores. A curadoria de imagens vive em fields_schema->selected_items
        // (lista ordenada de objetos {id, caminho, imgSrc, nome, legenda}).
        $table = $this->table('galleries', ['id' => 'id_galleries']);
        $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => 1])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('fields_schema', 'json', ['null' => true])
            // Template HTML editável do widget: contém a estrutura de repetição de imagens
            // com placeholders @[[item#campo]]@ e delimitadores <!-- item < --> ... <!-- item > -->.
            ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
            // CSS customizado para estilização do widget de galeria.
            ->addColumn('css', 'text', ['null' => true])
            ->addColumn('plugin', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
            ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
            ->addColumn('versao', 'integer', ['null' => true, 'default' => 1])
            ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
            // Hybrid system fields
            ->addColumn('user_modified', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
            ->addColumn('system_updated', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'default' => 0])
            ->addIndex(['id', 'language'], ['unique' => true])
            ->addIndex(['plugin'])
            ->addIndex(['language'])
            ->create();
    }
}
