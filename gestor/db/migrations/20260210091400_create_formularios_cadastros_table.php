<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateFormulariosCadastrosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('formularios_cadastros', ['id' => 'id_formularios_cadastros']);
        $table->addColumn('formulario_id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('cadastro_id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
            ->addColumn('fields_values', 'json', ['null' => true])
            ->addColumn('html_template', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
            // Hybrid system fields
            ->addIndex(['formulario_id', 'language'], ['unique' => true])
            ->addIndex(['cadastro_id'])
            ->addIndex(['language'])
            ->create();
    }
}