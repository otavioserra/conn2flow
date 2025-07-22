<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPedidosTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('hosts_pedidos', ['id' => false, 'primary_key' => ['id_hosts_pedidos']]);
        $table->addColumn('id_hosts_pedidos', 'integer')
              ->addColumn('id_hosts', 'integer', ['null' => true])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true])
              ->addColumn('id', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('codigo', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('total', 'float', ['null' => true])
              ->addColumn('live', 'boolean', ['null' => true])
              ->addColumn('status', 'string', ['null' => true, 'limit' => 255])
              ->addColumn('versao', 'integer', ['null' => true])
              ->addColumn('data_criacao', 'datetime', ['null' => true])
              ->addColumn('data_modificacao', 'datetime', ['null' => true])
              ->addColumn('voucher_chave', 'text', ['null' => true])
              ->addColumn('jwt_emitidos', 'boolean', ['null' => true])
              ->addColumn('jwt_bd_expiracao', 'datetime', ['null' => true])
              ->addColumn('jwt_bd_expirado', 'boolean', ['null' => true])
              ->create();
    }
}