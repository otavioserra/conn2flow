<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Renomeia a coluna `linguagem_codigo` para `language` na tabela `variaveis`,
 * completando a padronização internacional do campo de idioma (DEC-008).
 *
 * Idempotente: só renomeia quando a coluna antiga existe e a nova ainda não,
 * garantindo compatibilidade reversa com bancos que já criam a tabela com
 * `language` (via migração de criação atualizada) e com bancos legados que
 * ainda possuem `linguagem_codigo`.
 *
 * O índice composto criado em AlterRecursosAddPluginId (originalmente sobre
 * `linguagem_codigo`) é preservado automaticamente pelo MySQL durante o
 * CHANGE COLUMN do rename, passando a referenciar `language`.
 */
final class RenameVariaveisLinguagemCodigoToLanguage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('variaveis')) {
            return;
        }
        $table = $this->table('variaveis');
        if ($table->hasColumn('linguagem_codigo') && !$table->hasColumn('language')) {
            $table->renameColumn('linguagem_codigo', 'language')->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('variaveis')) {
            return;
        }
        $table = $this->table('variaveis');
        if ($table->hasColumn('language') && !$table->hasColumn('linguagem_codigo')) {
            $table->renameColumn('language', 'linguagem_codigo')->update();
        }
    }
}
