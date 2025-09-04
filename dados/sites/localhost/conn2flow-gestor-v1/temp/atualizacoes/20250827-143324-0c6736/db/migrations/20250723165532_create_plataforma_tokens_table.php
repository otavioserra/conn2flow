<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePlataformaTokensTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        $table = $this->table('plataforma_tokens', ['id' => 'id_plataforma_tokens']);
        $table->addColumn('id_hosts', 'integer', ['null' => true, 'default' => null])
              ->addColumn('pubID', 'string', ['limit' => 100, 'null' => true, 'default' => null])
              ->addColumn('pubIDValidation', 'string', ['limit' => 150, 'null' => true, 'default' => null])
              ->addColumn('expiration', 'integer', ['null' => true, 'default' => null])
              ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('remoto', 'integer', ['limit' => Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'null' => true, 'default' => null])
              ->create();
    }
}