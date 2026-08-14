<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMetaSeoToPaginas extends AbstractMigration
{
    /**
     * req-112 (BATCH-112) — meta tags clássicas de SEO por página.
     *
     *  - meta_descricao: `<meta name="description">`. Complementa (não substitui) o `og_descricao`
     *    do BATCH-110: buscador lê `description`, rede social lê `og:description`, e um site pode
     *    querer textos diferentes para cada um.
     *  - meta_keywords:  `<meta name="keywords">`, lista separada por vírgula.
     *
     * Ambas caem no fallback global do `config.php` quando vazias (ver `gestor_meta_seo_dados`).
     */
    public function up(): void
    {
        if (!$this->hasTable('paginas')) {
            return;
        }

        $table = $this->table('paginas');

        if (!$table->hasColumn('meta_descricao')) {
            $table->addColumn('meta_descricao', 'text', [
                'null' => true,
                'default' => null,
                'after' => 'og_descricao',
            ]);
        }

        if (!$table->hasColumn('meta_keywords')) {
            $table->addColumn('meta_keywords', 'string', [
                'limit' => 500,
                'null' => true,
                'default' => null,
                'after' => 'meta_descricao',
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

        foreach (['meta_descricao', 'meta_keywords'] as $coluna) {
            if ($table->hasColumn($coluna)) {
                $table->removeColumn($coluna);
            }
        }

        $table->update();
    }
}
