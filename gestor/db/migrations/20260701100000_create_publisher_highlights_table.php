<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePublisherHighlightsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('publisher_highlights', ['id' => 'id_publisher_highlights']);
        $table->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => 1])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('id', 'string', ['limit' => 100, 'null' => false])
            // publisher_id é o slug/identificador alfanumérico do publisher associado (D-022),
            // NÃO o id_publisher numérico auto-incrementado.
            ->addColumn('publisher_id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('fields_schema', 'json', ['null' => true])
            // Template HTML editável do widget (D-023): contém a estrutura de repetição de
            // itens com placeholders @[[item#campo]]@ e delimitadores <!-- item < --> ... <!-- item > -->.
            ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
            // CSS customizado para estilização do widget de destaques.
            ->addColumn('css', 'text', ['null' => true])
            // CSS compilado (ex.: utilitários Tailwind) e HTML extra para o <head> (req-028 / DEC-041).
            ->addColumn('css_compiled', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
            ->addColumn('html_extra_head', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
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
            ->addIndex(['publisher_id'])
            ->addIndex(['plugin'])
            ->addIndex(['language'])
            ->create();
    }
}
