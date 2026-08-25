<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Higienização do HTML entregue ao navegador (req-132 / BATCH-134).
 *
 * Esta função reescreve **toda página servida pelo sistema**. Um erro aqui não aparece num módulo
 * ou numa rota: aparece em tudo, e provavelmente numa página que ninguém abriu ainda. Por isso os
 * testes se concentram menos no que ela remove — que é fácil de ver — e mais no que ela **não pode
 * tocar**, que é onde um defeito ficaria escondido.
 */
final class HtmlSanitizeTest extends TestCase
{
    // --- O que deve sair -------------------------------------------------------------

    public function testComentarioDeHtmlERemovido(): void
    {
        $html = "<div>\n  <!-- ===== TOP APP BAR ===== -->\n  <p>oi</p>\n</div>";
        $limpo = gestor_html_higienizar($html);

        self::assertStringNotContainsString('TOP APP BAR', $limpo);
        self::assertStringContainsString('<p>oi</p>', $limpo);
    }

    public function testComentarioDeCssDentroDeStyleERemovido(): void
    {
        $html = "<style>\n  /* Indicador de processamento */\n  .a { color: red; }\n</style>";
        $limpo = gestor_html_higienizar($html);

        self::assertStringNotContainsString('Indicador de processamento', $limpo);
        self::assertStringContainsString('.a { color: red; }', $limpo);
    }

    public function testIndentacaoSaiMasAQuebraDeLinhaFica(): void
    {
        // A quebra permanece de propósito: entre dois elementos em linha ela é renderizada como
        // espaço. Removê-la junto com a indentação faria "ab" aparecer colado na tela.
        $limpo = gestor_html_higienizar("<div>\n        <span>a</span>\n        <span>b</span>\n</div>");

        self::assertStringNotContainsString('        ', $limpo);
        self::assertStringContainsString("<span>a</span>\n<span>b</span>", $limpo);
    }

    // --- O que NÃO pode ser tocado ---------------------------------------------------

    public function testConteudoDePreEPreservadoByteAByte(): void
    {
        // Espaço dentro de `<pre>` é conteúdo: reindentar muda o que o usuário lê.
        $pre = "<pre>\n    linha indentada\n        mais fundo\n</pre>";
        self::assertStringContainsString($pre, gestor_html_higienizar("<div>\n  {$pre}\n</div>"));
    }

    public function testConteudoDeTextareaEPreservado(): void
    {
        // Aqui o espaço volta ao servidor quando o formulário é enviado.
        $ta = "<textarea>\n  valor    com   espacos\n</textarea>";
        self::assertStringContainsString($ta, gestor_html_higienizar("<form>\n  {$ta}\n</form>"));
    }

    public function testJavaScriptPerdeComentariosMasNaoPerdeSemantica(): void
    {
        // Este teste mudou de contrato na 2a rodada da req-132. Antes, `<script>` era preservado
        // byte a byte; o operador apontou que os comentarios de JavaScript continuavam vazando na
        // pagina publica, e o bloco passou a ser processado por um SCANNER (nao por expressao
        // regular). O que se afirma agora e mais forte do que "nao mexe": mexe, e nao quebra.
        $js = "<script>\n  var url = 'http://x';        // comentario\n  var re = /\/\*/;\n  var t = `linha\n     com indentacao`;\n</script>";
        $limpo = gestor_html_higienizar("<body>\n  {$js}\n</body>");

        self::assertStringNotContainsString('// comentario', $limpo, 'o comentario deveria ter saido');

        // E as tres armadilhas continuam intactas:
        self::assertStringContainsString("'http://x'", $limpo, 'a URL virou comentario');
        self::assertStringContainsString('/\/\*/', $limpo, 'o regex literal foi corrompido');
        self::assertStringContainsString("`linha\n     com indentacao`", $limpo, 'o template literal perdeu o conteudo');
    }

    public function testComentarioCondicionalEPreservado(): void
    {
        // Comentário condicional é INSTRUÇÃO, não comentário: removido, o conteúdo que ele guarda
        // passa a valer para todos os navegadores.
        $html = "<!--[if lt IE 9]><script src=\"shim.js\"></script><![endif]-->";
        self::assertStringContainsString('[if lt IE 9]', gestor_html_higienizar($html));
    }

    public function testComentarioDentroDeScriptNaoEConfundidoComComentarioDeHtml(): void
    {
        $html = "<script>\n  var a = '<!-- isso e uma string -->';\n</script>";
        self::assertStringContainsString('<!-- isso e uma string -->', gestor_html_higienizar($html));
    }

    public function testTextoQueMencionaTagDentroDeAtributoSobrevive(): void
    {
        $html = '<div data-dica="use <!-- assim -->">x</div>';
        $limpo = gestor_html_higienizar($html);
        self::assertStringContainsString('data-dica', $limpo);
    }

    public function testEntradaVaziaOuSemComentarioVoltaIntacta(): void
    {
        self::assertSame('', gestor_html_higienizar(''));
        self::assertSame('<p>x</p>', gestor_html_higienizar('<p>x</p>'));
    }

