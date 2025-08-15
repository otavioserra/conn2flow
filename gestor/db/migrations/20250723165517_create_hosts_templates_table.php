<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsTemplatesTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_templates', ['id' => 'id_hosts_templates']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_categorias_pai', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_categorias', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_arquivos_Imagem', 'integer', ['null' => true, 'default' => null])
              ->addColumn('nome', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('html', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('css', 'text', ['limit' => Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM, 'null' => true])
              ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              ->addColumn('padrao', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('ativo', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->create();
    }
}