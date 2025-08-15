<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

/**
 * Cria tabela manager_updates para registrar metadados de execuções de atualização do gestor.
 * Campos:
 *  - id_manager_updates (PK auto)
 *  - db_checksum (MEDIUMTEXT) hash/concat de checksums do estado de dados/migrações
 *  - backup_path (TEXT) caminho do diretório de backup associado
 *  - version (TEXT) versão do gestor quando executado
 *  - date (TIMESTAMP) data/hora da execução (preenchido pela aplicação ou manual)
 *
 * down(): remove a tabela.
 */
final class CreateManagerUpdatesTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('manager_updates')) {
            return; // idempotente
        }
        $table = $this->table('manager_updates', ['id' => 'id_manager_updates']);
        $table->addColumn('db_checksum', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true, 'default' => null])
              ->addColumn('backup_path', 'text', ['null' => true, 'default' => null])
              ->addColumn('version', 'text', ['null' => true, 'default' => null])
              ->addColumn('date', 'timestamp', ['null' => true, 'default' => null])
              ->addIndex(['date'], ['name' => 'idx_manager_updates_date'])
              ->create();
    }
}
