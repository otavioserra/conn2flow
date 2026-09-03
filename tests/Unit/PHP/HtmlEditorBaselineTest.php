<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'html-editor.php';

/**
 * req-117 (BATCH-117) — baseline de CSS pré-compilado do editor.
 *
 * O baseline é o que a captura do `css_compiled` usa para gravar apenas o DELTA. Antes deste lote ele
 * era só o pré-compilado do próprio recurso — e página criada pelo usuário no gestor nunca tem esse
 * valor, porque `tailwind_recursos_descobrir()` varre arquivos físicos em `resources/` e ela vive
 * apenas no banco. Resultado medido na página `sobre` do photon: `@layer theme` e o Preflight inteiro
 * regravados por página, 62% de duplicata do que o layout já entrega.
 *
 * Os caminhos que dependem do banco (leitura de `layouts.css_precompiled`) ficam para a validação em
 * runtime; aqui estão a ordem da cascata e as saídas antecipadas, que são o que decide o resultado.
 */
final class HtmlEditorBaselineTest extends TestCase
{
    // ===== Ordem da cascata

    public function testLayoutVemAntesDoRecursoComoEmGestorCssPrecompiledOrdenar(): void
    {
        self::assertSame(
            "LAYOUT\nRECURSO",
            html_editor_css_precompiled_concatenar('LAYOUT', 'RECURSO')
        );
    }

    public function testSemLayoutDevolveApenasORecurso(): void
    {
        self::assertSame('RECURSO', html_editor_css_precompiled_concatenar('', 'RECURSO'));
    }

    public function testSemRecursoDevolveApenasOLayout(): void
    {
        // É o caso comum: página criada pelo usuário, que nunca passa pelo build offline.
        self::assertSame('LAYOUT', html_editor_css_precompiled_concatenar('LAYOUT', ''));
    }

    public function testSemNenhumaCamadaDevolveVazio(): void
    {
        // Baseline vazio significa "não filtre nada" — a página grava o output completo do Tailwind
        // e continua autossuficiente. É a política aprovada para instalação sem cascata offline.
        self::assertSame('', html_editor_css_precompiled_concatenar('', ''));
    }

    // ===== Saídas antecipadas (não tocam o banco)

    public function testPaginaSemLayoutNaoConsultaOBanco(): void
    {
        self::assertSame('RECURSO', html_editor_css_precompiled_baseline([
            'css_precompiled' => 'RECURSO',
            'layout_id' => '',
            'alvo' => 'paginas',
        ]));
    }

    public function testEditandoOProprioLayoutOBaselineEhELePropio(): void
    {
        // Não há camada acima de um layout: o pré-compilado dele já É a base.
        self::assertSame('LAYOUT', html_editor_css_precompiled_baseline([
            'css_precompiled' => 'LAYOUT',
            'layout_id' => 'photon-public',
            'alvo' => 'layouts',
        ]));
    }

    public function testParametrosAusentesNaoQuebram(): void
    {
        self::assertSame('', html_editor_css_precompiled_baseline([]));
    }

    // ===== Contrato do Tailwind Browser

    public function testVersaoDoBrowserEhAMesmaNosDoisEditores(): void
    {
        // Editor clássico e Editbar precisam compilar com a MESMA versão, senão o CSS gravado por um
        // difere do outro para o mesmo HTML.
        self::assertSame('4.3.0', html_editor_tailwind_browser_version());
    }

    public function testContratoDoBrowserEhLidoDoDisco(): void
    {
        // O core traz o contrato em `assets/tailwindcss/browser-contract.css`; projetos sobrescrevem
        // por `contents/tailwindcss/browser-contract.css`.
        self::assertIsString(html_editor_tailwind_browser_contract());
    }

    public function testAtributoComVariavelResolvidaMantemDadosParaReversao(): void
    {
        global $_GESTOR;

        $_GESTOR['url-raiz'] = '/';
        $html = html_editor_boxes_variaveis('<img src="[[pagina#url-raiz]]images/logo.png">');

        self::assertStringContainsString('src="/images/logo.png"', $html);
        self::assertStringContainsString(
            'data-c2f-orig-src="[[pagina#url-raiz]]images/logo.png"',
            $html
        );
        self::assertStringContainsString('data-c2f-resolved-src="/images/logo.png"', $html);
    }
}
