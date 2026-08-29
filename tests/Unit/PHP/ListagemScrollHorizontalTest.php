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
        self::assertStringContainsString('.wrap(', $codigo);
        self::assertStringContainsString('listagem-scroll-horizontal', $codigo);

        // A caixa envolve APENAS a tabela. Aplicar o overflow ao wrapper trazia junto um scroll
        // VERTICAL — em CSS nao existe rolar num eixo so — que engolia o scroll da pagina, e ainda
        // prendia busca e paginacao dentro da caixa.
        self::assertStringNotContainsString(
            "closest('.dataTables_wrapper').addClass('listagem-scroll-horizontal')",
            $codigo
        );
    }

    #[DataProvider('interfaces')]
    public function testOCssReageAClasseQueOJsAplica(string $js, string $css): void
    {
        // Os dois lados precisam concordar no nome: renomear um deles devolve a listagem espremida
        // sem erro nenhum no console.
        $folha = self::asset($css);

        self::assertStringContainsString('.listagem-scroll-horizontal', $folha);
        self::assertStringContainsString('overflow-x: auto', $folha);

        // O eixo vertical precisa ser explicito: com `visible` o navegador o converte em `auto` e a
        // tabela ganha um scroll vertical proprio, que rouba o scroll da pagina.
        self::assertStringContainsString('overflow-y: hidden', $folha);
        self::assertStringNotContainsString('overflow-y: visible', $folha);
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
