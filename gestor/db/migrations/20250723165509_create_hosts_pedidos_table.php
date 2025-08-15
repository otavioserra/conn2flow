<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHostsPedidosTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('hosts_pedidos', ['id' => 'id_hosts_pedidos']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id_hosts_usuarios', 'integer', ['null' => true, 'default' => null])
              ->addColumn('id', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('codigo', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('total', 'float', ['null' => true, 'default' => null])
              ->addColumn('live', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('status', 'string', ['limit' => 255, 'null' => true, 'default' => null])
              ->addColumn('versao', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])
              ->addColumn('voucher_chave', 'text', ['null' => true])
              ->addColumn('jwt_emitidos', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->addColumn('jwt_bd_expiracao', 'datetime', ['null' => true, 'default' => null])
              ->addColumn('jwt_bd_expirado', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->create();
    }
}