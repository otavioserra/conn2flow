<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona colunas de autenticação de dois fatores (2FA) à tabela `usuarios` (req-030 / BATCH-030).
 *
 * Suporta 2FA via Aplicativo Autenticador (TOTP / Google Authenticator) e via código por e-mail.
 * Os guards hasTable()/hasColumn() tornam a operação idempotente em ambientes já existentes.
 */
final class AddTwoFactorToUsuariosTable extends AbstractMigration
{
    public function up(): void
    {
        if(!$this->hasTable('usuarios')) return;

        $table = $this->table('usuarios');
        $alterou = false;

        if(!$table->hasColumn('two_factor_secret')){
            $table->addColumn('two_factor_secret', 'string', ['limit' => 80, 'null' => true, 'default' => null, 'after' => 'senha']);
            $alterou = true;
        }

        if(!$table->hasColumn('two_factor_enabled')){
            $table->addColumn('two_factor_enabled', 'boolean', ['null' => true, 'default' => false, 'after' => 'two_factor_secret']);
            $alterou = true;
        }

        if(!$table->hasColumn('two_factor_type')){
            $table->addColumn('two_factor_type', 'string', ['limit' => 20, 'null' => true, 'default' => null, 'after' => 'two_factor_enabled']);
            $alterou = true;
        }

        if(!$table->hasColumn('two_factor_email_code')){
            $table->addColumn('two_factor_email_code', 'string', ['limit' => 10, 'null' => true, 'default' => null, 'after' => 'two_factor_type']);
            $alterou = true;
        }

        if(!$table->hasColumn('two_factor_email_expire')){
            $table->addColumn('two_factor_email_expire', 'datetime', ['null' => true, 'default' => null, 'after' => 'two_factor_email_code']);
            $alterou = true;
        }

        if($alterou) $table->update();
    }

    public function down(): void
    {
        if(!$this->hasTable('usuarios')) return;

        $table = $this->table('usuarios');
        $alterou = false;

        foreach(['two_factor_email_expire', 'two_factor_email_code', 'two_factor_type', 'two_factor_enabled', 'two_factor_secret'] as $coluna){
            if($table->hasColumn($coluna)){
                $table->removeColumn($coluna);
                $alterou = true;
            }
        }

        if($alterou) $table->update();
    }
}
