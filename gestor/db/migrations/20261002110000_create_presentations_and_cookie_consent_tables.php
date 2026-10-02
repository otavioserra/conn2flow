<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

/**
 * Tabelas dos widgets `presentations` (apresentações em slides) e `cookie-consent` (aviso e
 * preferências de cookies) — req-208 / BATCH-216.
 *
 * Mesma estrutura de `galleries`: o registro guarda o HTML e o CSS do widget e as opções em
 * `fields_schema`. A coluna `project` já nasce aqui, porque as duas tabelas podem ser declaradas
 * como recurso de projeto (sync_resources) e a rotina de UPSERT marca nela o dono do registro.
 */
final class CreatePresentationsAndCookieConsentTables extends AbstractMigration
{
    private const TABLES = [
        'presentations' => 'id_presentations',
        'cookie_consent' => 'id_cookie_consent',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $nome => $pk) {
            if ($this->hasTable($nome)) {
                continue;
            }

            $this->table($nome, ['id' => $pk])
                ->addColumn('id_usuarios', 'integer', ['null' => true, 'default' => 1])
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('id', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('fields_schema', 'json', ['null' => true])
                ->addColumn('html', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('css', 'text', ['null' => true])
                ->addColumn('css_compiled', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('html_extra_head', 'text', ['limit' => MysqlAdapter::TEXT_MEDIUM, 'null' => true])
                ->addColumn('plugin', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('project', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('language', 'string', ['limit' => 10, 'null' => false, 'default' => 'pt-br'])
                ->addColumn('status', 'char', ['limit' => 1, 'null' => true, 'default' => 'A'])
                ->addColumn('versao', 'integer', ['null' => true, 'default' => 1])
                ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('data_modificacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addColumn('user_modified', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0])
                ->addColumn('system_updated', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0])
                ->addIndex(['id', 'language'], ['unique' => true])
                ->addIndex(['plugin'])
                ->addIndex(['language'])
                ->create();
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $nome) {
            if ($this->hasTable($nome)) {
                $this->table($nome)->drop()->save();
            }
        }
    }
}
