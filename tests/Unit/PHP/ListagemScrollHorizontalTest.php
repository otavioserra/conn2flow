<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * req-147 — a listagem rola na horizontal em vez de colapsar, em tela larga.
 *
 * O `responsive` do DataTables esconde colunas assim que a tabela não cabe e devolve o conteúdo
 * atrás de um botão "+", registro a registro. Numa tela de 1200px ou mais isso tira da vista dados
 * que caberiam com um arrasto lateral — e com campos longos o efeito piora, porque a tabela colapsa
 * cedo justamente quando há mais o que ver.
 *
 * Medido em navegador real (Playwright), em `/admin-paginas/`:
 *   1400px -> classe aplicada, overflow-x auto, tabela 2819px em caixa de 1092px, ZERO botão "+"
 *    900px -> classe ausente, colapso preservado, 25 botões "+"
 *
 * As checagens são estruturais porque o mecanismo tem DOIS lados (JS aplica a classe, CSS reage a
 * ela): mexer em um só devolve a listagem espremida sem nenhum erro visível.
 */
final class ListagemScrollHorizontalTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function interfaces(): array
    {
        return [
            'interface' => ['interface/interface.js', 'interface/interface.css'],
            'interface-v2' => ['interface-v2/interface-v2.js', 'interface-v2/interface-v2.css'],
        ];
    }

    /**
     * O JavaScript sem os comentarios de linha.
     *
     * Asserir "nao contem X" sobre o arquivo inteiro bate no proprio comentario que EXPLICA a
     * mudanca de X — o teste falha por o codigo estar bem documentado, nao por estar errado.
     */
    private static function jsSemComentarios(string $relativo): string
    {
        return (string)preg_replace('#^\s*//.*$#m', '', self::asset($relativo));
    }

    private static function asset(string $relativo): string
    {
        return (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativo)
        );
    }

    #[DataProvider('interfaces')]
    public function testOResponsiveEDesligadoAcimaDoLimiar(string $js): void
    {
        $codigo = self::asset($js);

        self::assertStringContainsString('preferirScrollHorizontal', $codigo);
        self::assertStringContainsString('responsive: preferirScrollHorizontal ? false :', $codigo);
        self::assertStringContainsString('window.innerWidth >= listagemLarguraSemColapso', $codigo);
    }

    #[DataProvider('interfaces')]
    public function testOLimiarTemPadraoEPodeSerConfigurado(string $js): void
    {
        // 1200px é o padrão; instalações com telas menores podem baixar o limiar sem tocar no código.
        $codigo = self::asset($js);

        self::assertStringContainsString('gestor.listagemLarguraSemColapso', $codigo);
        self::assertStringContainsString('1200', $codigo);
    }

    #[DataProvider('interfaces')]
    public function testAClasseEAplicadaNoEventoDeInitDoDataTables(string $js): void
    {
        // `init.dt` é o único momento em que o wrapper já existe no DOM. Aplicar antes não encontra
        // o elemento; aplicar depois, fora do evento, depende de a criação ter sido síncrona.
        $codigo = self::asset($js);

        self::assertStringContainsString("on('init.dt'", $codigo);
        self::assertStringContainsString("$('body').addClass('listagem-scroll-horizontal')", $codigo);

        // Quem rola e a JANELA, nao uma caixa em volta da tabela. A barra de uma caixa nasce no
        // rodape DELA: numa listagem longa, quem nao conhece o sistema precisava descer a pagina
        // inteira para descobrir que havia rolagem lateral.
        self::assertStringNotContainsString('.wrap(', self::jsSemComentarios($js));
    }

    #[DataProvider('interfaces')]
    public function testOCssReageAClasseQueOJsAplica(string $js, string $css): void
    {
        // Os dois lados precisam concordar no nome: renomear um deles devolve a listagem espremida
        // sem erro nenhum no console.
        $folha = self::asset($css);

        self::assertStringContainsString('body.listagem-scroll-horizontal', $folha);

        // `visible` nos ancestrais e o que PERMITE o estouro. Um `hidden` em qualquer um deles corta
        // a tabela em vez de deixar rolar, e o efeito e uma listagem truncada sem nenhum aviso.
        self::assertStringContainsString('overflow-x: visible', $folha);
        self::assertStringNotContainsString('overflow-x: auto', $folha);
    }

    #[DataProvider('interfaces')]
    public function testAColunaDeAcoesFicaAncoradaNaBordaEsquerda(string $js, string $css): void
    {
        // Sem `sticky`, rolar para a direita levaria embora justamente a coluna que foi movida para
        // a frente para ficar sempre alcancavel.
        $folha = self::asset($css);

        self::assertStringContainsString('position: sticky', $folha);
        self::assertStringContainsString('left: 0', $folha);
    }

    #[DataProvider('interfaces')]
    public function testOColunaDeOpcoesEAPrimeiraNoRenderizador(string $js): void
    {
        // A coluna de opcoes passou para a primeira posicao (`interface.php`), entao o renderizador
        // dos botoes e a ocultacao quando nao ha acoes seguem junto — eram `-1`.
        $codigo = self::jsSemComentarios($js);

        self::assertStringNotContainsString('targets: -1', $codigo);
        self::assertStringNotContainsString('column(-1)', $codigo);
        self::assertStringContainsString('column(0)', $codigo);
    }

    #[DataProvider('interfaces')]
    public function testODestaqueUsaAPaletaDaMarca(string $js, string $css): void
    {
        // As duas cores saem do proprio logo (`assets/images/logo-principal.png`), medidas por
        // contagem de pixels: ciano #18a8c0 (14.283 px) e azul-marinho #182840 (4.529 px). Ficam em
        // variaveis para que trocar a identidade seja uma edicao so.
        $folha = self::asset($css);

        self::assertStringContainsString('--c2f-marca-ciano: #18a8c0', $folha);
        self::assertStringContainsString('--c2f-marca-navy: #182840', $folha);

        // E o destaque REALMENTE usa as variaveis, em vez de repetir o hex.
        self::assertStringContainsString('var(--c2f-marca-ciano-claro)', $folha);
        self::assertStringContainsString('var(--c2f-marca-ciano-veu)', $folha);
    }

    #[DataProvider('interfaces')]
    public function testOsBotoesSeguemBrancosComHoverDaMarca(string $js, string $css): void
    {
        // A coluna inteira ja e a area destacada; pintar os botoes competiria com os icones, que sao
        // o que o operador procura. O destaque deles vem no hover.
        $folha = self::asset($css);

        $trecho = substr($folha, (int)strpos($folha, 'td:first-child .ui.button'));

        self::assertStringContainsString('background: #fff', $trecho);
        self::assertStringContainsString(':hover', $trecho);
        self::assertStringContainsString('var(--c2f-marca-ciano)', $trecho);
        self::assertStringContainsString('transform: translateY(-1px)', $trecho);
    }

    #[DataProvider('interfaces')]
    public function testOFundoDaColunaEOpaco(string $js, string $css): void
    {
        // Com a tabela rolada, o conteudo das outras colunas passa POR BAIXO da coluna ancorada. Um
        // fundo `rgba` ou `transparent` deixaria o texto atravessar, e o efeito e ilegivel.
        $folha = self::asset($css);

        $ini = (int)strpos($folha, 'table.dataTable > tbody > tr > td:first-child {');
        $trecho = substr($folha, $ini, 200);

        self::assertStringContainsString('background: var(--c2f-marca-ciano-veu)', $trecho);
        self::assertStringNotContainsString('transparent', $trecho);
    }

    #[DataProvider('interfaces')]
    public function testATabelaGanhaLarguraRealEmVezDeSeEspremer(string $js, string $css): void
    {
        $folha = self::asset($css);

        // `width: auto` derruba o `width: 100%` inline do DataTables — sem isso a tabela se espreme
        // para caber e o scroll nunca aparece.
        self::assertStringContainsString('width: auto !important', $folha);
        self::assertStringContainsString('min-width: 100%', $folha);

        // `nowrap` é o que dá largura real às colunas: sem ele o texto quebra, a tabela volta a caber
        // e o scroll fica inútil — que é justamente o caso dos campos longos.
        self::assertStringContainsString('white-space: nowrap', $folha);
    }
}
