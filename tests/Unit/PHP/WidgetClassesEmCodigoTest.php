<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-144 (req-141) — classes de estilo montadas dentro do código dos widgets.
 *
 * A norma do projeto é que PHP e JavaScript não carreguem HTML nem classe: o markup vive em
 * RECURSOS (componentes e templates), que são o que o compilador Tailwind varre. Classe escrita em
 * código é invisível para ele — foi assim que `cursor-default`, montada em
 * `galleries.widget.php`, chegou à home do `transformamp` sem nenhuma regra CSS, sendo a única
 * utility órfã real de todo o acervo.
 *
 * O teste é estrutural porque a regressão provável é reintrodução: alguém acrescenta
 * `$css .= ' text-sm'` num widget e nada acusa até a página renderizar torta.
 */
final class WidgetClassesEmCodigoTest extends TestCase
{
    private static function widget(string $modulo, string $arquivo): string
    {
        return CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . $modulo . DIRECTORY_SEPARATOR . $arquivo;
    }

    public function testGalleriesLeAAparenciaDoTemplateENaoDoCodigo(): void
    {
        $codigo = (string)file_get_contents(self::widget('galleries', 'galleries.widget.php'));

        // A decisão (tem link ou não) é do código; a aparência desse estado é do recurso.
        self::assertStringNotContainsString(
            "' pointer-events-none cursor-default'",
            $codigo,
            'as classes do item sem link devem vir do template, não do PHP'
        );
        self::assertStringContainsString('link-disabled-css', $codigo);
    }

    public function testTodosOsTemplatesDoGalleriesDeclaramAAparenciaDoItemSemLink(): void
    {
        // Se um único template esquecer o marcador, aquele modelo de galeria volta a publicar
        // imagem sem link parecendo clicável — e em silêncio.
        $padrao = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'galleries' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . '*'
            . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.html';

        $templates = glob($padrao) ?: [];
        self::assertNotEmpty($templates, 'nenhum template de galeria encontrado');

        foreach ($templates as $arquivo) {
            $html = (string)file_get_contents($arquivo);

            // Só os templates que realmente usam o slot de classes precisam declarar o estado.
            if (strpos($html, 'link-css-classes') === false) {
                continue;
            }

            self::assertStringContainsString(
                'link-disabled-css',
                $html,
                basename($arquivo) . ' usa o slot de classes mas não declara o estado sem link'
            );
        }
    }

    public function testAResolucaoDeLinkRecebeOTemplatePorParametro(): void
    {
        // Blindagem da classe de bug que já ocorreu duas vezes neste lote: o corpo da função usa uma
        // variável que a assinatura não declara. O PHP só emite warning, e o comportamento some.
        $codigo = (string)file_get_contents(self::widget('galleries', 'galleries.widget.php'));

        self::assertMatchesRegularExpression(
            '/function galleries_widget_resolver_link\([^)]*\$template_html[^)]*\)/',
            $codigo,
            'resolver_link precisa declarar $template_html na assinatura'
        );
        self::assertMatchesRegularExpression(
            '/function galleries_widget_resolver_item_vars\([^)]*\$template_html[^)]*\)/',
            $codigo,
            'resolver_item_vars precisa declarar $template_html na assinatura'
        );

        // E a chamada precisa passar uma variável que exista no escopo de quem chama.
        self::assertStringContainsString('$publicadorCache, $html_template)', $codigo);
    }

    public function testNenhumArquivoDoCoreTemByteDeControle(): void
    {
        // Armadilha do ambiente (documentada em `c2f-shell-and-windows-traps`): heredoc de Python
        // converte `` e `\s` em BYTE DE CONTROLE dentro do arquivo gerado. O PHP não reclama, o
        // lint passa, e a regex silenciosamente deixa de casar — foi assim que
        // `/<input[^>]*>/` virou `/<input<0x08>[^>]*>/` e o campo de senha perdeu o botão.
        $alvos = array_merge(
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . '*'
                . DIRECTORY_SEPARATOR . '*.php') ?: [],
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . '*.php') ?: [],
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores' . DIRECTORY_SEPARATOR . 'agents'
                . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR . '*.php') ?: []
        );

        self::assertNotEmpty($alvos);

        $suspeitos = [];
        foreach ($alvos as $arquivo) {
            $conteudo = (string)file_get_contents($arquivo);

            // Tab (0x09), LF (0x0A) e CR (0x0D) são legítimos; o resto abaixo de 0x20 não é.
            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $conteudo)) {
                $suspeitos[] = basename($arquivo);
            }
        }

        self::assertSame([], $suspeitos, 'byte de controle encontrado em: ' . implode(', ', $suspeitos));
    }

    public function testNenhumWidgetDeclaraFontesTailwindApontandoParaCodigo(): void
    {
        // `tailwind_sources` para `.php`/`.js` é o remendo que mantém viva a classe em código: o
        // compilador passa a varrer o próprio código-fonte em vez do recurso. Deve desaparecer à
        // medida que os widgets forem corrigidos.
        $manifestos = glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . 'galleries' . DIRECTORY_SEPARATOR . '*.json') ?: [];

        // Sem esta asserção o teste passaria "vazio" caso o manifesto sumisse — e deixaria de
        // vigiar exatamente o que existe para vigiar.
        self::assertNotEmpty($manifestos, 'manifesto do galleries não encontrado');

        foreach ($manifestos as $arquivo) {
            $dados = json_decode((string)file_get_contents($arquivo), true);
            if (!is_array($dados)) {
                continue;
            }

            $texto = json_encode($dados);
            if (!is_string($texto) || strpos($texto, 'tailwind_sources') === false) {
                continue;
            }

            self::assertDoesNotMatchRegularExpression(
                '/"tailwind_sources":\s*\[[^\]]*\.(php|js)"/',
                $texto,
                basename($arquivo) . ': galleries não deve mais depender de varrer o próprio código'
            );
        }
    }
}
