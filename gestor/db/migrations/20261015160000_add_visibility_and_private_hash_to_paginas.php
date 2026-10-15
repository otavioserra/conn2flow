<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * req-275: visibilidade e link direto privado das páginas (`admin-paginas` e `publisher-pages`).
 *
 * `visivel` = 1 mantém tudo como estava (as páginas existentes seguem visíveis). Com 0, a página só abre
 * pelo link com `?acesso=<hash_privado>`. O hash é preenchido em PHP para cada página existente:
 * `RANDOM_BYTES` não existe no MariaDB < 10.10 (mesmo cuidado da REQ-129 do site).
 */
final class AddVisibilityAndPrivateHashToPaginas extends AbstractMigration
{
    private const INDICE = 'idx_paginas_hash_privado';

    public function up(): void
    {
        if (!$this->hasTable('paginas')) {
            return;
        }

        $tabela = $this->table('paginas');
        if (!$tabela->hasColumn('visivel')) {
            $tabela->addColumn('visivel', 'boolean', ['default' => true, 'null' => false]);
        }
        if (!$tabela->hasColumn('hash_privado')) {
            $tabela->addColumn('hash_privado', 'string', ['limit' => 64, 'null' => true]);
        }
        $tabela->update();

        $connection = $this->getAdapter()->getConnection();
        $rows = $connection->query("SELECT id_paginas FROM paginas WHERE hash_privado IS NULL OR hash_privado = ''")->fetchAll(PDO::FETCH_ASSOC);
        $update = $connection->prepare('UPDATE paginas SET hash_privado = ? WHERE id_paginas = ?');
        foreach ($rows as $row) {
            $update->execute([bin2hex(random_bytes(16)), $row['id_paginas']]);
        }

        $tabela = $this->table('paginas');
        if (!$tabela->hasIndexByName(self::INDICE)) {
            $tabela->addIndex(['hash_privado'], ['name' => self::INDICE, 'unique' => true])->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('paginas')) {
            return;
        }
        $tabela = $this->table('paginas');
        if ($tabela->hasIndexByName(self::INDICE)) {
            $tabela->removeIndexByName(self::INDICE)->update();
        }
        if ($tabela->hasColumn('hash_privado')) {
            $tabela->removeColumn('hash_privado')->update();
        }
        if ($tabela->hasColumn('visivel')) {
            $tabela->removeColumn('visivel')->update();
        }
    }
}
