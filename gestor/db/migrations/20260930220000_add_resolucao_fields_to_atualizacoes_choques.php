<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Resolução dos choques das entregas — req-199 / BATCH-205.
 *
 * `resolucao` (da req-198) recebe a decisão: `sobrescrever`, `manter`, `mesclar`, ou `manter-regra` quando a
 * entrega aplica uma decisão anterior. Estas colunas guardam quando e por quem.
 */
final class AddResolucaoFieldsToAtualizacoesChoques extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('atualizacoes_choques')) return;
        $t = $this->table('atualizacoes_choques');
        if (!$t->hasColumn('resolvido_em')) $t->addColumn('resolvido_em', 'datetime', ['null' => true, 'after' => 'resolucao']);
        if (!$t->hasColumn('resolvido_por')) $t->addColumn('resolvido_por', 'string', ['limit' => 150, 'null' => true, 'after' => 'resolvido_em', 'comment' => 'Usuário do painel, token da API ou "entrega" (regra)']);
        $t->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('atualizacoes_choques')) return;
        $t = $this->table('atualizacoes_choques');
        if ($t->hasColumn('resolvido_por')) $t->removeColumn('resolvido_por');
        if ($t->hasColumn('resolvido_em')) $t->removeColumn('resolvido_em');
        $t->update();
    }
}
