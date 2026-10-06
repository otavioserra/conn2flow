<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'gestor.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'interface.php';

/**
 * req-190 — botões de cabeçalho/rodapé da interface no modo Tailwind.
 *
 * Com a página em Tailwind puro o Fomantic não é carregado: `ui button` sairia sem estilo nenhum.
 * O contrato não muda (o `excluir` leva a URL em `data-href`, `callback` vira classe), só o markup.
 */
final class InterfaceBotoesTailwindTest extends TestCase
{
    private array $gestorAntes = [];

    protected function setUp(): void
    {
        global $_GESTOR;
        $this->gestorAntes = is_array($_GESTOR ?? null) ? $_GESTOR : [];
    }

    protected function tearDown(): void
    {
        global $_GESTOR;
        $_GESTOR = $this->gestorAntes;
    }

    private function modo(?string $layout, ?string $pagina): void
    {
        global $_GESTOR;
        $_GESTOR['layout#framework_css'] = $layout;
        $_GESTOR['pagina#framework_css'] = $pagina;
    }

    private function botoes(): array
    {
        return [
            'adicionar' => ['url' => '/produtos/add/', 'rotulo' => 'Incluir', 'tooltip' => 'Incluir "novo"', 'icon' => 'plus circle', 'cor' => 'blue'],
            'status' => ['url' => '/produtos/?id=7', 'rotulo' => 'Desativar', 'icon' => 'eye', 'cor' => 'basic green'],
            'excluir' => ['url' => '/produtos/?id=7', 'rotulo' => 'Excluir', 'icon' => 'trash alternate', 'cor' => 'red'],
        ];
    }

    public function testFomanticMantemOMarkupLegado(): void
    {
        $this->modo('fomantic-ui', null);

        $html = interface_botoes_cabecalho(['botoes' => $this->botoes()]);

        self::assertStringContainsString('class="ui button blue"', $html);
        self::assertStringContainsString('<div class="ui button excluir red" data-href="/produtos/?id=7"', $html);
        self::assertStringNotContainsString('data-lucide', $html);
    }

    public function testTailwindEmiteUtilitiesEIconeLucide(): void
    {
        $this->modo('tailwindcss', 'tailwindcss');

        $html = interface_botoes_cabecalho(['botoes' => $this->botoes()]);

        self::assertStringNotContainsString('ui button', $html);
        self::assertStringContainsString('<i data-lucide="circle-plus" class="size-4"></i><span>Incluir</span>', $html);
        self::assertStringContainsString('bg-sky-600', $html);
        // `basic` vence a cor: é o botão neutro.
        self::assertMatchesRegularExpression('/<a class="[^"]*bg-white[^"]*" href="\/produtos\/\?id=7"/', $html);
    }

    public function testExcluirTailwindEhBotaoComDataHrefParaOModal(): void
    {
        $this->modo('tailwindcss', null);

        $html = interface_botoes_rodape(['botoes_rodape' => $this->botoes()]);

        self::assertMatchesRegularExpression('/<button type="button" class="excluir [^"]*bg-red-600[^"]*" data-href="\/produtos\/\?id=7"/', $html);
        self::assertStringContainsString('data-lucide="trash-2"', $html);
    }

    public function testTooltipAusenteNaoGeraAvisoEEscapaAspas(): void
    {
        $this->modo('tailwindcss', null);

        $html = interface_botoes_cabecalho(['botoes' => $this->botoes()]);

        // req-219: dica da biblioteca (data-c2f-dica), não o title nativo
        self::assertStringContainsString('data-c2f-dica="Incluir &quot;novo&quot;"', $html);
        // REQ-243: botão declarado sem dica usa o rótulo; dica vazia desenhava um balão sem texto.
        self::assertStringNotContainsString('data-c2f-dica=""', $html);
        self::assertStringContainsString('data-c2f-dica="Desativar"', $html);
        self::assertStringContainsString('data-c2f-dica="Excluir"', $html);
        self::assertStringNotContainsString(' title=', $html);
    }

    public function testIconeSemTraducaoSaiSoComRotulo(): void
    {
        self::assertSame('', interface_botao_tailwind_icone('bizarre unknown'));
        self::assertSame('eye-off', interface_botao_tailwind_icone(' Eye Slash '));
    }

    public function testClassesDaPaletaEstaoDeclaradasNosComponentes(): void
    {
        // Os botões são montados em PHP: as utilities só chegam ao CSS pré-compilado se estiverem no
        // `<template data-c2f-botoes>` dos dois formulários Tailwind, nos dois idiomas.
        $classes = [];
        foreach (['blue', 'green', 'red', 'basic'] as $cor) {
            foreach (explode(' ', interface_botao_tailwind_classes($cor)) as $classe) $classes[$classe] = true;
        }
        $classes['size-4'] = true;

        foreach (['pt-br', 'en'] as $lang) {
            foreach (['interface-formulario-edicao-tailwind', 'interface-formulario-inclusao-tailwind'] as $id) {
                $arquivo = CONN2FLOW_GESTOR_ROOT . "/resources/{$lang}/components/{$id}/{$id}.html";
                $html = (string) file_get_contents($arquivo);
                self::assertStringContainsString('data-c2f-botoes', $html, $arquivo);
                $usadas = array_flip(gestor_css_classes_usadas($html));
                foreach (array_keys($classes) as $classe) {
                    self::assertArrayHasKey($classe, $usadas, "{$classe} ausente em {$arquivo}");
                }
            }
        }
    }

    public function testInclusaoGanhaVarianteTailwind(): void
    {
        self::assertSame('interface-formulario-inclusao-tailwind', interface_componente_variante('interface-formulario-inclusao', 'tailwindcss'));
    }
}
