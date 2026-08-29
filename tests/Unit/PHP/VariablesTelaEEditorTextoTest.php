<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-144 / BATCH-147 — a tela de variáveis e o tipo de campo do editor de texto.
 *
 * Dois achados distintos, ambos nascidos de cópia de scaffold:
 *
 *  1. A tela `variables` edita as VARIÁVEIS de um módulo, mas oferecia os quatro botões do CRUD
 *     genérico apontando para a tabela `modulos`. Dois eram links quebrados; os outros dois
 *     desativavam e EXCLUÍAM o módulo inteiro a partir da tela de variáveis.
 *  2. O tipo de campo continuava chamado `tinymce` depois que o TinyMCE saiu do sistema: o operador
 *     escolhia o nome de um fornecedor e recebia outro editor.
 */
final class VariablesTelaEEditorTextoTest extends TestCase
{
    private static function ler(string $relativo): string
    {
        return (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativo)
        );
    }

    public function testATelaDeVariaveisNaoOfereceAcoesSobreOModulo(): void
    {
        $codigo = self::ler('modulos/variables/variables.php');

        // `excluir` e `status` agiam sobre `modulos`: excluir na tela de variáveis do
        // `usuarios-perfis` apagava o módulo `usuarios-perfis`.
        self::assertStringNotContainsString("'opcao=excluir", $codigo);
        self::assertStringNotContainsString("'opcao=status", $codigo);

        // `adicionar` e `editar` apontavam para páginas que nunca existiram.
        self::assertStringNotContainsString("/adicionar/'", $codigo);
        self::assertStringNotContainsString("/editar/?'", $codigo);
    }

    public function testATelaDeVariaveisNaoDeclaraPaginasQueNaoExistem(): void
    {
        // O manifesto declara uma única página. Se um botão voltar a apontar para `adicionar/` ou
        // `editar/` sem a página correspondente, o link nasce quebrado de novo.
        $manifesto = json_decode(self::ler('modulos/variables/variables.json'), true);

        $paths = [];
        foreach ($manifesto['resources']['pt-br']['pages'] as $pagina) {
            $paths[] = $pagina['path'];
        }

        self::assertSame(['variables/'], $paths);
    }

    public function testOTipoDeCampoNaoCarregaMaisONomeDoFornecedor(): void
    {
        $configuracao = self::ler('bibliotecas/configuracao.php');

        self::assertStringContainsString("'valor' => 'editor-texto'", $configuracao);
        self::assertStringContainsString('variable-type-editor-texto-label', $configuracao);
        self::assertStringNotContainsString("'valor' => 'tinymce'", $configuracao);
    }

    public function testOValorAntigoContinuaSendoLidoEnquantoAMigracaoNaoRodou(): void
    {
        // Entre o deploy do código e a migração há uma janela em que o banco ainda diz `tinymce`.
        // Sem o alias, a chave erra e o campo SOME da tela — pior que um erro visível.
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'configuracao.php';

        self::assertSame('editor-texto', configuracao_campo_tipo('tinymce'));
        self::assertSame('editor-texto', configuracao_campo_tipo('editor-texto'));
        self::assertSame('string', configuracao_campo_tipo('string'));
    }

    public function testOGanchoDoCampoFoiRenomeadoNosDoisLados(): void
    {
        // O recurso declara a classe e o JS a procura: renomear só um dos lados deixa o campo sem
        // editor, em silêncio.
        foreach (['pt-br', 'en'] as $lang) {
            $componente = self::ler('resources/' . $lang . '/components/configuracao-campos/configuracao-campos.html');

            self::assertStringContainsString('<!-- editor-texto < -->', $componente, $lang);
            self::assertStringContainsString('class="campo editor-texto"', $componente, $lang);
            self::assertStringNotContainsString('tinymce', $componente, $lang);
        }

        $js = self::ler('assets/configuracao/configuracao.js');

        self::assertStringContainsString("case 'editor-texto':", $js);
        self::assertStringContainsString("textarea.editor-texto", $js);
        self::assertStringNotContainsString('tinymce', $js);
    }

    public function testNaoSobraAssetDoEditorLicenciado(): void
    {
        // O TinyMCE era licenciado e carregado de `cdn.tiny.cloud`. Deixar o pacote de idioma no
        // repositório mantém viva a impressão de que ele ainda faz parte do sistema.
        self::assertDirectoryDoesNotExist(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'tinymce');

        self::assertStringNotContainsString('tinymce', self::ler('assets/asset-versions.json'));
    }
}
