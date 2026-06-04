<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMenusTable extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de menus do site (req-015). Análoga a publisher_highlights, porém SEM a
        // coluna publisher_id: menus são livres de publicadores — seus itens curados em
        // fields_schema->selected_items referenciam diretamente slugs de páginas do site.
        $table = $this->table('menus', ['id' => 'id_menus']);
        $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => 1])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('fields_schema', 'json', ['null' => true])
            // Template HTML editável do widget: contém a estrutura de repetição de itens
            // com placeholders @[[item#campo]]@ e delimitadores <!-- item < --> ... <!-- item > -->.
            ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
            // CSS customizado para estilização do widget de menu.
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
