<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-252 — widget "Lousa": uma lousa do sistema renderizada dentro de uma página, sem iframe.
 * O widget roda num processo à parte, com banco, componente e sistema de widgets simulados; a página
 * de verdade é conferida pelo roteiro de navegador (`sdd/validation/req252/req252-browser.cjs`).
 */
final class DashboardLousaWidgetReq252Test extends TestCase
{
    private const MODULO = CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/';

    private const STUBS = <<<'PHP'
<?php
$_GESTOR=['linguagem-codigo'=>'pt-br'];
$GLOBALS['c']=['consultas'=>[],'widgets'=>[],'recursos'=>[],'js'=>[],'componente'=>0];
function banco_escape_field($v){return addslashes((string)$v);}
function banco_select($p){
    $GLOBALS['c']['consultas'][]=$p['tabela'];
    if($p['tabela']==='dashboard_boards') return $GLOBALS['lousa'];
    if($p['tabela']==='widgets') return [['id'=>'menus'],['id'=>'galleries'],['id'=>'dashboard']];
    return null;
}
function gestor_componente($p){$GLOBALS['c']['componente']++;return file_get_contents($GLOBALS['componente']);}
function gestor_incluir_biblioteca($b){}
function gestor_pagina_recursos_incluir($p){$GLOBALS['c']['recursos'][]=$p;}
function gestor_pagina_javascript_incluir($p){$GLOBALS['c']['js'][]=$p;}
function assets_externos_urls_js($n){return ['lucide'=>['lucide.min.js'=>'/assets/lucide/lucide.min.js?v=1']];}
function widgets_get($p){
    $GLOBALS['c']['widgets'][]=$p['id'];
    if(strpos($p['id'],'"vazio"')!==false) return '   ';
    // Um widget que tenta abrir outra lousa por dentro não recebe nada.
    if(strpos($p['id'],'"aninhado"')!==false) return '<i>'.dashboard_render(['grupo_slug'=>'outra']).'</i>';
    return '<nav data-sig="'.htmlspecialchars($p['id']).'">conteúdo #modo# #w# #conteudo#</nav>';
}
PHP;

