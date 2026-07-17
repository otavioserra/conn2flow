<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * BATCH-090 (req-090): associação de categorias por CAMINHO FÍSICO.
 *
 * Com a migração do gerenciador de arquivos para a árvore física de diretórios
 * sob `$_GESTOR['contents-path']`, os arquivos deixam de ter registro na tabela
 * `arquivos`. A relação arquivo↔categoria passa a usar o CAMINHO RELATIVO do
 * arquivo como identificador. Para indexação eficiente guarda-se também o MD5
 * do caminho (`caminho_hash`).
 *
 * A tabela legada `arquivos_categorias` (baseada em `id_arquivos`) é preservada
 * intacta para retrocompatibilidade de registros antigos.
 */
final class CreateArquivosDiscoCategoriasTable extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('arquivos_disco_categorias')) {
            $table = $this->table('arquivos_disco_categorias', ['id' => 'id_arquivos_disco_categorias']);
            $table->addColumn('caminho', 'string', ['limit' => 1024, 'null' => false])
                ->addColumn('caminho_hash', 'char', ['limit' => 32, 'null' => false])
                ->addColumn('id_categorias', 'integer', ['null' => false])
                ->addColumn('data_criacao', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['caminho_hash', 'id_categorias'], ['unique' => true, 'name' => 'uq_caminho_categoria'])
                ->addIndex(['caminho_hash'])
                ->addIndex(['id_categorias'])
                ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('arquivos_disco_categorias')) {
            $this->table('arquivos_disco_categorias')->drop()->save();
        }
    }
}
