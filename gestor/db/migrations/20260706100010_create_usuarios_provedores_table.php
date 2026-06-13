<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela `usuarios_provedores` para vínculos de login social OAuth 2.0 (Google/Meta) — req-030 / BATCH-030.
 *
 * Convenção do legado conn2flow: o relacionamento com `usuarios` é feito por coluna integer
 * (`usuario_id`) + índice, sem chave estrangeira física (nenhuma migração do projeto usa
 * addForeignKey; ver `usuarios.id_hosts`/`id_usuarios_perfis`). O cascateamento ON DELETE
 * é tratado em código no fluxo de exclusão de usuário. O índice único composto
 * (provider_name, provider_uid) evita vínculos duplicados do mesmo provedor.
 *
 * O guard hasTable() torna a operação idempotente em ambientes já existentes.
 */
final class CreateUsuariosProvedoresTable extends AbstractMigration
{
    public function up(): void
    {
        if($this->hasTable('usuarios_provedores')) return;

        $table = $this->table('usuarios_provedores', ['id' => 'id_usuarios_provedores']);
        $table->addColumn('usuario_id', 'integer', ['null' => false])
              ->addColumn('provider_name', 'string', ['limit' => 30, 'null' => false])
              ->addColumn('provider_uid', 'string', ['limit' => 100, 'null' => false])
              ->addColumn('created_at', 'datetime', ['null' => true, 'default' => null])
              ->addIndex(['usuario_id'])
              ->addIndex(['provider_name', 'provider_uid'], ['unique' => true])
              ->create();
    }

    public function down(): void
    {
        if($this->hasTable('usuarios_provedores')){
            $this->table('usuarios_provedores')->drop()->update();
        }
    }
}