    public function testMarcadorInternoNaoVazaParaOHtmlFinal(): void
    {
        // Os blocos protegidos saem e voltam por um marcador; se um sobrar na página, o defeito
        // seria visível para o visitante.
        $limpo = gestor_html_higienizar("<div>\n  <pre>a</pre>\n  <script>var b=1;</script>\n  <textarea>c</textarea>\n</div>");
        self::assertStringNotContainsString('c2f:protegido', $limpo);
        self::assertStringContainsString('<pre>a</pre>', $limpo);
        self::assertStringContainsString('var b=1;', $limpo);
    }

    public function testMultiplosBlocosProtegidosVoltamNaOrdemCerta(): void
    {
        $limpo = gestor_html_higienizar("<pre>PRIMEIRO</pre>\n<!-- x -->\n<pre>SEGUNDO</pre>");
        self::assertLessThan(strpos($limpo, 'SEGUNDO'), strpos($limpo, 'PRIMEIRO'));
    }

    // --- O gate de ativação ----------------------------------------------------------

    public function testModoOnLigaMesmoEmDesenvolvimento(): void
    {
        global $_GESTOR;
        $_ENV['HTML_SANITIZE'] = 'on';
        $_GESTOR['development-env'] = true;
        self::assertTrue(gestor_pagina_higienizar_ativo());
    }

    public function testModoOffDesligaMesmoEmProducao(): void
    {
        global $_GESTOR;
        $_ENV['HTML_SANITIZE'] = 'off';
        $_GESTOR['development-env'] = false;
        self::assertFalse(gestor_pagina_higienizar_ativo());
    }

    public function testAutoLigaEmProducaoEDesligaEmDesenvolvimento(): void
    {
        global $_GESTOR;
        $_ENV['HTML_SANITIZE'] = 'auto';

        $_GESTOR['development-env'] = false;
        self::assertTrue(gestor_pagina_higienizar_ativo(), 'producao deveria sair limpa');

        $_GESTOR['development-env'] = true;
        self::assertFalse(gestor_pagina_higienizar_ativo(), 'o ambiente local deveria ficar legivel');
    }

    public function testChaveAusenteSeComportaComoAuto(): void
    {
        global $_GESTOR;
        unset($_ENV['HTML_SANITIZE']);
        $_GESTOR['development-env'] = false;
        self::assertTrue(gestor_pagina_higienizar_ativo());
    }

    public function testValorDesconhecidoCaiEmAutoEmVezDeDesligar(): void
    {
        // Uma chave digitada errada no `.env` não pode desligar em silêncio a limpeza de um site
        // em produção — o erro de digitação apareceria como vazamento de comentário interno.
        global $_GESTOR;
        $_ENV['HTML_SANITIZE'] = 'talvez';
        $_GESTOR['development-env'] = false;
        self::assertTrue(gestor_pagina_higienizar_ativo());
    }

    // --- JavaScript: o scanner (req-132, 2a rodada) ----------------------------------
    //
    // Cada teste aqui é um caso em que uma expressão regular ingênua truncaria o código. O defeito
    // não apareceria no navegador de quem programou: apareceria na página de alguém.

    public function testComentarioDeLinhaEDeBlocoSaem(): void
    {
        $js = "var a = 1; // conta\n/* bloco\n   de comentario */\nvar b = 2;";
        $limpo = gestor_js_higienizar($js);

        self::assertStringNotContainsString('conta', $limpo);
        self::assertStringNotContainsString('bloco', $limpo);
        self::assertStringContainsString('var a = 1;', $limpo);
        self::assertStringContainsString('var b = 2;', $limpo);
    }

    public function testBarrasDentroDeStringNaoSaoComentario(): void
    {
        // O caso mais comum de todos: uma URL.
        $js = "var url = 'http://exemplo.test/x';\nvar fim = 1;";
        $limpo = gestor_js_higienizar($js);

        self::assertStringContainsString("'http://exemplo.test/x'", $limpo);
        self::assertStringContainsString('var fim = 1;', $limpo);
    }

    public function testAberturaDeBlocoDentroDeStringNaoEngoleOResto(): void
    {
        $js = 'var msg = "isto tem /*"; var depois = 2;';
        $limpo = gestor_js_higienizar($js);

        self::assertStringContainsString('var depois = 2;', $limpo, 'o resto do arquivo foi engolido');
    }

    public function testTemplateLiteralPreservaConteudoEQuebras(): void
    {
        $js = "var t = `linha // nao comenta\n   com indentacao`;";
        self::assertStringContainsString("`linha // nao comenta\n   com indentacao`", gestor_js_higienizar($js));
    }

    public function testRegexLiteralNaoEConfundidoComComentario(): void
    {
        // `/\/\*/` começa com barra-barra: uma regex ingênua leria como comentário de linha.
        $js = 'var re = /\/\*/; var depois = 3;';
        $limpo = gestor_js_higienizar($js);

        self::assertStringContainsString('var depois = 3;', $limpo);
        self::assertStringContainsString('/\/\*/', $limpo);
    }

