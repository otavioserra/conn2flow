<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-243 (BATCH-252) — refinamentos do painel Tailwind, rodada 2 da auditoria humana.
 *
 * Contratos de marcação e de fonte nos dois idiomas: o que a auditoria pediu fica verificável sem
 * navegador. O comportamento em tela tem roteiro próprio em `sdd/validation/req243/`.
 */
final class RefinamentosReq243Test extends TestCase
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

    /** @return array<string, string> id => valor, do bloco de variáveis de um idioma no manifesto do módulo. */
    private function variaveisDoModulo(string $modulo, string $idioma): array
    {
        $manifesto = json_decode($this->ler("modulos/$modulo/$modulo.json"), true);
        $this->assertIsArray($manifesto);

        return array_column($manifesto['resources'][$idioma]['variables'] ?? [], 'value', 'id');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testHistoricoQuebraLinhaNosTresFormularios(string $idioma): void
    {
        foreach (['interface-formulario-edicao-tailwind', 'interface-formulario-visualizacao-tailwind'] as $componente) {
            $html = $this->ler("resources/$idioma/components/$componente/$componente.html");
            $historico = substr($html, (int) strpos($html, 'data-c2f-historico'));
            $this->assertStringContainsString('table-fixed', $historico, $componente);
            $this->assertStringNotContainsString('min-w-max', $historico, $componente);
            $this->assertStringContainsString('break-words whitespace-normal', $historico, $componente);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testCartoesDeModelosCompactos(string $idioma): void
    {
        $html = $this->ler("resources/$idioma/components/html-editor-modelos-tailwind/html-editor-modelos-tailwind.html");
        $this->assertStringContainsString('class="modelos-cards c2fc-modelos-grade" id="modelos-cards"', $html);
        $this->assertStringContainsString('modelo-card c2fc-modelo-cartao', $html);
        $this->assertStringContainsString('modeloSelecionar c2fc-botao c2fc-botao-primario c2fc-botao-compacto', $html);
        $this->assertStringNotContainsString('xl:grid-cols-4', $html);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testAdminArquivosTemDicasBandejaEConclusao(string $idioma): void
    {
        $html = $this->ler("modulos/admin-arquivos/resources/$idioma/pages/admin-arquivos/admin-arquivos.html");
        foreach (['add-button-tooltip', 'folder-new-tooltip', 'select-all-tooltip'] as $dica) {
            $this->assertStringContainsString('data-c2f-dica="@[[' . $dica . ']]@"', $html);
        }
        $this->assertStringContainsString('id="c2f-pick-tray-empty"', $html);
        $this->assertStringContainsString('@[[picker-tray-confirm]]@', $html);
        // Ação e visualização na mesma altura: o mesmo respiro vertical nos dois grupos.
        $this->assertSame(4, substr_count($html, 'c2f-view-btn rounded-lg border px-2.5 py-1.5'));
        $this->assertStringContainsString('px-3 py-1.5 text-sm font-medium shadow-sm transition-colors hover:bg-slate-100" id="c2f-new-folder"', $html);

        $variaveis = $this->variaveisDoModulo('admin-arquivos', $idioma);
        foreach (['picker-tray-confirm', 'picker-tray-empty', 'add-button-tooltip', 'folder-new-tooltip', 'select-all-tooltip'] as $id) {
            $this->assertNotSame('', trim((string) ($variaveis[$id] ?? '')), "$id ($idioma)");
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testCategoriaFilhaUsaControlesOficiais(string $idioma): void
    {
        $html = $this->ler("modulos/admin-categorias/resources/$idioma/pages/admin-categorias-adicionar-filho/admin-categorias-adicionar-filho.html");
        $this->assertStringContainsString('class="c2fc-campo-entrada" id="admin-categorias-filho-nome" type="text" name="nome"', $html);
        $this->assertStringContainsString('<input type="hidden" name="id_pai" value="#id-pai#">', $html);

        $interface = $this->ler('bibliotecas/interface.php');
        $this->assertStringContainsString("\$tailwind ? 'interface-formulario-inclusao-tailwind' : 'interface-formulario-inclusao-incomum'", $interface);
        // O botão de envio da variante usada tem as classes oficiais.
        $formulario = $this->ler("resources/$idioma/components/interface-formulario-inclusao-tailwind/interface-formulario-inclusao-tailwind.html");
        $this->assertMatchesRegularExpression('/id="_gestor-interface-insert-button"[^>]*class="c2fc-botao c2fc-botao-primario/s', $formulario);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testMensagensInformativasEmLinhaUnica(string $idioma): void
    {
        $telas = [];
        foreach (['cookie-consent', 'menus', 'pages-index', 'publisher-highlights', 'publisher-index'] as $modulo) {
            foreach (['adicionar', 'editar', 'clonar'] as $tela) {
                $telas[] = "modulos/$modulo/resources/$idioma/pages/$modulo-$tela/$modulo-$tela.html";
            }
        }
        $convertidas = 0;
        foreach ($telas as $tela) {
            $html = $this->ler($tela);
            $this->assertStringNotContainsString('display: block;">', $html, $tela);
            // Cada caixa em linha traz o texto dentro de um <span>, ao lado do ícone.
            preg_match_all('/<div class="c2fc-rotulo label inline-flex items-center gap-2"[^>]*>\s*<i data-lucide="[^"]+" class="[^"]*"><\/i>\s*<span>/', $html, $caixas);
            $this->assertSame(substr_count($html, 'c2fc-rotulo label inline-flex items-center gap-2'), count($caixas[0]), $tela);
            $convertidas += count($caixas[0]);
        }
        $this->assertGreaterThanOrEqual(20, $convertidas);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testTipoDePaginaDoMenuEmLinhaHorizontal(string $idioma): void
    {
        foreach (['adicionar', 'editar', 'clonar'] as $tela) {
            $html = $this->ler("modulos/menus/resources/$idioma/pages/menus-$tela/menus-$tela.html");
            $this->assertStringContainsString('<div class="flex flex-wrap items-center gap-4" role="radiogroup"', $html, $tela);
            $this->assertSame(3, preg_match_all('/class="inline-flex cursor-pointer items-center gap-2 radio checkbox" data-c2f-dica="[^"]+" data-c2f-dica-pos="top left">\s*<input type="radio" name="page_search_type"/', $html), $tela);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testMapeamentoEmTresColunasECopiarCompacto(string $idioma): void
    {
        foreach (['pages-index', 'publisher', 'publisher-highlights', 'publisher-index'] as $modulo) {
            foreach (['adicionar', 'editar', 'clonar'] as $tela) {
                $caminho = "modulos/$modulo/resources/$idioma/pages/$modulo-$tela/$modulo-$tela.html";
                $html = $this->ler($caminho);
                $this->assertStringContainsString('template-fields-list c2fc-mapa grid grid-cols-1 gap-4 md:grid-cols-3', $html, $caminho);
                $this->assertSame(3, substr_count($html, '<h5 class="c2fc-mapa-titulo">'), $caminho);
                // A grade externa de três colunas com uma linha só espremia o mapa no primeiro terço.
                $this->assertDoesNotMatchRegularExpression('/<div class="grid grid-cols-1 gap-4 md:grid-cols-3">\s*<div class="row">/', $html, $caminho);
                foreach (['available-fields-list', 'missing-fields-list', 'linked-fields-list'] as $lista) {
                    $this->assertStringContainsString('id="' . $lista . '"', $html, $caminho);
                }
                if ($modulo !== 'publisher') {
                    $this->assertMatchesRegularExpression('/id="btn-copy-widget-val" class="[^"]*c2fc-botao-compacto[^"]*"/', $html, $caminho);
                    $this->assertMatchesRegularExpression('/id="hep-widget-val"[^>]*c2fc-campo-entrada min-w-0 flex-1|c2fc-campo-entrada min-w-0 flex-1"[^>]*id="hep-widget-val"/', $html, $caminho);
                }
            }
        }
        $titulos = $idioma === 'pt-br' ? ['Variáveis do Modelo', 'Campos do Publicador', 'Vinculados (Variável/Campo)'] : ['Template Variables', 'Publisher Fields', 'Linked (Variable/Field)'];
        $publisher = $this->ler("modulos/publisher/resources/$idioma/pages/publisher-editar/publisher-editar.html");
        foreach ($titulos as $titulo) {
            $this->assertStringContainsString('<h5 class="c2fc-mapa-titulo">' . $titulo . '</h5>', $publisher);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testSelectsDaFamiliaPublisherUsamControleOficial(string $idioma): void
    {
        foreach (['pages-index', 'publisher', 'publisher-highlights', 'publisher-index'] as $modulo) {
            foreach (['adicionar', 'editar', 'clonar'] as $tela) {
                $caminho = "modulos/$modulo/resources/$idioma/pages/$modulo-$tela/$modulo-$tela.html";
                preg_match_all('/<select\b[^>]*\bid="(?:template_id|order_by|rule|publisher_id)"[^>]*>/', $this->ler($caminho), $selects);
                $this->assertNotEmpty($selects[0], $caminho);
                foreach ($selects[0] as $select) {
                    $this->assertStringContainsString('data-c2f-select="1"', $select, $caminho);
                    $this->assertStringContainsString('c2fc-campo-selecao', $select, $caminho);
                }
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testPublisherPagesEspelhaAdminPaginas(string $idioma): void
    {
        $filtro = $this->ler("modulos/publisher-pages/resources/$idioma/components/lista-pagina-ou-sistema-ou-publisher-tailwind/lista-pagina-ou-sistema-ou-publisher-tailwind.html");
        $referencia = $this->ler("modulos/admin-paginas/resources/$idioma/components/lista-pagina-ou-sistema-tailwind/lista-pagina-ou-sistema-tailwind.html");
        $this->assertStringContainsString('<div class="mb-4 flex flex-wrap items-end gap-x-6 gap-y-3">', $filtro);
        $this->assertStringContainsString('<div class="mb-4 flex flex-wrap items-end gap-x-6 gap-y-3">', $referencia);
        $this->assertSame(3, preg_match_all('/<input type="radio" name="tipo"[^>]*value="(?:pagina|sistema|ambos)"/', $filtro));
        // A variante guarda os marcadores do componente original do mesmo idioma.
        $original = $this->ler("modulos/publisher-pages/resources/$idioma/components/lista-pagina-ou-sistema-ou-publisher/lista-pagina-ou-sistema-ou-publisher.html");
        preg_match_all('/<!-- [a-z-]+ (?:<|>) -->|<!-- [a-z-]+-opcoes -->|#[a-z_]+#/', $original, $esperados);
        foreach (array_unique($esperados[0]) as $marcador) {
            $this->assertStringContainsString($marcador, $filtro, $marcador);
        }

        foreach (['adicionar', 'editar', 'clonar'] as $tela) {
            $caminho = "modulos/publisher-pages/resources/$idioma/pages/publisher-pages-$tela/publisher-pages-$tela.html";
            $html = $this->ler($caminho);
            $this->assertStringStartsWith('<div class="space-y-4 min-w-0 publisher-pages-form">', $html, $caminho);
            $this->assertMatchesRegularExpression('/<!-- publisher-options < -->\s*<div class="mt-3 mb-2 flex flex-wrap items-center gap-2">/', $html, $caminho);
            $this->assertMatchesRegularExpression('/<\/div><!-- publisher-options > -->/', $html, $caminho);
            // O editor não fica mais dentro de um campo flexível em coluna.
            $this->assertDoesNotMatchRegularExpression('/<div class="c2fc-campo">\s*<label class="c2fc-campo-rotulo">#form-page-content-label#<\/label>\s*#html-editor#/', $html, $caminho);
            $this->assertSame(1, substr_count($html, '#html-editor#'), $caminho);
            $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'), $caminho);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testVariaveisDoComponenteDeConfiguracaoExistem(string $idioma): void
    {
        $catalogo = json_decode($this->ler("resources/$idioma/variables.json"), true);
        $this->assertIsArray($catalogo);
        $doModulo = [];
        foreach ($catalogo as $variavel) {
            if (($variavel['modulo'] ?? '') === 'configuracao') $doModulo[$variavel['id']] = (string) $variavel['value'];
        }
        foreach (['configuracao-widget-tailwind', 'configuracao-campos-tailwind'] as $componente) {
            $html = $this->ler("resources/$idioma/components/$componente/$componente.html");
            preg_match_all('/@\[\[([a-z0-9-]+)\]\]@/', $html, $usadas);
            foreach (array_unique($usadas[1]) as $id) {
                $this->assertNotSame('', trim($doModulo[$id] ?? ''), "variável $id usada em $componente sem valor em $idioma");
            }
            // Ícones do painel (Lucide), sem a marcação de ícone do Fomantic que ganhava contorno.
            $this->assertDoesNotMatchRegularExpression('/<i class="[^"]*\bicon\b[^"]*"><\/i>/', $html, $componente);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testCronEAmbienteComSelectsBotoesEDicas(string $idioma): void
    {
        foreach (['admin-cron', 'admin-environment'] as $modulo) {
            $html = $this->ler("modulos/$modulo/resources/$idioma/pages/$modulo/$modulo.html");
            preg_match_all('/<select\b[^>]*>/', $html, $selects);
            $this->assertNotEmpty($selects[0], $modulo);
            foreach ($selects[0] as $select) {
                $this->assertStringContainsString('data-c2f-select="1"', $select, $modulo);
                $this->assertStringContainsString('c2fc-campo-selecao', $select, $modulo);
            }
            // Todo botão de ação (as abas ficam de fora) usa a classe oficial e tem dica com variável existente.
            $variaveis = $this->variaveisDoModulo($modulo, $idioma);
            preg_match_all('/<button\b[^>]*>/', $html, $botoes);
            $conferidos = 0;
            foreach ($botoes[0] as $botao) {
                if (str_contains($botao, 'c2fc-aba')) continue;
                $this->assertMatchesRegularExpression('/class="c2fc-botao\b/', $botao, $modulo);
                if (preg_match('/id="cron-form-(cancelar|salvar)"/', $botao)) continue;
                $this->assertSame(1, preg_match('/data-c2f-dica="@\[\[([a-z0-9-]+)\]\]@"/', $botao, $dica), $modulo . ': ' . $botao);
                $this->assertNotSame('', trim((string) ($variaveis[$dica[1]] ?? '')), "$modulo: {$dica[1]} ($idioma)");
                $conferidos++;
            }
            $this->assertGreaterThanOrEqual(4, $conferidos, $modulo);
        }
    }

    public function testPublisherPagesEscapaOQueVaiParaCamposDoFormulario(): void
    {
        $php = $this->ler('modulos/publisher-pages/publisher-pages.php');
        // HTML da publicação dentro do <textarea> do editor: escapado na inclusão, na edição e na clonagem.
        $this->assertSame(2, substr_count($php, "\$html = (isset(\$html_template) ? htmlentities(\$html_template) : '');"));
        $this->assertStringNotContainsString("\$html = (isset(\$html_template) ? \$html_template : '');", $php);
        $this->assertStringContainsString("htmlentities(\$template['html'])", $php);
        // Valores dos campos do publicador: escapados em <textarea> e em value="…"; o tipo html segue como marcação.
        $this->assertSame(2, substr_count($php, "'[[field-value]]', htmlspecialchars((string)\$value_field, ENT_QUOTES, 'UTF-8'));"));
        $this->assertSame(2, substr_count($php, "\$field['type'] === 'html' ? \$value_field : htmlspecialchars((string)\$value_field, ENT_QUOTES, 'UTF-8')"));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testPublisherPagesTitulosEEtiquetasComIconeNaMesmaLinha(string $idioma): void
    {
        foreach (['adicionar', 'editar', 'clonar'] as $tela) {
            $html = $this->ler("modulos/publisher-pages/resources/$idioma/pages/publisher-pages-$tela/publisher-pages-$tela.html");
            $this->assertSame(2, substr_count($html, '<h4 class="mt-6 mb-3 flex items-center gap-2 border-b border-slate-200 pb-2 font-semibold publisher-fields-header">'), $tela);
        }
        $campos = $this->ler("modulos/publisher-pages/resources/$idioma/components/publisher-fields-tailwind/publisher-fields-tailwind.html");
        $this->assertSame(4, substr_count($campos, '<span class="inline-flex items-center gap-1.5" data-c2f-dica="'));
        $this->assertSame(2, substr_count($campos, '<span>[[field-description]]</span>'));
        // A variante guarda os marcadores e os ganchos do componente original.
        $original = $this->ler("modulos/publisher-pages/resources/$idioma/components/publisher-fields/publisher-fields.html");
        preg_match_all('/<!-- publisher-[a-z-]+ (?:<|>) -->|\[\[field-[a-z-]+\]\]/', $original, $marcadores);
        foreach (array_unique($marcadores[0]) as $marcador) {
            $this->assertStringContainsString($marcador, $campos, $marcador);
        }
        foreach (['copy-to-clipboard', 'field-variable', 'pfc-field', 'quill-editor'] as $gancho) {
            $this->assertStringContainsString($gancho, $campos, $gancho);
        }
    }

    public function testBotaoSemDicaUsaORotuloEHistoricoNaoApagaVariaveisDaPagina(): void
    {
        $interface = $this->ler('bibliotecas/interface.php');
        $this->assertStringContainsString("if(\$tooltip === '') \$tooltip = htmlspecialchars(trim(strip_tags(\$rotulo)), ENT_QUOTES, 'UTF-8');", $interface);
        // O histórico mescla as variáveis dele às que a página já registrou (seletor de imagem, por exemplo).
        $this->assertMatchesRegularExpression("/\\\$_GESTOR\['javascript-vars'\]\['interface'\] = array_merge\(\s*\(isset\(\\\$_GESTOR\['javascript-vars'\]\['interface'\]\)/", $interface);
        $this->assertDoesNotMatchRegularExpression("/\\\$_GESTOR\['javascript-vars'\]\['interface'\] = Array\(\s*'id' => \\\$id,/", $interface);
    }
}
