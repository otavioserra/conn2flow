<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsMenusItensTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_menus_itens', ['id' => 'id_hosts_menus_itens']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('menu_id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('label', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('tipo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('url', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('inativo', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->create();
    }
}