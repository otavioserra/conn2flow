<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSeoMetadataToPaginas extends AbstractMigration
{
    /**
     * req-110 (BATCH-110) — metadados de compartilhamento social por página.
     *
     *  - imagem_destaque: caminho relativo do arquivo escolhido no gerenciador (`og:image`).
     *                     Guardamos o CAMINHO, e não o id numérico, pelo mesmo motivo do BATCH-090:
     *                     a árvore de arquivos é física e o caminho é o identificador estável.
     *  - og_titulo:       título para redes sociais; vazio cai no nome da página.
     *  - og_descricao:    descrição para redes sociais; vazio cai na descrição global do site.
     *
     * O consumo dessas colunas é o `$_GESTOR['pagina#og']` que o BATCH-109 já lê em
     * `gestor_open_graph_dados()`.
     */
    public function up(): void
    {
        if (!$this->hasTable('paginas')) {
            return;
        }

        $table = $this->table('paginas');

        if (!$table->hasColumn('imagem_destaque')) {
            $table->addColumn('imagem_destaque', 'string', [
                'limit' => 500,
                'null' => true,
                'default' => null,
                'after' => 'framework_css',
            ]);
        }

        if (!$table->hasColumn('og_titulo')) {
            $table->addColumn('og_titulo', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'after' => 'imagem_destaque',
            ]);
        }

        if (!$table->hasColumn('og_descricao')) {
            $table->addColumn('og_descricao', 'text', [
                'null' => true,
                'default' => null,
                'after' => 'og_titulo',
            ]);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('paginas')) {
            return;
        }

        $table = $this->table('paginas');

        foreach (['imagem_destaque', 'og_titulo', 'og_descricao'] as $coluna) {
            if ($table->hasColumn($coluna)) {
                $table->removeColumn($coluna);
            }
        }

        $table->update();
    }
}
