<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'gestor.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'interface.php';

/**
 * req-118 (BATCH-119) — resolução do framework CSS a partir de layout + página.
 *
 * Uma página final é layout + página, e as DUAS carregam `framework_css`. Esta função decide quais
 * bibliotecas entram no `<head>` e qual variante de componente a interface entrega. Errar aqui não
 * gera erro nenhum: gera tela sem CSS, ou tela com um modal Fomantic sem o JS que o abre — que, no
 * caso do modal de Área Restrita, é uma trava de credenciais que some da frente do usuário.
 */
final class FrameworkCssResolverTest extends TestCase
{
    // ===== Legado: nada declarado continua Fomantic

    public function testNenhumDosDoisDeclaraNadaCaiNoLegado(): void
    {
        $resolucao = gestor_framework_css_resolver(null, null);

        self::assertTrue($resolucao['fomantic']);
        self::assertFalse($resolucao['tailwind']);
        self::assertSame('fomantic-ui', $resolucao['modo']);
    }

    public function testStringVaziaEquivaleANaoDeclarado(): void
    {
        self::assertSame('fomantic-ui', gestor_framework_css_resolver('', '')['modo']);
    }

    // ===== Tailwind puro

    public function testLayoutEPaginaTailwindResultamEmTailwindPuro(): void
    {
        $resolucao = gestor_framework_css_resolver('tailwindcss', 'tailwindcss');

        self::assertFalse($resolucao['fomantic']);
        self::assertTrue($resolucao['tailwind']);
        self::assertSame('tailwindcss', $resolucao['modo']);
    }

    public function testLayoutTailwindComPaginaSemDeclaracaoContinuaTailwindPuro(): void
    {
        // Página sem declaração NÃO reintroduz o Fomantic: quem manda na moldura é o layout, e ele
        // já disse que não tem Fomantic algum para oferecer.
        self::assertSame('tailwindcss', gestor_framework_css_resolver('tailwindcss', null)['modo']);
    }

    // ===== Híbrido: um lado ainda depende do Fomantic

    public function testPaginaTailwindSobLayoutFomanticEhHibrido(): void
    {
        // É o cenário do painel legado: o layout desenha menu, topo e modais em Fomantic, então ele
        // continua obrigatório mesmo com a página escrita em utilities.
        $resolucao = gestor_framework_css_resolver('fomantic-ui', 'tailwindcss');

        self::assertTrue($resolucao['fomantic']);
        self::assertTrue($resolucao['tailwind']);
        self::assertSame('hibrido', $resolucao['modo']);
    }

    public function testLayoutTailwindComPaginaFomanticEhHibrido(): void
    {
        self::assertSame('hibrido', gestor_framework_css_resolver('tailwindcss', 'fomantic-ui')['modo']);
    }

    public function testEspacosEmVoltaNaoMudamADecisao(): void
    {
        self::assertSame('tailwindcss', gestor_framework_css_resolver('  tailwindcss  ', null)['modo']);
    }

    public function testValorDesconhecidoNaoLigaNenhumDosDois(): void
    {
        // Framework não suportado não pode ligar o Tailwind por engano; e como o layout declarou
        // algo, o fallback de "nada declarado" também não vale.
        $resolucao = gestor_framework_css_resolver('bootstrap', null);

        self::assertFalse($resolucao['tailwind']);
        self::assertFalse($resolucao['fomantic']);
    }

    // ===== Variante de componente

    public function testVarianteEhAplicadaApenasEmTailwindPuro(): void
    {
        self::assertSame(
            'interface-formulario-edicao-tailwind',
            interface_componente_variante('interface-formulario-edicao', 'tailwindcss')
        );
    }

    public function testHibridoMantemOComponenteLegado(): void
    {
        // Com Fomantic na página, o componente legado é o único com estilo garantido.
        self::assertSame(
            'interface-formulario-edicao',
            interface_componente_variante('interface-formulario-edicao', 'hibrido')
        );
    }

    public function testFomanticMantemOComponenteLegado(): void
    {
        self::assertSame(
            'interface-carregando-modal',
            interface_componente_variante('interface-carregando-modal', 'fomantic-ui')
        );
    }

    public function testVarianteNaoEhAplicadaDuasVezes(): void
    {
        self::assertSame(
            'interface-alerta-modal-tailwind',
            interface_componente_variante('interface-alerta-modal-tailwind', 'tailwindcss')
        );
    }

    public function testCanonicoRemoveApenasOSufixoDeVariante(): void
    {
        self::assertSame('interface-alerta-modal', interface_componente_canonico('interface-alerta-modal-tailwind'));
        self::assertSame('interface-alerta-modal', interface_componente_canonico('interface-alerta-modal'));
    }

    public function testCanonicoNaoAmputaIdQueApenasTerminaComTailwind(): void
    {
        // Sem a checagem do hífen, `layout-iframe-tailwindcss` viraria `layout-iframe-tailwind` e o
        // componente carregado seria outro.
        self::assertSame('layout-iframe-tailwindcss', interface_componente_canonico('layout-iframe-tailwindcss'));
    }
}
