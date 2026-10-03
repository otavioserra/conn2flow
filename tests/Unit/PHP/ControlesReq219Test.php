<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/controles.php';

/**
 * req-219: ajudantes de HTML da biblioteca de controles (padrão de campos, select, chave, abas, botões).
 */
final class ControlesReq219Test extends TestCase
{
    private function dom(string $html): DOMXPath
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="raiz">' . $html . '</div>');
        libxml_clear_errors();
        return new DOMXPath($doc);
    }

    public function testCampoDeTextoTemRotuloLigadoAjudaErroEEscape(): void
    {
        $html = controles_campo(['tipo' => 'texto', 'name' => 'pagina-nome', 'rotulo' => 'Nome <b>', 'valor' => '"><script>x</script>',
            'ajuda' => 'Aparece no menu', 'erro' => 'Obrigatório', 'obrigatorio' => true]);
        $x = $this->dom($html);
        $entrada = $x->query('//input[@name="pagina-nome"]')->item(0);
        self::assertNotNull($entrada);
        self::assertSame('"><script>x</script>', $entrada->getAttribute('value'));
        self::assertSame(0, $x->query('//script')->length);
        $id = $entrada->getAttribute('id');
        self::assertSame($id, $x->query('//label')->item(0)->getAttribute('for'));
        self::assertSame($id . '-ajuda ' . $id . '-erro', $entrada->getAttribute('aria-describedby'));
        self::assertSame('true', $entrada->getAttribute('aria-invalid'));
        self::assertTrue($entrada->hasAttribute('required'));
        self::assertStringContainsString('Nome &lt;b&gt;', $html);
        self::assertStringContainsString('c2fc-campo-entrada', $html);
    }

    public function testTiposMapeadosAreaEOculto(): void
    {
        self::assertStringContainsString('type="datetime-local"', controles_campo(['tipo' => 'data-hora', 'name' => 'inicio']));
        self::assertStringContainsString('type="password"', controles_campo(['tipo' => 'senha', 'name' => 's', 'valor' => 'segredo']));
        self::assertStringNotContainsString('segredo', controles_campo(['tipo' => 'senha', 'name' => 's', 'valor' => 'segredo']));
        $area = controles_campo(['tipo' => 'area', 'name' => 'bio', 'valor' => '</textarea><b>', 'linhas' => 6]);
        self::assertStringContainsString('rows="6"', $area);
        self::assertStringContainsString('&lt;/textarea&gt;&lt;b&gt;', $area);
        self::assertSame('<input type="hidden" name="id" value="7">', controles_campo(['tipo' => 'oculto', 'name' => 'id', 'valor' => 7]));
    }

    public function testSelectComGruposValorBuscaEAjax(): void
    {
        $html = controles_select(['name' => 'layout', 'valor' => 'b', 'busca' => true, 'placeholder' => 'Escolha',
            'opcoes' => ['a' => 'Alfa', ['valor' => 'b', 'rotulo' => 'Beta', 'grupo' => 'G1'], ['valor' => 'c', 'rotulo' => '<i>Gama</i>', 'grupo' => 'G1']],
            'ajax' => ['opcao' => 'buscar-layouts', 'minimo' => 3], 'atributos' => ['data-x' => '1', 'onclick' => 'mal()']]);
        $x = $this->dom($html);
        $select = $x->query('//select')->item(0);
        self::assertSame('1', $select->getAttribute('data-c2f-select'));
        self::assertSame('1', $select->getAttribute('data-c2f-busca'));
        self::assertSame('buscar-layouts', $select->getAttribute('data-c2f-ajax-opcao'));
        self::assertSame('3', $select->getAttribute('data-c2f-ajax-minimo'));
        self::assertSame('1', $select->getAttribute('data-x'));
        self::assertFalse($select->hasAttribute('onclick'), 'atributo de evento não passa');
        self::assertSame('G1', $x->query('//optgroup')->item(0)->getAttribute('label'));
        self::assertSame('b', $x->query('//option[@selected]')->item(0)->getAttribute('value'));
        self::assertSame(0, $x->query('//i')->length);
        $multiplo = controles_select(['name' => 'tags', 'multiplo' => true, 'valor' => ['1', '2'], 'opcoes' => ['1' => 'Um', '2' => 'Dois', '3' => 'Três']]);
        self::assertStringContainsString('name="tags[]"', $multiplo);
        self::assertSame(2, substr_count($multiplo, ' selected'));
    }

    public function testChaveAbasEBotoes(): void
    {
        $chave = controles_chave(['name' => 'raiz', 'marcado' => true, 'rotulo' => 'Página raiz', 'oculto_quando_desligada' => true]);
        self::assertStringContainsString('<input type="hidden" name="raiz" value="0">', $chave);
        self::assertStringContainsString('checked', $chave);
        self::assertStringContainsString('c2fc-chave-trilho', $chave);
        self::assertStringContainsString('data-c2fc-campo="chave"', controles_campo(['tipo' => 'chave', 'name' => 'raiz', 'rotulo' => 'Raiz']));

        $abas = controles_abas([['id' => 'codigo', 'rotulo' => 'Código', 'conteudo' => '<p>c</p>'], ['id' => 'visual', 'rotulo' => 'Visual <x>', 'conteudo' => '<p>v</p>']], 'visual');
        self::assertStringContainsString('data-c2f-aba="visual" aria-selected="true"', $abas);
        self::assertStringContainsString('data-c2f-painel="codigo" hidden', $abas);
        self::assertStringContainsString('Visual &lt;x&gt;', $abas);

        $botao = controles_botao(['rotulo' => 'Salvar', 'tipo' => 'submit', 'icone' => 'save']);
        self::assertStringContainsString('type="submit"', $botao);
        self::assertStringContainsString('c2fc-botao c2fc-botao-primario', $botao);
        self::assertStringContainsString('data-lucide="save"', $botao);
        $link = controles_botao(['rotulo' => 'Excluir', 'variante' => 'perigo', 'url' => '/x?a=1&b="2"']);
        self::assertStringStartsWith('<a', $link);
        self::assertStringContainsString('href="/x?a=1&amp;b=&quot;2&quot;"', $link);
        self::assertStringContainsString('c2fc-botao-perigo', $link);
    }
}