    private function rodar(?array $lousa, string $id = 'vendas'): array
    {
        $script = self::STUBS . "\n\$GLOBALS['componente']=" . var_export(self::MODULO . 'resources/pt-br/components/dashboard-lousa-widget/dashboard-lousa-widget.html', true)
            . ";\n\$GLOBALS['lousa']=" . var_export($lousa === null ? null : ['id' => 'vendas', 'mode' => $lousa['mode'], 'layout' => json_encode($lousa['itens'])], true)
            . ";\nrequire " . var_export(self::MODULO . 'dashboard.widget.php', true) . ";\n\$html=dashboard_render(['grupo_slug'=>" . var_export($id, true) . "]);\necho json_encode(['html'=>\$html]+\$GLOBALS['c']);";
        $arquivo = tempnam(sys_get_temp_dir(), 'req252-');
        try {
            file_put_contents($arquivo, $script);
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo) . ' 2>&1', $linhas, $status);
            self::assertSame(0, $status, implode("\n", $linhas));
            return json_decode(implode("\n", $linhas), true, 512, JSON_THROW_ON_ERROR);
        } finally {
            unlink($arquivo);
        }
    }

    /** Os itens renderizados, na ordem, como elementos. */
    private function itens(string $html): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>');
        $itens = [];
        foreach ((new DOMXPath($dom))->query('//div[contains(concat(" ", @class, " "), " c2f-lousa-item ")]') as $no) {
            $itens[] = $no;
        }
        return $itens;
    }

    private function widget(string $id, string $registro, array $extra = []): array
    {
        return $extra + ['id' => $id, 'name' => ucfirst($id), 'registro_id' => $registro, 'width' => 4, 'height_px' => 220];
    }

    private function objeto(array $objeto, array $extra = []): array
    {
        return $extra + ['id' => 'objeto', 'name' => 'Objeto', 'width' => 3, 'height_px' => 120, 'object' => $objeto, 'options' => ['header' => false, 'frame' => false]];
    }

    public function testGradeComWidgetsOpcoesEPosicao(): void
    {
        $r = $this->rodar(['mode' => 'grade', 'itens' => [
            $this->widget('menus', 'principal', ['width' => 5, 'height_px' => 300, 'x' => 2, 'y' => 4,
                'options' => ['title' => 'Navegue <b>aqui</b>', 'background' => '#0F172A', 'padding' => 'medium', 'hide' => 'md', 'titleFont' => 'Oswald',
                    'bgImage' => '/files/fundo.webp', 'bgOpacity' => 35, 'bgFit' => 'repeat']]),
            $this->widget('galleries', 'fotos', ['width' => 12, 'options' => ['frame' => false, 'header' => false, 'title' => 'Não aparece']]),
        ]]);
        self::assertSame(1, preg_match('/^<div class="c2f-lousa" data-c2f-lousa data-lousa="vendas" data-mode="grade" data-lucide-url="">/', $r['html']));
        self::assertStringNotContainsString('objeto-', $r['html']);
        self::assertStringNotContainsString('<!-- item', $r['html']);
        $itens = $this->itens($r['html']);
        self::assertCount(2, $itens);

        [$menu, $galeria] = $itens;
        // REQ-259: posição e tipo de cada item, para a medição.
        self::assertSame([['1', 'menus'], ['2', 'galleries']], array_map(static fn ($i) => [$i->getAttribute('data-item'), $i->getAttribute('data-tipo')], $itens));
        self::assertSame('c2f-lousa-item has-bg-image', $menu->getAttribute('class'));
        self::assertSame(['5', '300', '2', '4', 'md', 'dark'], array_map([$menu, 'getAttribute'], ['data-w', 'data-h', 'data-x', 'data-y', 'data-hide', 'data-tone']));
        self::assertSame('--c2f-w:5;--c2f-wt:3;--c2f-h:300px;background-color:#0f172a;--c2f-fundo:url("/files/fundo.webp");--c2f-fundo-opacidade:0.35;--c2f-fundo-tamanho:auto;--c2f-fundo-repetir:repeat;', $menu->getAttribute('style'));
        $titulo = $menu->firstChild;
        self::assertSame(['c2f-lousa-titulo', 'Navegue <b>aqui</b>', "font-family:'Oswald',sans-serif;"], [$titulo->getAttribute('class'), $titulo->textContent, $titulo->getAttribute('style')]);
        self::assertSame(['c2f-lousa-corpo', 'medium'], [$menu->lastChild->getAttribute('class'), $menu->lastChild->getAttribute('data-padding')]);

        self::assertSame('c2f-lousa-item is-frameless', $galeria->getAttribute('class'));
        self::assertSame(['12', '', '', '', 'light'], array_map([$galeria, 'getAttribute'], ['data-w', 'data-x', 'data-y', 'data-hide', 'data-tone']));
        self::assertSame('--c2f-w:12;--c2f-wt:6;--c2f-h:220px;', $galeria->getAttribute('style'));
        self::assertSame(1, $galeria->childNodes->length);

        // Cada widget sai pelo sistema de widgets da página, com a assinatura do registro.
        self::assertSame(['menus->render({"grupo_slug":"principal","id":"principal"})', 'galleries->render({"grupo_slug":"fotos","id":"fotos"})'], $r['widgets']);
        // Marcador dentro do HTML de um widget não é lido de novo.
        self::assertStringContainsString('conteúdo #modo# #w# #conteudo#</nav>', $r['html']);
        self::assertSame([['tipo' => 'widget', 'modulo_id' => 'dashboard']], array_map(static fn ($js) => ['tipo' => $js['tipo'], 'modulo_id' => $js['modulo_id']], $r['js']));
        self::assertSame(['<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;700&amp;display=swap">'], array_column($r['recursos'], 'html_extra_head'));
    }

    public function testObjetosLivresSaemDosMoldesComTextoEscapado(): void
    {
        $r = $this->rodar(['mode' => 'lousa', 'itens' => [
            $this->objeto(['type' => 'text', 'text' => "Olá <script>alert(1)</script>\n#texto# #estilo#", 'font' => 'Bebas Neue', 'size' => 40, 'weight' => 400, 'align' => 'left', 'color' => '#FF0000'], ['x' => 0, 'y' => 0]),
            $this->objeto(['type' => 'shape', 'shape' => 'circle', 'fill' => '#00ff00']),
            $this->objeto(['type' => 'image', 'src' => '/files/a.png', 'fit' => 'contain', 'alt' => 'Foto "final"']),
            $this->objeto(['type' => 'icon', 'icon' => 'rocket', 'color' => '#123456']),
            $this->objeto(['type' => 'button', 'text' => 'Assine', 'href' => 'https://conn2flow.com/planos?a=1&b=2', 'newTab' => true, 'fill' => '#0284c7', 'color' => '#ffffff', 'font' => 'Inter']),
            $this->objeto(['type' => 'button', 'text' => 'Sem destino', 'href' => 'javascript:alert(1)']),
            // Sem o que mostrar: não ocupam lugar na página.
            $this->objeto(['type' => 'text', 'text' => "  \n "]),
            $this->objeto(['type' => 'image', 'src' => 'https://evil.example/a.png']),
            $this->objeto(['type' => 'button', 'text' => '', 'href' => '/x/']),
        ]]);
        self::assertStringContainsString('data-mode="lousa" data-lucide-url="/assets/lucide/lucide.min.js?v=1"', $r['html']);
        self::assertStringNotContainsString('<script', $r['html']);
        self::assertStringNotContainsString('javascript:', $r['html']);
        self::assertStringNotContainsString('evil.example', $r['html']);
        $itens = $this->itens($r['html']);
        self::assertCount(6, $itens);
        foreach ($itens as $item) {
            self::assertSame('c2f-lousa-item is-frameless is-objeto', $item->getAttribute('class'));
        }
        // REQ-259: os que não entram não contam na posição; o tipo do objeto vai junto.
        self::assertSame(['1:objeto-text', '2:objeto-shape', '3:objeto-image', '4:objeto-icon', '5:objeto-button', '6:objeto-button'], array_map(static fn ($i) => $i->getAttribute('data-item') . ':' . $i->getAttribute('data-tipo'), $itens));
        $dentro = static fn (DOMElement $item): DOMElement => $item->lastChild->firstChild;

        $texto = $dentro($itens[0]);
        self::assertSame("Olá <script>alert(1)</script>\n#texto# #estilo#", $texto->textContent);
        self::assertSame("font-family:'Bebas Neue',sans-serif;font-size:40px;font-weight:400;text-align:left;color:#ff0000;", $texto->getAttribute('style'));
        self::assertSame(['0', '0'], [$itens[0]->getAttribute('data-x'), $itens[0]->getAttribute('data-y')]);

        $forma = $dentro($itens[1])->firstChild;
        self::assertSame(['c2f-lousa-figura', 'circle', 'background-color:#00ff00;'], [$forma->getAttribute('class'), $forma->getAttribute('data-shape'), $forma->getAttribute('style')]);

        $imagem = $dentro($itens[2])->firstChild;
        self::assertSame(['img', '/files/a.png', 'Foto "final"', 'lazy', 'object-fit:contain;'], [$imagem->nodeName, $imagem->getAttribute('src'), $imagem->getAttribute('alt'), $imagem->getAttribute('loading'), $imagem->getAttribute('style')]);

        $icone = $dentro($itens[3]);
        self::assertSame(['color:#123456;', 'rocket'], [$icone->getAttribute('style'), $icone->firstChild->getAttribute('data-lucide')]);

        $botao = $dentro($itens[4])->firstChild;
        self::assertSame(['a', 'https://conn2flow.com/planos?a=1&b=2', '_blank', 'noopener', 'Assine'], [$botao->nodeName, $botao->getAttribute('href'), $botao->getAttribute('target'), $botao->getAttribute('rel'), $botao->textContent]);
        self::assertSame("font-family:'Inter',sans-serif;font-size:28px;font-weight:700;background-color:#0284c7;color:#ffffff;", $botao->getAttribute('style'));

        $rotulo = $dentro($itens[5])->firstChild;
        self::assertSame(['span', 'c2f-lousa-acao', 'Sem destino', false], [$rotulo->nodeName, $rotulo->getAttribute('class'), $rotulo->textContent, $rotulo->hasAttribute('href')]);

        // Uma folha só, famílias em ordem, Bebas Neue sem pesos.
        self::assertSame(['<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&amp;family=Inter:wght@400;700&amp;display=swap">'], array_column($r['recursos'], 'html_extra_head'));
        self::assertSame([], $r['widgets']);
    }

    public function testTituloDoAutorNaoViraMarcador(): void
    {
        $r = $this->rodar(['mode' => 'grade', 'itens' => [$this->widget('menus', 'principal', ['options' => ['title' => '#w# #conteudo# #classes#']])]]);
        self::assertSame('#w# #conteudo# #classes#', $this->itens($r['html'])[0]->firstChild->textContent);
    }

    public function testOQueNaoEntraNaPagina(): void
    {
        // Lousa que não existe, identificador fora do formato e lousa sem item.
        self::assertSame('', $this->rodar(null)['html']);
        $invalido = $this->rodar(['mode' => 'grade', 'itens' => [$this->widget('menus', 'principal')]], "vendas' OR '1'='1");
        self::assertSame(['', []], [$invalido['html'], $invalido['consultas']]);
        $vazia = $this->rodar(['mode' => 'grade', 'itens' => []]);
        self::assertSame(['', 0, []], [$vazia['html'], $vazia['componente'], $vazia['js']]);

        // Widget fora do cadastro, sem registro, lousa dentro de lousa e widget que não devolve nada.
        $r = $this->rodar(['mode' => 'grade', 'itens' => [
            $this->widget('modulo-qualquer', 'x'),
            $this->widget('menus', ''),
            $this->widget('dashboard', 'outra'),
            $this->widget('menus', 'vazio'),
            $this->widget('menus', 'aninhado'),
        ]]);
        self::assertSame(['menus->render({"grupo_slug":"vazio","id":"vazio"})', 'menus->render({"grupo_slug":"aninhado","id":"aninhado"})'], $r['widgets']);
        $itens = $this->itens($r['html']);
        self::assertCount(1, $itens);
        self::assertSame('<i></i>', $itens[0]->ownerDocument->saveHTML($itens[0]->lastChild->firstChild));
        // A lousa de dentro nem foi consultada.
        self::assertSame(['dashboard_boards', 'widgets'], $r['consultas']);

        // Nada para mostrar: nem casca, nem script.
        $nada = $this->rodar(['mode' => 'lousa', 'itens' => [$this->widget('modulo-qualquer', 'x'), $this->objeto(['type' => 'text', 'text' => ''])]]);
        self::assertSame(['', []], [$nada['html'], $nada['js']]);
    }

    public function testCadastroComponenteEArquivoDeLayout(): void
    {
        $modulo = json_decode((string) file_get_contents(self::MODULO . 'dashboard.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach (['pt-br' => 'Lousa', 'en' => 'Board'] as $lang => $nome) {
            self::assertSame([['id' => 'dashboard', 'name' => $nome, 'icon' => 'th large', 'tabela' => 'dashboard_boards']], $modulo['resources'][$lang]['widgets']);
            self::assertContains('dashboard-lousa-widget', array_column($modulo['resources'][$lang]['components'], 'id'));
        }
        $base = self::MODULO . 'resources/%s/components/dashboard-lousa-widget/dashboard-lousa-widget.%s';
        foreach (['html', 'css'] as $tipo) {
            self::assertFileEquals(sprintf($base, 'pt-br', $tipo), sprintf($base, 'en', $tipo));
        }
        $html = (string) file_get_contents(sprintf($base, 'pt-br', 'html'));
        // Componente sem utilitário do Tailwind e sem texto: só estrutura e moldes.
        self::assertSame(0, preg_match('/class="[^"]*\b(?:flex|grid|hidden|p-\d|text-\w+-\d)\b/', $html));
        self::assertStringNotContainsString('@[[', $html);
        $css = (string) file_get_contents(sprintf($base, 'pt-br', 'css'));
        foreach (['.c2f-lousa[data-mode="grade"] { grid-template-columns: repeat(12, minmax(0, 1fr)); }', '.c2f-lousa[data-mode="lousa"].is-arranged', 'grid-auto-rows: 20px', '.c2f-lousa-item[data-hide="lg"] { display: none; }',
            '.c2f-lousa-item.is-frameless', '.c2f-lousa-corpo { contain: layout paint; }', '.c2f-lousa-item.has-bg-image::before', '@keyframes c2f-lousa-aparece'] as $regra) {
            self::assertStringContainsString($regra, $css);
        }

        // A normalização tem uma definição só, incluída pelo painel e pelo widget.
        $painel = (string) file_get_contents(self::MODULO . 'dashboard.php');
        $layout = (string) file_get_contents(self::MODULO . 'dashboard-layout.php');
        $widget = (string) file_get_contents(self::MODULO . 'dashboard.widget.php');
        foreach (['dashboard_widgets_fontes', 'dashboard_widgets_imagem', 'dashboard_widgets_objeto_normalizar', 'dashboard_widgets_opcoes_normalizar', 'dashboard_widgets_layout_normalizar', 'dashboard_widgets_layout_ler'] as $funcao) {
            self::assertSame(1, substr_count($layout, 'function ' . $funcao . '('), $funcao);
            self::assertSame(0, substr_count($painel, 'function ' . $funcao . '('), $funcao);
        }
        self::assertStringContainsString("require_once __DIR__.'/dashboard-layout.php';", $painel);
        self::assertStringContainsString("require_once __DIR__.'/dashboard-layout.php';", $widget);
        self::assertStringNotContainsString('banco_', $layout);
    }
}
