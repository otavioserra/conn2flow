<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

/**
 * req-226: Tabela para persistência de preferências de interface e dashboard do usuário.
 */
final class CreateUsuariosPreferenciasTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('usuarios_preferencias')) {
            return;
        }

        $this->table('usuarios_preferencias', ['id' => 'id_usuarios_preferencias'])
            ->addColumn('id_usuarios', 'integer', ['null' => false])
            ->addColumn('chave', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('valor', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
            ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['id_usuarios', 'chave'], ['unique' => true])
            ->addIndex(['id_usuarios'])
            ->create();
    }
}
