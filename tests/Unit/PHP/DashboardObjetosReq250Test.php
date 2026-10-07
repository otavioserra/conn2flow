<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-250 — fase 1 da lousa: objetos livres, esconder por largura, imagem de fundo e Google Fonts.
 * O servidor só guarda, no layout publicado, atributos conhecidos e dentro das listas.
 */
final class DashboardObjetosReq250Test extends TestCase
{
    private function fonte(): string
    {
        return (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/dashboard.php');
    }

    /** Roda as funções da área de widgets num processo à parte e devolve `$saida`. */
    private function rodar(string $codigo, array $entrada)
    {
        // REQ-252: a normalização mora em `dashboard-layout.php`, que o painel e o widget público incluem.
        $script = "<?php\n\$entrada=" . var_export($entrada, true) . ";\nrequire " . var_export(CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/dashboard-layout.php', true) . ";\n" . $codigo . "\necho json_encode(\$saida);";
        $arquivo = tempnam(sys_get_temp_dir(), 'req250-');
        try {
            file_put_contents($arquivo, $script);
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo) . ' 2>&1', $linhas, $status);
            self::assertSame(0, $status, implode("\n", $linhas));
            return json_decode(implode("\n", $linhas), true, 512, JSON_THROW_ON_ERROR);
        } finally {
            unlink($arquivo);
        }
    }

    public function testListaDeFontesIgualNoServidorENoCliente(): void
    {
        $js = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/dashboard.js');
        self::assertSame(1, preg_match('/var FONTS = (\[[^\]]+\]);/', $js, $m));
        $cliente = json_decode(str_replace("'", '"', $m[1]), true);
        self::assertSame($cliente, $this->rodar('$saida=dashboard_widgets_fontes();', []));
        self::assertCount(13, $cliente);
    }

    public function testOpcoesNovasFicamDentroDasListas(): void
    {
        $boas = $this->rodar('$saida=dashboard_widgets_opcoes_normalizar($entrada);', ['hide' => 'md', 'bgImage' => '/files/2026/fundo.webp', 'bgOpacity' => '35', 'bgFit' => 'repeat', 'titleFont' => 'Playfair Display']);
        self::assertSame(['md', '/files/2026/fundo.webp', 35, 'repeat', 'Playfair Display'], [$boas['hide'], $boas['bgImage'], $boas['bgOpacity'], $boas['bgFit'], $boas['titleFont']]);

        $ruins = $this->rodar('$saida=dashboard_widgets_opcoes_normalizar($entrada);', ['hide' => 'xl', 'bgImage' => 'https://evil.example/x.png', 'bgOpacity' => 'muito', 'bgFit' => 'explode', 'titleFont' => 'Comic Sans']);
        self::assertSame(['', '', 100, 'cover', ''], [$ruins['hide'], $ruins['bgImage'], $ruins['bgOpacity'], $ruins['bgFit'], $ruins['titleFont']]);
        self::assertSame(100, $this->rodar('$saida=dashboard_widgets_opcoes_normalizar($entrada);', ['bgOpacity' => 900])['bgOpacity']);
        self::assertSame(0, $this->rodar('$saida=dashboard_widgets_opcoes_normalizar($entrada);', ['bgOpacity' => -5])['bgOpacity']);
    }

    public function testImagemSoDoProprioPainel(): void
    {
        $casos = [
            '/files/2026/a.png' => '/files/2026/a.png',
            '/contents/files/Foto%20final.JPEG' => '/contents/files/Foto%20final.JPEG',
            'files/a.png' => '',
            '//evil.example/a.png' => '',
            'https://evil.example/a.png' => '',
            '/files/../../etc/passwd.png' => '',
            '/files/a.png") , url("x' => '',
            '/files/a.php' => '',
            '/files/a.png?x=1' => '',
        ];
        $saida = $this->rodar('$saida=array_map("dashboard_widgets_imagem", $entrada);', array_keys($casos));
        self::assertSame(array_values($casos), $saida);
    }

