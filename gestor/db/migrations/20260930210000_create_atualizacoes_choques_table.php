<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Registro de choques das entregas — req-198 / BATCH-202 (BL-028 B).
 *
 * Uma linha por choque: a atualização do core encontrou um arquivo sobreposto por camada superior
 * (`sobreposto`) ou editado no servidor (`editado`), ou não pôde retirar um arquivo editado
 * (`retirado-editado`), ou faltou a original do core para restaurar (`original-ausente`). A versão
 * nova fica em `copia` (sob `backups/overrides/`) e o diff textual em `diff`. `resolucao` fica para a
 * decisão humana (req-199: sobrescrever, manter ou mesclar).
 */
final class CreateAtualizacoesChoquesTable extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('atualizacoes_choques')) return;
        $this->table('atualizacoes_choques', ['id' => 'id_atualizacoes_choques'])
            ->addColumn('execucao', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Id da execução (atualizacoes_execucoes) ou do deploy'])
            ->addColumn('origem', 'string', ['limit' => 40, 'null' => false, 'comment' => 'update-system | api-project-update'])
            ->addColumn('camada', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Camada que entregava: core | plugin:<id> | projeto'])
            ->addColumn('versao', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('caminho', 'string', ['limit' => 500, 'null' => false])
            ->addColumn('tipo', 'string', ['limit' => 20, 'null' => false, 'default' => 'arquivo'])
            ->addColumn('motivo', 'string', ['limit' => 40, 'null' => false])
            ->addColumn('camada_dona', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Camada que sobrepõe (quando sobreposto)'])
            ->addColumn('hash_disco', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('hash_novo', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('copia', 'string', ['limit' => 600, 'null' => true, 'comment' => 'Versão nova guardada (relativo à instalação)'])
            ->addColumn('diff', 'text', ['null' => true, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM])
            ->addColumn('resolucao', 'string', ['limit' => 40, 'null' => true, 'comment' => 'Pendente (null) | sobrescrever | manter | mesclar'])
            ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['caminho'], ['limit' => ['caminho' => 191]])
            ->addIndex(['resolucao'])
            ->addIndex(['data_criacao'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('atualizacoes_choques')) $this->table('atualizacoes_choques')->drop()->save();
    }
}
