<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * REQ-286: `atualizacoes_choques` em utf8mb4.
 *
 * No MariaDB do Lab a tabela nasceu em utf8 (3 bytes) e o INSERT de um choque com emoji no diff (prompts de IA,
 * textos do painel) falhava com "Incorrect string value: '\xF0\x9F...'": o choque não ia para a tabela. Converte a
 * tabela inteira (o índice de `caminho` já usa prefixo de 191, que cabe em utf8mb4).
 */
final class AlterAtualizacoesChoquesUtf8mb4 extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('atualizacoes_choques') || $this->getAdapter()->getAdapterType() !== 'mysql') return;
        $this->execute('ALTER TABLE `atualizacoes_choques` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        // Sem volta: voltar para utf8 perderia os caracteres de 4 bytes já gravados.
    }
}
