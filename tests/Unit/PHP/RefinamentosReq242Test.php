<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-242 (BATCH-251) — refinamentos do painel Tailwind apontados na auditoria humana.
 *
 * Contratos de marcação e de fonte: o que a auditoria pediu fica verificável sem navegador, nos dois
 * idiomas. O comportamento em tela tem roteiro próprio em `sdd/validation/req242/`.
 */
final class RefinamentosReq242Test extends TestCase
{
    private function ler(string $relativo): string
    {
        $arquivo = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativo);
        $this->assertFileExists($arquivo);

        return (string) file_get_contents($arquivo);
    }

    /** @return array<int, array{0: string}> */
    public static function idiomas(): array
    {
        return [['pt-br'], ['en']];
    }

    /** Toda aba (`<a class="c2fc-aba …" data-tab>` ou `<button class="c2fc-aba" data-c2f-aba>`) tem dica. */
    private function abasSemDica(string $html): array
    {
        preg_match_all('/<(?:a|button)\b[^>]*class="[^"]*\bc2fc-aba\b[^"]*"[^>]*>/', $html, $abas);
        $sem = [];
        foreach ($abas[0] as $aba) {
            if (!preg_match('/data-tab=|data-c2f-aba=/', $aba)) continue;
            if (!str_contains($aba, 'data-c2f-dica=')) $sem[] = $aba;
        }

        return $sem;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testAbasDosFormulariosEGaleriasTemDica(string $idioma): void
    {
        $paginas = [
            'modulos/forms/resources/%s/pages/forms-adicionar/forms-adicionar.html',
            'modulos/forms/resources/%s/pages/forms-editar/forms-editar.html',
            'modulos/forms/resources/%s/pages/forms-clonar/forms-clonar.html',
            'modulos/forms-search/resources/%s/pages/forms-search-adicionar/forms-search-adicionar.html',
            'modulos/forms-search/resources/%s/pages/forms-search-editar/forms-search-editar.html',
            'modulos/forms-search/resources/%s/pages/forms-search-clonar/forms-search-clonar.html',
            'modulos/forms-submissions/resources/%s/pages/forms-submissions-view/forms-submissions-view.html',
            'modulos/galleries/resources/%s/pages/galleries-adicionar/galleries-adicionar.html',
            'modulos/galleries/resources/%s/pages/galleries-editar/galleries-editar.html',
            'modulos/galleries/resources/%s/pages/galleries-clonar/galleries-clonar.html',
        ];
        foreach ($paginas as $modelo) {
            $caminho = sprintf($modelo, $idioma);
            $html = $this->ler($caminho);
            $this->assertSame([], $this->abasSemDica($html), $caminho);
            $this->assertGreaterThan(0, preg_match_all('/\bc2fc-aba\b[^"]*"[^>]*data-c2f-dica=/', $html), $caminho);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testBotaoCopiarCompactoAoLadoDoCampo(string $idioma): void
    {
        foreach (['forms' => 'btn-copy-forms-widget-val', 'forms-search' => 'btn-copy-forms-search-widget-val', 'galleries' => 'btn-copy-widget-val'] as $modulo => $id) {
            foreach (['adicionar', 'editar', 'clonar'] as $tela) {
                $html = $this->ler("modulos/$modulo/resources/$idioma/pages/$modulo-$tela/$modulo-$tela.html");
                $this->assertMatchesRegularExpression('/<button[^>]*id="' . preg_quote($id, '/') . '"[^>]*class="[^"]*c2fc-botao-compacto/', $html, "$modulo-$tela");
            }
        }
        $this->assertStringContainsString('.c2fc-botao { white-space: nowrap; }', $this->ler('assets/interface/controles.css'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testEnvioMostraJsonEmCodeMirrorSomenteLeitura(string $idioma): void
    {
        $html = $this->ler("modulos/forms-submissions/resources/$idioma/pages/forms-submissions-view/forms-submissions-view.html");
        $this->assertMatchesRegularExpression('/<textarea class="codemirror-json" readonly/', $html);
        $this->assertStringNotContainsString('<details', $html);
        $this->assertMatchesRegularExpression('/<select id="form-status-select" class="c2fc-campo-selecao[ "]/', $html);
        $this->assertStringNotContainsString('style="min-width: 160px;"', $html);

        $js = $this->ler('modulos/forms-submissions/forms-submissions.js');
        $this->assertStringContainsString("mode: 'application/json'", $js);
        $this->assertStringContainsString('readOnly: true', $js);
    }

    public function testJsonDoVisitanteEntraEscapadoNaPagina(): void
    {
        $php = $this->ler('modulos/forms-submissions/forms-submissions.php');
        $this->assertMatchesRegularExpression('/htmlspecialchars\(\(string\)\$fields_values_pretty, ENT_QUOTES \| ENT_SUBSTITUTE, \'UTF-8\'\)/', $php);
        $this->assertStringContainsString("str_replace('@[[', '&#64;[['", $php);
        $this->assertStringContainsString("assets_externos_incluir('codemirror')", $php);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testVariaveisNovasExistemNosDoisIdiomas(string $idioma): void
    {
        $esperadas = [
            'forms-submissions' => ['ui-tab-data-tip', 'ui-tab-replies-tip', 'ui-tab-json-tip', 'ui-json-title', 'ui-json-copy', 'ui-json-copied', 'ui-status-save-tip', 'ui-reply-send-tip'],
            'admin-arquivos' => ['picker-title-multiple', 'picker-tray-label', 'picker-tray-cancel', 'picker-tray-remove'],
        ];
        foreach ($esperadas as $modulo => $ids) {
            $manifesto = json_decode($this->ler("modulos/$modulo/$modulo.json"), true);
            $this->assertIsArray($manifesto, $modulo);
            $existentes = array_column($manifesto['resources'][$idioma]['variables'], 'value', 'id');
            foreach ($ids as $id) {
                $this->assertArrayHasKey($id, $existentes, "$modulo/$idioma/$id");
                $this->assertNotSame('', trim((string) $existentes[$id]), "$modulo/$idioma/$id");
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testSeletorDeArquivosTemTituloDeSelecaoMultiplaEBandeja(string $idioma): void
    {
        $html = $this->ler("modulos/admin-arquivos/resources/$idioma/pages/admin-arquivos/admin-arquivos.html");
        $this->assertStringContainsString('id="c2f-files-title" data-title-multiple="@[[picker-title-multiple]]@"', $html);
        foreach (['id="c2f-pick-tray"', 'id="c2f-pick-tray-thumbs"', 'id="c2f-pick-tray-cancel"', 'id="c2f-pick-tray-confirm"'] as $gancho) {
            $this->assertStringContainsString($gancho, $html);
        }
        // nenhum botão do gerenciador fica com o canto de 4 px
        $this->assertSame(0, preg_match('/<(?:button|a)\b[^>]*class="[^"]*(?<![\w-])rounded(?![\w-])/', $html));

        $php = $this->ler('modulos/admin-arquivos/admin-arquivos.php');
        $this->assertStringContainsString("'selecaoMultipla' => admin_arquivos_selecao_multipla()", $php);
        $this->assertStringContainsString("admin-arquivos/?paginaIframe=sim&multiplo=sim", $this->ler('modulos/galleries/galleries.php'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testGaleriaTemAjudaEmLinhaEControlesEmGrade(string $idioma): void
    {
        foreach (['adicionar', 'editar', 'clonar'] as $tela) {
            $html = $this->ler("modulos/galleries/resources/$idioma/pages/galleries-$tela/galleries-$tela.html");
            $this->assertStringNotContainsString('class="c2fc-rotulo label" style="margin: 8px 0; display: block;"', $html, $tela);
            $this->assertStringContainsString('inline-flex items-center gap-2 text-xs text-slate-500', $html, $tela);
            $this->assertStringContainsString('lg:grid-cols-4 gallery-display-fields', $html, $tela);
            $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'), $tela);
        }
        $variaveis = $this->ler("resources/$idioma/components/html-editor-publisher-controls-tailwind/html-editor-publisher-controls-tailwind.html");
        $this->assertStringContainsString('gap-2 hep-variables-toolbar', $variaveis);
        $this->assertStringContainsString('gap-2 hep-val-options-buttons', $variaveis);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testSidebarETopbarTemTipografiaEAlinhamentoDoLayout(string $idioma): void
    {
        $css = $this->ler("resources/$idioma/layouts/layout-administrativo-tailwind/layout-administrativo-tailwind.css");
        $this->assertStringContainsString('body:has(> #c2f-admin-shell), #c2f-admin-shell { font-family:', $css);
        $this->assertStringContainsString('[data-admin-sidebar] [data-menu-item] { font-family: inherit; font-size: .875rem;', $css);
        $this->assertStringContainsString('[data-admin-topbar] > nav { justify-content: flex-end; }', $css);
    }

    /**
     * O manifesto do módulo é fonte do Tailwind quando as utilities vivem nas variáveis. Regravado com
     * a barra escapada, `ring-sky-600/20` deixava de ser reconhecida e sumia do CSS compilado.
     */
    public function testManifestoDeModuloERegravadoSemEscaparBarras(): void
    {
        $compilador = $this->ler('controladores/agents/arquitetura/atualizacao-dados-recursos.php');
        $this->assertStringContainsString('jsonWrite($jsonFile,$data, JSON_UNESCAPED_SLASHES);', $compilador);
        $this->assertStringContainsString('function jsonWrite(string $path, array $data, int $flagsExtras = 0): bool', $compilador);

        $manifesto = $this->ler('modulos/perfil-usuario/perfil-usuario.json');
        $this->assertStringContainsString('focus:ring-sky-600/20', $manifesto);
        $this->assertStringNotContainsString('ring-sky-600\\/20', $manifesto);
    }
}
