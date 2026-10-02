<?php

declare(strict_types=1);

use Conn2Flow\Cli\Support\Docs\FunctionReferenceHtml;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/DocsTheme.php';
require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/LibraryReference.php';
require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/MarkdownRenderer.php';
require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/FunctionReferenceHtml.php';

/**
 * req-207: no site, o bloco de funções de uma biblioteca vira filtro, índice e um cartão por função.
 */
final class DocsFunctionReferenceReq207Test extends TestCase
{
    private const BLOCO = <<<'MD'
<!-- c2f:extract:start -->

Referência gerada a partir de `gestor/bibliotecas/x.php` por `c2f docs:extract` — 3 funções. Não edite dentro deste bloco.

- `x_query(string $query, array $opcoes = array('a', 'b')): mysqli_result|bool` — [linha 165](../../../../../gestor/bibliotecas/x.php#L165)
  Executa uma query no banco
  de dados.
  Parâmetros:
  - `$query`: A query SQL com `SELECT`.
  - `$opcoes`: Opções <não> escapadas.
  Retorno: O resultado ou false.
- `x_ping(): void` — [linha 200](../../../../../gestor/bibliotecas/x.php#L200)
  Verifica a conexão.
- `x_ref(&$valor, ...$resto)` — [linha 210](../../../../../gestor/bibliotecas/x.php#L210)

<!-- c2f:extract:end -->
MD;

    public function testBlocoViraDadosEstruturados(): void
    {
        $dados = FunctionReferenceHtml::parse(self::BLOCO);

        self::assertSame('gestor/bibliotecas/x.php', $dados['source']);
        self::assertSame(['x_query', 'x_ping', 'x_ref'], array_column($dados['functions'], 'name'));

        $query = $dados['functions'][0];
        self::assertSame(165, $query['line']);
        self::assertSame('mysqli_result|bool', $query['returnType']);
        self::assertSame('Executa uma query no banco de dados.', $query['description']);
        self::assertSame('O resultado ou false.', $query['returnDescription']);
        // A vírgula dentro do valor padrão não separa parâmetros.
        self::assertSame(['$query', '$opcoes'], array_column($query['params'], 'name'));
        self::assertSame(['string', 'array'], array_column($query['params'], 'type'));
        self::assertSame('A query SQL com `SELECT`.', $query['params'][0]['description']);

        self::assertSame([], $dados['functions'][1]['params']);
        self::assertSame('void', $dados['functions'][1]['returnType']);
        // Referência e variádico: o nome fica sem os prefixos; sem tipo e sem retorno declarados.
        self::assertSame(['$valor', '$resto'], array_column($dados['functions'][2]['params'], 'name'));
        self::assertSame('', $dados['functions'][2]['returnType']);
    }

    public function testHtmlTemFiltroIndiceCartaoEEscape(): void
    {
        $html = FunctionReferenceHtml::render(self::BLOCO, 'pt-br', static fn(string $href): string => 'https://repo.test/' . ltrim(str_replace('../', '', $href), '/'));

        self::assertStringContainsString('data-docs-fn-root', $html);
        self::assertStringContainsString('data-docs-fn-filter', $html);
        self::assertStringContainsString('3 de 3', $html);
        self::assertSame(3, substr_count($html, 'data-docs-fn-chip='));
        self::assertSame(3, substr_count($html, '<article id="fn-'));
        self::assertStringContainsString('id="fn-x-query"', $html);
        self::assertStringContainsString('href="#fn-x-query"', $html);
        self::assertStringContainsString('href="https://repo.test/gestor/bibliotecas/x.php#L165"', $html);
        self::assertStringContainsString('linha 165', $html);
        // Assinatura copiável pelo mesmo botão dos blocos de código.
        self::assertStringContainsString('x_query(string $query, array $opcoes = array(&#039;a&#039;, &#039;b&#039;)): mysqli_result|bool</code></pre><button', $html);
        self::assertStringContainsString('data-docs-copy', $html);
        // Código em linha vira <code>; o restante do texto é escapado.
        self::assertMatchesRegularExpression('#A query SQL com <code[^>]*>SELECT</code>\.#', $html);
        self::assertStringContainsString('Opções &lt;não&gt; escapadas.', $html);
        self::assertStringNotContainsString('`', $html);
        // Função sem descrição nem parâmetros descritos continua tendo cartão.
        self::assertStringContainsString('data-docs-fn="x_ref"', $html);
    }

    public function testRotulosEmInglesEBlocoVazio(): void
    {
        $en = FunctionReferenceHtml::render(str_replace(['linha', 'Parâmetros', 'Retorno'], ['line', 'Parameters', 'Returns'], self::BLOCO), 'en', static fn(string $h): string => $h);
        self::assertStringContainsString('3 of 3', $en);
        self::assertStringContainsString('Filter functions', $en);
        self::assertStringContainsString('view source', $en);
        self::assertMatchesRegularExpression('#>Parameters</p>#', $en);
        self::assertMatchesRegularExpression('#>Returns</p>#', $en);

        self::assertSame('', FunctionReferenceHtml::render("<!-- c2f:extract:start -->\n\nSem funções.\n\n<!-- c2f:extract:end -->", 'pt-br', static fn(string $h): string => $h));
    }
}
