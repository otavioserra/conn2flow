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

    public function testAResolucaoDeLinkRecebeAAparenciaJaResolvida(): void
    {
        // Blindagem da classe de bug que já ocorreu duas vezes neste lote: o corpo da função usa uma
        // variável que a assinatura não declara. O PHP só emite warning, e o comportamento some.
        $codigo = (string)file_get_contents(self::widget('galleries', 'galleries.widget.php'));

        foreach (['galleries_widget_resolver_link', 'galleries_widget_resolver_item_vars'] as $fn) {
            self::assertMatchesRegularExpression(
                '/function ' . $fn . '\([^)]*\$css_link_desabilitado[^)]*\)/',
                $codigo,
                $fn . ' precisa declarar $css_link_desabilitado na assinatura'
            );
        }

        // A aparência é resolvida UMA vez, fora do laço: ela não varia entre os itens, e o degrau 2
        // da cadeia consulta o banco.
        self::assertSame(
            1,
            substr_count($codigo, '= galleries_widget_css_link_desabilitado('),
            'a resolução deve acontecer uma única vez, fora do laço de itens'
        );
        self::assertStringContainsString('$cssLinkDesabilitado = $estadoSemLink[', $codigo);

        // E o CSS do recurso que forneceu as classes entra na página: classe sem regra é a mesma
        // falha silenciosa, só que um degrau adiante.
        self::assertStringContainsString('$estadoSemLink[' . "'css'" . ']', $codigo);
    }

    public function testACadeiaDeResolucaoAlcancaOTemplateDeOrigemEODefaultDoCore(): void
    {
        // O bug real: a galeria não renderiza a partir do template, e sim de uma CÓPIA congelada dele
        // em `galleries.html`, com `user_modified = 1` — que por design bloqueia o sync. Corrigir o
        // recurso não bastava, e foi por isso que a home continuou com cursor de mão.
        $codigo = (string)file_get_contents(self::widget('galleries', 'galleries.widget.php'));

        self::assertStringContainsString('function galleries_widget_css_link_desabilitado(', $codigo);

        // Degrau 2: o template de origem declarado no schema.
        self::assertStringContainsString("\$schema['template_id']", $codigo);
        // Degrau 3: o padrão do core, para o template de projeto que esquecer o marcador.
        self::assertStringContainsString("'galleries-estados'", $codigo);
    }

    public function testORecursoDeEstadosPadraoExisteEDeclaraOEstadoSemLink(): void
    {
        // Sem este recurso a cadeia perde o último degrau em silêncio: nenhuma classe seria aplicada
        // e a imagem sem link voltaria a parecer clicável.
        $encontrados = 0;

        foreach (['pt-br', 'en'] as $lang) {
            $arquivo = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
                . 'galleries' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $lang
                . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'galleries-estados'
                . DIRECTORY_SEPARATOR . 'galleries-estados.html';

            self::assertFileExists($arquivo);

            $html = (string)file_get_contents($arquivo);
            self::assertStringContainsString('<!-- link-disabled-css < -->', $html);
            self::assertStringContainsString('cursor-default', $html);
            $encontrados++;
        }

        self::assertSame(2, $encontrados);

        // `target` próprio: senão o recurso interno apareceria no dropdown de modelos do painel.
        $manifesto = json_decode((string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'galleries' . DIRECTORY_SEPARATOR . 'galleries.json'
        ), true);

        foreach (['pt-br', 'en'] as $lang) {
            $alvo = null;
            foreach ($manifesto['resources'][$lang]['templates'] as $t) {
                if (($t['id'] ?? '') === 'galleries-estados') {
                    $alvo = $t;
                }
            }
            self::assertNotNull($alvo, 'galleries-estados nao registrado em ' . $lang);
            self::assertSame('galleries-estados', $alvo['target']);
        }
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
