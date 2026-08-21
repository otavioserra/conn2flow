<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterModulosAddIconeTailwind extends AbstractMigration
{
    /**
     * req-086: ícone do menu por framework CSS.
     *
     * `modulos.icone` guarda o nome do ícone no catálogo do Fomantic UI ("credit card outline",
     * "file contract"). Uma instalação com layouts Tailwind desenha o mesmo menu com outro
     * catálogo (Lucide, "credit-card", "file-text") e os nomes NÃO se traduzem entre os dois —
     * "file-text" não existe no Fomantic e some da tela sem erro nenhum.
     *
     * Com um campo só, um projeto que serve os dois tipos de layout precisava escolher qual menu
     * ficaria sem ícone. As colunas abaixo guardam o vocabulário Tailwind ao lado do legado;
     * `gestor_pagina_menu()` escolhe pelo framework da requisição e cai no campo Fomantic quando o
     * módulo ainda não declarou o par.
     */
    public function up(): void
    {
        if (!$this->hasTable('modulos')) {
            return;
        }

        $table = $this->table('modulos');

        if (!$table->hasColumn('icone_tailwind')) {
            $table->addColumn('icone_tailwind', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null,
                'after' => 'icone',
                'comment' => 'Nome do ícone no catálogo Tailwind/Lucide (menus com framework_css=tailwindcss).',
            ]);
        }

        if (!$table->hasColumn('icone2_tailwind')) {
            $table->addColumn('icone2_tailwind', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null,
                'after' => 'icone2',
                'comment' => 'Ícone secundário no catálogo Tailwind/Lucide.',
            ]);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('modulos')) {
            return;
        }

        $table = $this->table('modulos');

        if ($table->hasColumn('icone_tailwind')) {
            $table->removeColumn('icone_tailwind');
        }

        if ($table->hasColumn('icone2_tailwind')) {
            $table->removeColumn('icone2_tailwind');
        }

        $table->update();
    }
}
