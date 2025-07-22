<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsMenusItensTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_menus_itens', ['id' => false, 'primary_key' => ['id_hosts_menus_itens']]);
        $table->addColumn('id_hosts_menus_itens', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('menu_id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('label', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('tipo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('url', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('inativo', 'boolean', ['null' => true])
              ->addColumn('versao', 'integer', ['null' => true])
              ->create();
    }
}