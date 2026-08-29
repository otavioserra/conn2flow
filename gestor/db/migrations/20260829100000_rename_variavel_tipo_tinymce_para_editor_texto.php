<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RenameVariavelTipoTinymceParaEditorTexto extends AbstractMigration
{
    /**
     * req-144 / BATCH-147 — o tipo de campo `tinymce` passa a se chamar `editor-texto`.
     *
     * O TinyMCE saiu do sistema no req-142 (era licenciado, carregado de `cdn.tiny.cloud` com a
     * chave de API versionada no repositório) e foi substituído pelo Quill, servido por
     * `bibliotecas/editor-texto.php`. O nome do tipo, porém, continuou sendo o do fornecedor
     * anterior: o operador escolhia "TinyMCE" na lista e recebia outro editor.
     *
     * Um tipo de campo descreve o QUE o campo é — um editor de texto rico —, não quem o fabrica.
     * Enquanto o nome do fornecedor for a chave, cada troca de editor vira ou uma mentira na
     * interface ou uma migração como esta.
     *
     * O código tolera o valor antigo (`configuracao_campo_tipo()` trata `tinymce` como alias), então
     * esta migração não é uma corrida contra o deploy — ela encerra a dívida em vez de perpetuar o
     * alias como estado permanente.
     */
    public function up(): void
    {
        if (!$this->hasTable('variaveis')) {
            return;
        }

        // 1) O tipo gravado nos campos.
        $this->execute("UPDATE variaveis SET tipo='editor-texto' WHERE tipo='tinymce'");

        // 2) O rótulo do tipo na lista de seleção. É renomeado em vez de deixado para trás porque o
        //    sync de recursos casa pela chave natural (language + id): sem o rename, o registro
        //    antigo sobreviveria como órfão e o novo entraria ao lado dele.
        $rotulos = ['pt-br' => 'Editor de texto', 'en' => 'Text editor'];

        foreach ($rotulos as $idioma => $rotulo) {
            // Por idioma: se o sync ja tiver inserido o registro novo neste idioma, renomear o
            // antigo colidiria com a chave natural (language + modulo + id).
            $existe = $this->fetchRow(
                "SELECT COUNT(*) AS total FROM variaveis"
                . " WHERE id='variable-type-editor-texto-label' AND modulo='configuracao'"
                . " AND language='" . $idioma . "'"
            );

            if ((int)($existe['total'] ?? 0) > 0) {
                continue;
            }

            $this->execute(
                "UPDATE variaveis SET id='variable-type-editor-texto-label', valor='" . $rotulo . "'"
                . " WHERE id='variable-type-tinymce-label' AND modulo='configuracao'"
                . " AND language='" . $idioma . "'"
            );
        }
    }

    /**
     * O caminho de volta existe porque o dado é do operador, não do sistema: uma instalação que
     * precise voltar a uma versão anterior do gestor não pode ficar com campos de tipo desconhecido.
     */
    public function down(): void
    {
        if (!$this->hasTable('variaveis')) {
            return;
        }

        $this->execute("UPDATE variaveis SET tipo='tinymce' WHERE tipo='editor-texto'");
        $this->execute(
            "UPDATE variaveis SET id='variable-type-tinymce-label', valor='TinyMCE'"
            . " WHERE id='variable-type-editor-texto-label' AND modulo='configuracao'"
        );
    }
}
