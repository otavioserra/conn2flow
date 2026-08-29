<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-144 (req-141 / CR-002) — CSS de conteúdo do editor Quill na página pública.
 *
 * O Quill grava a formatação em CLASSES (`ql-indent-3`, `ql-align-right`), e a página pública não
 * carregava CSS nenhum para elas: medido em `/artigos/teste-de-artigo/`, `.ql-indent-1` chegava sem
 * definição e a indentação escolhida pelo usuário desaparecia. As classes que funcionavam vinham por
 * acidente de um `css_compiled` contaminado — daí o comportamento errático ("tem hora que alinha").
 *
 * A detecção precisa ser exata nos dois sentidos: incluir o asset onde há conteúdo Quill e NÃO
 * incluí-lo no resto do site.
 */
final class QuillConteudoTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // `editor-texto.php` é o ponto único do Quill no sistema; não está entre as bibliotecas que o
        // bootstrap carrega porque só páginas com conteúdo do editor precisam dela.
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'editor-texto.php';
    }

    // ===== gestor_quill_conteudo_detectar =====

    public function testDetectaOContainerDoQuill(): void
    {
        $html = '<div class="ql-container ql-snow"><div class="ql-editor"><p>texto</p></div></div>';
        self::assertTrue(editor_texto_conteudo_detectar($html));
    }

    public function testDetectaCadaFamiliaDeFormatacao(): void
    {
        $casos = [
            'indentação' => '<p class="ql-indent-1">a</p>',
            'alinhamento' => '<p class="ql-align-right">a</p>',
            'tamanho' => '<span class="ql-size-large">a</span>',
            'fonte' => '<span class="ql-font-serif">a</span>',
            'direção' => '<p class="ql-direction-rtl">a</p>',
            'código' => '<div class="ql-code-block">a</div>',
        ];

        foreach ($casos as $rotulo => $html) {
            self::assertTrue(editor_texto_conteudo_detectar($html), $rotulo);
        }
    }

    public function testDetectaComAspasSimplesEClassesVizinhas(): void
    {
        self::assertTrue(editor_texto_conteudo_detectar("<p class='mt-4 ql-align-center text-sm'>a</p>"));
        self::assertTrue(editor_texto_conteudo_detectar('<p class="  ql-indent-2  ">a</p>'));
    }

    public function testNaoDetectaEmPaginaSemConteudoQuill(): void
    {
        // O caso comum: a esmagadora maioria das páginas do site não pode carregar o asset.
        self::assertFalse(editor_texto_conteudo_detectar('<div class="flex items-center gap-4"><p>texto</p></div>'));
        self::assertFalse(editor_texto_conteudo_detectar(''));
        self::assertFalse(editor_texto_conteudo_detectar(null));
    }

    public function testNaoDetectaClasseDeTerceiroQueApenasContemOPrefixo(): void
    {
        // Comparação por TOKEN, não por substring — mesma escolha de gestor_pdf_viewer_detectar().
        self::assertFalse(editor_texto_conteudo_detectar('<div class="sql-editor">a</div>'));
        self::assertFalse(editor_texto_conteudo_detectar('<div class="minha-ql-editor">a</div>'));
        self::assertFalse(editor_texto_conteudo_detectar('<div class="ql-editorial">a</div>'));
    }

    public function testNaoDetectaPrefixoSemSufixo(): void
    {
        // `ql-indent-` sozinho não é formatação que o Quill produza.
        self::assertFalse(editor_texto_conteudo_detectar('<p class="ql-indent-">a</p>'));
        self::assertFalse(editor_texto_conteudo_detectar('<p class="ql-align-">a</p>'));
    }

    public function testNaoDetectaMencaoForaDeAtributoClass(): void
    {
        // Texto, URL e comentário citando `ql-` não são conteúdo formatado.
        self::assertFalse(editor_texto_conteudo_detectar('<p>use a classe ql-align-right aqui</p>'));
        self::assertFalse(editor_texto_conteudo_detectar('<a href="/docs/ql-editor">doc</a>'));
        self::assertFalse(editor_texto_conteudo_detectar('<!-- ql-editor -->'));
    }

    // ===== gestor_quill_assets =====

    public function testAssetApontaParaOCssDeConteudoComCacheBust(): void
    {
        $tags = editor_texto_assets_publicacao('/transformamp/', '1.2.3');

        self::assertCount(1, $tags);
        self::assertStringContainsString('/transformamp/interface/quill-content.css?v=1.2.3', $tags[0]);
        self::assertStringContainsString('rel="stylesheet"', $tags[0]);
    }

    public function testAssetCarregaOPapelQueOMantemForaDaCapturaDoEditor(): void
    {
        // A captura do `css_compiled` busca folhas SEM papel declarado; sem esta marca, o CSS do
        // Quill voltaria a ser confundido com saída do Tailwind e gravado no banco.
        $tags = editor_texto_assets_publicacao('/', '1');
        self::assertStringContainsString('data-c2f-css-role="quill"', $tags[0]);
    }

    // ===== o arquivo entregue =====

    public function testOAssetExisteECobreAsClassesDeFormatacao(): void
    {
        $arquivo = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'interface' . DIRECTORY_SEPARATOR . 'quill-content.css';

        self::assertFileExists($arquivo);
        $css = (string)file_get_contents($arquivo);

        foreach (['.ql-indent-1', '.ql-indent-8', '.ql-align-right', '.ql-align-center',
                  '.ql-size-large', '.ql-font-serif', '.ql-direction-rtl'] as $classe) {
            self::assertStringContainsString($classe, $css, 'faltou ' . $classe);
        }
    }

    public function testModuloQueAbreOEditorDeclaraABiblioteca(): void
    {
        // Chamar `editor_texto_incluir()` sem declarar a biblioteca no manifesto derruba a tela com
        // HTTP 500 (função indefinida) — aconteceu ao migrar o publisher-pages para a biblioteca.
        $manifesto = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'publisher-pages' . DIRECTORY_SEPARATOR . 'publisher-pages.json';

        $dados = json_decode((string)file_get_contents($manifesto), true);
        self::assertIsArray($dados);
        self::assertContains(
            'editor-texto',
            $dados['bibliotecas'] ?? [],
            'o módulo chama editor_texto_incluir(); sem declarar a biblioteca a tela cai em 500'
        );
    }

    public function testModuloNaoReferenciaOCdnDoEditorDiretamente(): void
    {
        // A biblioteca é o ponto único: versão do Quill espalhada por módulo é como o editor e o CSS
        // publicado divergem sem ninguém perceber.
        $modulo = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'publisher-pages' . DIRECTORY_SEPARATOR . 'publisher-pages.php';

        self::assertStringNotContainsString(
            'cdn.jsdelivr.net/npm/quill',
            (string)file_get_contents($modulo),
            'a inclusão do Quill deve passar por editor_texto_incluir()'
        );
    }

    // ===== paridade entre editor e página publicada =====

    public function testParidadeInjetaOsTokensDoProjetoEscopadosNaAreaDeEdicao(): void
    {
        // O Quill roda dentro do painel e herdaria a tipografia do gestor: o operador escrevia com
        // uma fonte e via outra depois de publicar.
        $contrato = ':root{--color-mp-red:#ff010b;--font-sans:Lato,sans-serif;--color-mp-ink:#1a1a1a}';
        $css = editor_texto_paridade_css($contrato);

        self::assertStringContainsString('.ql-editor {', $css, 'precisa ser escopado');
        self::assertStringContainsString('--color-mp-red: #ff010b', $css);
        self::assertStringContainsString('font-family: var(--font-sans', $css);
        self::assertStringContainsString('data-c2f-css-role="quill-editor-parity"', $css);
    }

    public function testParidadeNaoCarregaArteEmbutidaNemRegrasDeLayout(): void
    {
        // Injetar o contrato inteiro traria o Preflight e as regras do site para dentro do painel,
        // quebrando o próprio gestor. Só tokens viajam — e nem todos.
        $contrato = ':root{--art-mask:url(data:image/svg+xml;base64,AAAA);--ok:#fff}'
            . '.container{display:flex}';
        $css = editor_texto_paridade_css($contrato);

        self::assertStringNotContainsString('data:image', $css);
        self::assertStringNotContainsString('display:flex', $css);
        self::assertStringContainsString('--ok: #fff', $css);
    }

    public function testParidadeSemContratoNaoInjetaNada(): void
    {
        // Instalação sem tema próprio: melhor nada do que um bloco vazio disputando a cascata.
        self::assertSame('', editor_texto_paridade_css(''));
        self::assertSame('', editor_texto_paridade_css('.sem-tokens{color:red}'));
    }

    public function testEditorERuntimeCompartilhamOMesmoCssDeConteudo(): void
    {
        // É essa partilha que faz o operador ver no editor o que o visitante verá: se as duas
        // pontas carregassem folhas diferentes, a paridade seria coincidência.
        $editor = editor_texto_assets_editor('/transformamp/', '1.0');
        $publico = editor_texto_assets_publicacao('/transformamp/', '1.0');

        self::assertStringContainsString('interface/quill-content.css', $publico[0]);

        $temNoEditor = false;
        foreach ($editor['css'] as $tag) {
            if (strpos($tag, 'interface/quill-content.css') !== false) {
                $temNoEditor = true;
            }
        }
        self::assertTrue($temNoEditor, 'o editor precisa carregar o MESMO css de conteúdo do site');
    }

    public function testAVersaoDoQuillVemDeUmLugarSo(): void
    {
        // Versão espalhada por módulo é como o editor e o CSS publicado divergem sem ninguém notar.
        $editor = editor_texto_assets_editor('/', '1');
        $versao = editor_texto_versao_cdn();

        foreach (array_merge($editor['css'], $editor['javascript']) as $tag) {
            if (strpos($tag, 'cdn.jsdelivr.net/npm/quill') !== false) {
                self::assertStringContainsString('quill@' . $versao, $tag);
            }
        }
    }

    public function testOChassiDoEditorNaoDitaAparencia(): void
    {
        // Regra dada pelo Engenheiro Chefe: do Quill vem a ESTRUTURA da formatação (alinhamento,
        // indentação, listas); fonte, tamanho, cor e borda vêm do PROJETO (Tailwind). O chassi do
        // editor (`.ql-container`, `.ql-editor`, `.ql-snow`) estava impondo `font-size:13px`,
        // `font-family:Helvetica` e `border:1px solid #ccc` — a fonte saía menor que a do site e
        // aparecia uma moldura cinza em volta do conteúdo publicado.
        $arquivo = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'interface' . DIRECTORY_SEPARATOR . 'quill-content.css';
        $css = (string)preg_replace('#/\*.*?\*/#s', '', (string)file_get_contents($arquivo));

        // Isola as regras cujo seletor é chassi puro (sem classe de formatação do autor).
        preg_match_all('/([^{}]+)\{([^}]*)\}/', $css, $regras, PREG_SET_ORDER);

        foreach ($regras as $regra) {
            $seletor = trim($regra[1]);
            $corpo = $regra[2];

            $ehFormatacao = (bool)preg_match('/ql-(align|indent|size|font|direction)-/', $seletor);
            if ($ehFormatacao || strpos($seletor, 'ql-') === false) {
                continue;
            }

            // Só o CHASSI é avaliado: a regra cujo alvo final é o próprio container/editor. Regras
            // que vestem elementos DENTRO do conteúdo (`.ql-editor td`, `.ql-editor blockquote`) são
            // formatação publicada — foi ao cortá-las que sumiram os números das listas, as bolinhas
            // e a barra da citação.
            $partes = preg_split('/\s+/', trim(explode(',', $seletor)[0]));
            $alvo = (string)end($partes);
            $alvo = explode(':', $alvo)[0];
            $semChassi = str_replace(['.ql-container', '.ql-editor', '.ql-snow'], '', $alvo);
            if ($semChassi !== '') {
                continue;
            }

            foreach (['font-family', 'font-size', 'border:', 'height:'] as $proibida) {
                self::assertStringNotContainsString(
                    $proibida,
                    $corpo,
                    "o chassi '{$seletor}' não pode ditar {$proibida} — isso vem do projeto"
                );
            }
        }
    }

    public function testAFormatacaoEscolhidaPeloAutorEhPreservada(): void
    {
        // O corte de aparência não pode levar junto o que o autor escolheu no editor.
        $arquivo = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'interface' . DIRECTORY_SEPARATOR . 'quill-content.css';
        $css = (string)file_get_contents($arquivo);

        self::assertStringContainsString('.ql-align-right{text-align:right}', $css);
        self::assertStringContainsString('.ql-align-center{text-align:center}', $css);
        self::assertMatchesRegularExpression('/ql-indent-1[^{]*\{[^}]*padding-left/', $css);
        self::assertStringContainsString('list-style-type', $css);
    }

    public function testOAssetNaoUsaImportantNemTrazAUiDoEditor(): void
    {
        $arquivo = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'interface' . DIRECTORY_SEPARATOR . 'quill-content.css';
        $css = (string)file_get_contents($arquivo);

        // Comentários fora: o cabeçalho documenta a própria regra e não é CSS aplicado.
        $regras = (string)preg_replace('#/\*.*?\*/#s', '', $css);

        // Ele divide a página com as utilities do Tailwind: vencer por força bruta é justamente o
        // que o `css_compiled` contaminado fazia.
        self::assertStringNotContainsString('!important', $regras);

        // A toolbar não existe na página publicada.
        foreach (['.ql-toolbar', '.ql-picker', '.ql-tooltip'] as $ui) {
            self::assertStringNotContainsString($ui, $css, 'vazou UI do editor: ' . $ui);
        }
    }
}
