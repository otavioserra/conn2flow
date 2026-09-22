<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-173 / BATCH-178 — query string preservada no redirecionamento de `paginas_301`.
 *
 * A tabela `paginas_301` existe para aposentar caminhos sem perder quem chega por eles. O roteador
 * chamava `gestor_roteador_erro()` sem `querystring`, então `/pro-checkout/?plan=starter&utm_source=ads`
 * caía no destino sem plano e sem UTM: o visitante via uma página genérica e a campanha perdia a
 * origem. O status HTTP nunca foi o problema (já era 301) — o que se perdia era o contexto.
 *
 * O que este teste protege:
 *
 * 1. A montagem da URL de destino, isolada em `gestor_redirecionar_montar_url()` justamente para ser
 *    verificável sem `header()`/`exit`.
 * 2. Destino que já traz `?`: concatenar outro `?` produz um parâmetro com valor sujo, e o navegador
 *    não reclama — o erro só aparece do lado de quem lê o parâmetro.
 * 3. A ausência de `?` órfão quando não há query string, exigida pelo critério de aceite.
 * 4. O repasse de `querystring` no ramo 301 do roteador: sem ele, a função acima nunca recebe nada.
 */
final class Req173Redirecionamento301Test extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'gestor.php';
    }

    public function testQueryStringSimplesEPreservada(): void
    {
        self::assertSame(
            '/subscription/?plan=starter',
            gestor_redirecionar_montar_url('/subscription/', 'plan=starter')
        );
    }

    public function testQueryStringCompostaComUtmsEPreservadaNaOrdem(): void
    {
        $query = 'plan=starter&utm_source=adwords&utm_medium=cpc&ref=abc';

        self::assertSame(
            '/subscription/?' . $query,
            gestor_redirecionar_montar_url('/subscription/', $query)
        );
    }

    public function testSemQueryStringNaoDeixaInterrogacaoOrfa(): void
    {
        self::assertSame('/subscription/', gestor_redirecionar_montar_url('/subscription/', ''));
        self::assertSame('/subscription/', gestor_redirecionar_montar_url('/subscription/'));
    }

    public function testDestinoQueJaTemParametrosRecebeEComercial(): void
    {
        // `...?a=1?b=2` não é erro de sintaxe para o navegador: ele manda o segundo `?` como parte
        // do VALOR do primeiro parâmetro, e quem lê recebe lixo silenciosamente.
        self::assertSame(
            '/subscription-checkout/?plan=starter&utm_source=ads',
            gestor_redirecionar_montar_url('/subscription-checkout/?plan=starter', 'utm_source=ads')
        );
    }

    public function testPrefixosDeQueryStringNaoSaoDuplicados(): void
    {
        self::assertSame(
            '/destino/?a=1',
            gestor_redirecionar_montar_url('/destino/', '?a=1')
        );
        self::assertSame(
            '/destino/?x=1&a=1',
            gestor_redirecionar_montar_url('/destino/?x=1', '&a=1')
        );
    }

    public function testUrlExternaTambemRecebeAConcatenacaoCorreta(): void
    {
        self::assertSame(
            'https://exemplo.com/pagina?a=1&b=2',
            gestor_redirecionar_montar_url('https://exemplo.com/pagina?a=1', 'b=2')
        );
    }

    public function testRoteadorRepassaQueryStringNoRamo301(): void
    {
        $roteador = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'gestor.php');

        $inicio = strpos($roteador, 'function gestor_roteador_301_ou_404(');
        self::assertNotFalse($inicio, 'Roteador de 301/404 não encontrado.');

        $fim = strpos($roteador, "\nfunction ", $inicio + 10);
        $corpo = substr($roteador, $inicio, ($fim === false ? null : $fim - $inicio));

        $posCodigo = strpos($corpo, "'codigo' => 301");
        self::assertNotFalse($posCodigo, 'O ramo de redirecionamento 301 sumiu do roteador.');

        // A chave precisa estar na MESMA chamada do 301: `gestor_roteador_erro()` lê `querystring`
        // do array que recebe, e um `true` solto noutro ponto do arquivo não teria efeito.
        $chamada = substr($corpo, $posCodigo, 220);
        self::assertStringContainsString("'querystring' => true", $chamada);
    }
}