    public function testBarraDentroDeClasseDeRegexNaoFechaOLiteral(): void
    {
        $js = 'var re = /[/]/; var depois = 4;';
        self::assertStringContainsString('var depois = 4;', gestor_js_higienizar($js));
    }

    public function testDivisaoNaoEConfundidaComRegex(): void
    {
        $js = "var m = (a + b) / 2; // fim\nvar n = x / y;";
        $limpo = gestor_js_higienizar($js);

        self::assertStringContainsString('(a + b) / 2;', $limpo);
        self::assertStringContainsString('var n = x / y;', $limpo);
        self::assertStringNotContainsString('fim', $limpo);
    }

    public function testAspaEscapadaNaoEncerraAString(): void
    {
        $js = "var s = 'ele disse \'oi\' // nao e comentario'; var depois = 5;";
        $limpo = gestor_js_higienizar($js);

        self::assertStringContainsString('nao e comentario', $limpo);
        self::assertStringContainsString('var depois = 5;', $limpo);
    }

    public function testIndentacaoSaiMasAQuebraFicaPorCausaDaAsi(): void
    {
        // JavaScript insere ponto e vírgula automaticamente. Juntar duas linhas que dependem disso
        // mudaria o programa — por isso só a indentação sai.
        $js = "var a = 1\n        var b = 2";
        $limpo = gestor_js_higienizar($js);

        self::assertStringContainsString("var a = 1\nvar b = 2", $limpo);
    }

    public function testComentarioDeBlocoComQuebraDeixaUmaQuebraNoLugar(): void
    {
        // Sem isso, `var a = 1` e `var b = 2` ficariam na mesma linha e a ASI mudaria de ideia.
        $js = "var a = 1\n/* nota\n   longa */\nvar b = 2";
        $limpo = gestor_js_higienizar($js);

        self::assertStringNotContainsString('nota', $limpo);
        self::assertStringNotContainsString('var a = 1 var b = 2', $limpo);
    }

    public function testComentarioDeBlocoNaoFechadoNaoVazaLixo(): void
    {
        self::assertStringNotContainsString('resto', gestor_js_higienizar('var a = 1; /* resto sem fechar'));
    }

    // --- Quais <script> podem ser tocados --------------------------------------------

    public function testScriptDeJsonNaoEProcessado(): void
    {
        // `//` dentro de uma URL no JSON viraria comentário e o resto da linha sumiria.
        $html = '<script type="application/json">{"url":"http://x.test/a","n":1}</script>';
        self::assertStringContainsString('"url":"http://x.test/a"', gestor_html_higienizar($html));
    }

    public function testScriptDeTemplateNaoEProcessado(): void
    {
        $html = "<script type=\"text/template\">\n  <div>  <!-- markup guardado -->  </div>\n</script>";
        self::assertStringContainsString('markup guardado', gestor_html_higienizar($html));
    }

    public function testScriptComSrcNaoTemConteudoParaLimpar(): void
    {
        $html = '<script src="/a.js"></script>';
        self::assertStringContainsString('<script src="/a.js"></script>', gestor_html_higienizar($html));
    }

    public function testScriptModuleEProcessado(): void
    {
        $html = "<script type=\"module\">\n  // some\n  var a = 1;\n</script>";
        $limpo = gestor_html_higienizar($html);

        self::assertStringNotContainsString('// some', $limpo);
        self::assertStringContainsString('var a = 1;', $limpo);
    }

    public function testScriptInlineSemTypeEProcessadoNaPagina(): void
    {
        $html = "<body>\n<script>\n  // comentario interno\n  var url = 'http://x';\n</script>\n</body>";
        $limpo = gestor_html_higienizar($html);

        self::assertStringNotContainsString('comentario interno', $limpo);
        self::assertStringContainsString("var url = 'http://x';", $limpo);
    }

    public function testGateDoJsDesligaApenasOJavaScript(): void
    {
        global $_GESTOR;
        $_ENV['HTML_SANITIZE_JS'] = 'off';

        $html = "<div>\n  <!-- comentario html -->\n  <script>\n    // comentario js\n    var a = 1;\n  </script>\n</div>";
        $limpo = gestor_html_higienizar($html);

        self::assertStringNotContainsString('comentario html', $limpo, 'o HTML deveria continuar sendo limpo');
        self::assertStringContainsString('// comentario js', $limpo, 'o JS deveria ter ficado intacto');

        unset($_ENV['HTML_SANITIZE_JS']);
    }

    public function testGateDoJsSegueAChavePrincipalQuandoAusente(): void
    {
        unset($_ENV['HTML_SANITIZE_JS']);
        self::assertTrue(gestor_pagina_higienizar_js_ativo());
    }

    protected function tearDown(): void
    {
        unset($_ENV['HTML_SANITIZE']);
        parent::tearDown();
    }
}
