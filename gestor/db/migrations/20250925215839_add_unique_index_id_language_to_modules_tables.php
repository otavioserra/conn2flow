<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona índice único (id, language) nas tabelas de módulos.
 * Permite múltiplas versões por idioma por id, mas impede duplicatas.
 */
final class AddUniqueIndexIdLanguageToModulesTables extends AbstractMigration
{
    public function up(): void
    {
        // Adicionar unique index em (id, language) para tabela modulos
        $table = $this->table('modulos');
        if (!$table->hasIndex(['id', 'language'])) {
            $table->addIndex(['id', 'language'], ['unique' => true])->save();
        }

        // Adicionar unique index em (id, language) para tabela modulos_grupos
        $table = $this->table('modulos_grupos');
        if (!$table->hasIndex(['id', 'language'])) {
            $table->addIndex(['id', 'language'], ['unique' => true])->save();
        }

        // Adicionar unique index em (id, language) para tabela modulos_operacoes
        $table = $this->table('modulos_operacoes');
        if (!$table->hasIndex(['id', 'language'])) {
            $table->addIndex(['id', 'language'], ['unique' => true])->save();
        }

        // Adicionar unique index em (id, language) para tabela usuarios_perfis
        $table = $this->table('usuarios_perfis');
        if (!$table->hasIndex(['id', 'language'])) {
            $table->addIndex(['id', 'language'], ['unique' => true])->save();
        }
    }

    public function down(): void
    {
        // Remover unique index em (id, language) para tabela modulos
        $table = $this->table('modulos');
        if ($table->hasIndex(['id', 'language'])) {
            $table->removeIndex(['id', 'language'])->save();
        }

        // Remover unique index em (id, language) para tabela modulos_grupos
        $table = $this->table('modulos_grupos');
        if ($table->hasIndex(['id', 'language'])) {
            $table->removeIndex(['id', 'language'])->save();
        }

        // Remover unique index em (id, language) para tabela modulos_operacoes
        $table = $this->table('modulos_operacoes');
        if ($table->hasIndex(['id', 'language'])) {
            $table->removeIndex(['id', 'language'])->save();
        }

        // Remover unique index em (id, language) para tabela usuarios_perfis
        $table = $this->table('usuarios_perfis');
        if ($table->hasIndex(['id', 'language'])) {
            $table->removeIndex(['id', 'language'])->save();
        }
    }
}
