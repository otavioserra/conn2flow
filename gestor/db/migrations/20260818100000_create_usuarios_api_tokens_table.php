<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Personal Access Tokens e códigos de recuperação de 2FA (req-119 / BATCH-120).
 *
 * `usuarios_api_tokens` guarda apenas o HASH do token; o valor em texto puro existe uma única vez,
 * na resposta da criação. O `token_prefix` é o que permite ao usuário reconhecer a chave na
 * listagem sem que o segredo precise ser recuperável.
 *
 * O índice único em `token_hash` não é conveniência: a validação busca a linha PELO hash, e uma
 * colisão de gravação faria dois usuários compartilharem uma credencial.
 *
 * Os guards hasTable()/hasColumn() tornam a operação idempotente em ambientes já existentes.
 */
final class CreateUsuariosApiTokensTable extends AbstractMigration
{
    public function up(): void
    {
        if(!$this->hasTable('usuarios_api_tokens')){
            $table = $this->table('usuarios_api_tokens', ['id' => 'id_usuarios_api_tokens']);
            $table->addColumn('id_usuarios', 'integer', ['null' => false])
                  ->addColumn('nome', 'string', ['limit' => 120, 'null' => false])
                  ->addColumn('token_prefix', 'string', ['limit' => 32, 'null' => false])
                  ->addColumn('token_hash', 'string', ['limit' => 64, 'null' => false])
                  ->addColumn('escopos', 'text', ['null' => true, 'default' => null])
                  ->addColumn('expiracao', 'datetime', ['null' => true, 'default' => null])
                  ->addColumn('ultimo_uso', 'datetime', ['null' => true, 'default' => null])
                  ->addColumn('status', 'string', ['limit' => 1, 'null' => false, 'default' => 'A'])
                  ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                  ->addColumn('data_modificacao', 'datetime', ['null' => true, 'default' => null])
                  ->addIndex(['token_hash'], ['unique' => true, 'name' => 'idx_usuarios_api_tokens_hash'])
                  ->addIndex(['id_usuarios', 'status'], ['name' => 'idx_usuarios_api_tokens_usuario'])
                  ->create();
        }

        if(!$this->hasTable('usuarios')) return;

        $usuarios = $this->table('usuarios');

        if(!$usuarios->hasColumn('two_factor_recovery_codes')){
            // JSON de HASHES, nunca os códigos em claro: quem lê o banco não pode passar pelo 2FA.
            $usuarios->addColumn('two_factor_recovery_codes', 'text', ['null' => true, 'default' => null, 'after' => 'two_factor_type'])
                     ->update();
        }
    }

    public function down(): void
    {
        if($this->hasTable('usuarios_api_tokens')){
            $this->table('usuarios_api_tokens')->drop()->save();
        }

        if(!$this->hasTable('usuarios')) return;

        $usuarios = $this->table('usuarios');

        if($usuarios->hasColumn('two_factor_recovery_codes')){
            $usuarios->removeColumn('two_factor_recovery_codes')->update();
        }
    }
}