    public function testObjetoNormalizado(): void
    {
        $botao = $this->rodar('$saida=dashboard_widgets_objeto_normalizar($entrada);', ['type' => 'button', 'text' => str_repeat('a', 3000), 'font' => 'Oswald', 'size' => 22, 'weight' => 400, 'align' => 'right',
            'color' => '#FFFFFF', 'fill' => '#0284c7', 'href' => 'https://conn2flow.com/planos?x=1', 'newTab' => true, 'intruso' => '<script>']);
        self::assertSame(['type' => 'button', 'text' => str_repeat('a', 2000), 'font' => 'Oswald', 'size' => 22, 'weight' => 400, 'align' => 'right', 'color' => '#ffffff', 'fill' => '#0284c7',
            'shape' => 'rounded', 'src' => '', 'fit' => 'cover', 'alt' => '', 'icon' => 'star', 'href' => 'https://conn2flow.com/planos?x=1', 'newTab' => true], $botao);

        $ruim = $this->rodar('$saida=dashboard_widgets_objeto_normalizar($entrada);', ['type' => 'video', 'font' => 'Papyrus', 'size' => 9999, 'weight' => 'gordo', 'align' => 'meio', 'color' => 'red', 'fill' => 'url(x)',
            'shape' => 'estrela', 'src' => 'javascript:alert(1)', 'icon' => '"><svg', 'href' => 'javascript:alert(1)', 'newTab' => 'sim']);
        self::assertSame(['text', '', 28, 700, 'center', '#0f172a', '#0ea5e9', 'rounded', '', 'star', '', false],
            [$ruim['type'], $ruim['font'], $ruim['size'], $ruim['weight'], $ruim['align'], $ruim['color'], $ruim['fill'], $ruim['shape'], $ruim['src'], $ruim['icon'], $ruim['href'], $ruim['newTab']]);
        foreach (['//evil.example/', 'data:text/html,x', '/ok/caminho/'] as $destino) {
            $href = $this->rodar('$saida=dashboard_widgets_objeto_normalizar($entrada);', ['type' => 'button', 'href' => $destino])['href'];
            self::assertSame($destino === '/ok/caminho/' ? $destino : '', $href, $destino);
        }
    }

    public function testLayoutPublicadoGuardaObjetoEWidgetComum(): void
    {
        $layout = [
            ['id' => 'objeto', 'name' => 'Objeto', 'registro_id' => 'qualquer', 'params' => ['x' => 'y'], 'width' => 5, 'x' => 2, 'y' => 3, 'object' => ['type' => 'image', 'src' => '/files/a.png', 'fit' => 'contain'], 'options' => ['hide' => 'sm', 'header' => false]],
            ['id' => 'menus', 'registro_id' => 'main', 'object' => ['type' => 'text', 'text' => 'não é objeto']],
        ];
        $saida = $this->rodar('$saida=dashboard_widgets_layout_normalizar($entrada);', $layout);
        self::assertSame(['objeto', '', [], 5, 2, 3], [$saida[0]['id'], $saida[0]['registro_id'], $saida[0]['params'], $saida[0]['width'], $saida[0]['x'], $saida[0]['y']]);
        self::assertSame(['image', '/files/a.png', 'contain'], [$saida[0]['object']['type'], $saida[0]['object']['src'], $saida[0]['object']['fit']]);
        self::assertSame(['sm', false], [$saida[0]['options']['hide'], $saida[0]['options']['header']]);
        self::assertArrayNotHasKey('object', $saida[1]);
        self::assertSame('main', $saida[1]['registro_id']);
    }

    public function testComponenteTemOsCamposNosDoisIdiomas(): void
    {
        foreach (['pt-br', 'en'] as $lang) {
            $base = CONN2FLOW_GESTOR_ROOT . "/modulos/dashboard/resources/$lang/components/dashboard-cards-tailwind/dashboard-cards-tailwind";
            $html = (string) file_get_contents($base . '.html');
            foreach (['hide', 'bgImage', 'bgOpacity', 'bgFit', 'titleFont'] as $opcao) self::assertStringContainsString('data-widget-option="' . $opcao . '"', $html, "$lang $opcao");
            foreach (['type', 'text', 'font', 'size', 'weight', 'align', 'color', 'fill', 'shape', 'src', 'fit', 'alt', 'icon', 'href', 'newTab'] as $campo) {
                // Um controle por atributo (o seletor de imagem cita o nome do campo no botão, por isso a contagem é de controles).
                self::assertSame(1, preg_match_all('/<(?:input|select|textarea)\b[^>]*\sdata-object-option="' . $campo . '"/', $html), "$lang $campo");
            }
            // Adicionar objeto e o seletor de imagem ficam dentro dos blocos de quem administra.
            $semAdmin = preg_replace('/<!-- (widgets-(?:menu|aviso|vazio|modais)-admin) < -->[\s\S]*?<!-- \1 > -->/', '', $html);
            foreach (['dashboard-btn-add-object', 'dashboard-image-picker', 'data-object-section'] as $controle) {
                self::assertStringContainsString($controle, $html, "$lang $controle");
                self::assertStringNotContainsString($controle, $semAdmin, "$lang $controle");
            }
            $css = (string) file_get_contents($base . '.css');
            foreach (['.dashboard-widget-card.is-hidden-now', '.dashboard-widget-card.has-bg-image::before', '.dashboard-object-action', '#dashboard-widgets-grid.is-editing .dashboard-object-action { pointer-events: none; }'] as $regra) {
                self::assertStringContainsString($regra, $css, "$lang $regra");
            }
        }
    }
}
