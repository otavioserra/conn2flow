<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-219 (fatias 3 e 4): variantes Tailwind do editor HTML, do assistente de IA, do imagepick e das
 * páginas do admin-paginas.
 *
 * A variante troca o HTML, nunca o contrato: o PHP do editor troca marcadores e placeholders, e o
 * JavaScript legado (html-editor-interface.js, ia.js, admin-paginas.js) acha os elementos por id,
 * nome, `data-tab` e classes-gancho. Se a variante perder um deles, o editor quebra só na página
 * Tailwind, sem erro no PHP. Este teste compara cada variante com a original.
 */
final class HtmlEditorTailwindReq219Test extends TestCase
{
    /** Componente original => classes-gancho que o JS procura e a variante precisa manter. */
    private const COMPONENTES = [
        'html-editor' => ['html-editor-component', 'menuContainerPagina', 'containerPagina', 'editorHtmlVisual', 'menuPaginas',
            'publisherVariablesOrSimulation', 'publisherVariablesOrValues', 'publisher-design-mode-simulation', 'menu-pagina-conteudo', 'dimmer'],
        'html-editor-visual-modal' => ['previsualizar', 'screenPagina', 'html-editor-add-btn', 'c2f-tb-view-options', 'html-editor-undo-btn',
            'html-editor-redo-btn', 'iframe-resize-handle', 'iframe-resize-indicator', 'previsualizarVoltar', 'previsualizarConfirmar', 'approve', 'actions', 'dimmer'],
        'html-editor-modal' => ['_html-editor-imagepick-btn', '_html-editor-imagepick-preview', '_html-editor-imagepick-image',
            '_html-editor-imagepick-nome', '_html-editor-imagepick-tipo', '_html-editor-imagepick-clear', 'codemirror-html-editor', 'cancel', 'approve', 'actions'],
        'html-editor-page-modification' => ['page-modification-wrapper', 'page-modification-target-select', 'page-modification-section-select',
            'page-modification-container', 'page-modification-section-options', 'page-modification-section-rename', 'page-modification-section-up',
            'page-modification-section-down', 'page-modification-section-delete', 'page-modification-auto-preview', 'page-modification-rename-modal',
            'page-modification-publisher', 'labels', 'label', 'delete'],
        'html-editor-seo' => ['html-editor-seo', 'c2f-seo-og-titulo', 'c2f-seo-og-descricao', 'c2f-seo-meta-descricao', 'c2f-seo-meta-keywords'],
        'html-editor-modelos' => ['modelos-container', 'menu-pagina-conteudo', 'modelos-search-clear', 'modelo-card', 'modeloSelecionar'],
        'ia-prompt' => ['ai-conteiner', 'AIMenu', 'ai-prompt-select', 'ai-prompt-clear', 'ai-prompt-edit', 'ai-prompt-del', 'ai-prompt-new',
            'ai-prompt-textarea', 'ai-mode-select', 'ai-mode-textarea', 'ai-connection-select', 'ai-model-select', 'ai-return-raw',
            'ai-return-textarea', 'negative', 'message', 'ai-error-message', 'close', 'ai-send-prompt', 'dimmer'],
        'ia-prompt-modais' => ['ai-prompt-save-modal', 'ai-prompt-save-form', 'ai-prompt-del-modal', 'deny', 'approve', 'actions'],
        'ia-sem-servidor' => [],
        'interface-iframe-modal' => ['iframePagina', 'header', 'cancel', 'iframe-container', 'dimmer'],
        'interface-backup-dropdown' => ['backupDropdown'],
        'widget-imagem' => ['_gestor-widgetImage-cont', 'widgetImage-image', 'widgetImage-nome', 'widgetImage-data', 'widgetImage-tipo',
            '_gestor-widgetImage-btn-add', '_gestor-widgetImage-btn-del', '_gestor-widgetImage-file-id', '_gestor-widgetImage-file-caminho'],
    ];

    /** Placeholders que a variante dispensa de propósito (o PHP passa a trocar outro). */
    private const DISPENSADOS = [
        // No Tailwind o modelo escolhido sai como <option selected> em #select-model# (ia.php).
        'ia-prompt' => ['#selected-model#'],
        // O separador "ou" entre botões é do Fomantic (`.or`); a variante usa grupo de botões colados.
        'html-editor' => ['@[[html-editor-publisher-or-btn]]@'],
        // Select nativo: sem ícone e sem o rótulo de versão atual (a primeira opção já é a versão atual).
        'interface-backup-dropdown' => ['#versao-atual-icon#', '#versao-atual-label#', '#versao-atual-description#', '#icon#'],
    ];

    private static function recursos(): string
    {
        return CONN2FLOW_GESTOR_ROOT . '/resources';
    }

    private static function ler(string $caminho): string
    {
        self::assertFileExists($caminho);
        return (string)file_get_contents($caminho);
    }

    /** @return array<string,list<string>> */
    private static function contrato(string $html): array
    {
        $semComentarioLivre = preg_replace('/<!--(?!\s*[\w-]+\s*[<>]\s*-->)(?!\s*[\w-]+-componente\s*-->).*?-->/s', '', $html);
        $pegar = static function (string $re, string $alvo): array {
            preg_match_all($re, $alvo, $m);
            $v = array_values(array_unique($m[1]));
            sort($v);
            return $v;
        };
        return [
            'marcadores' => $pegar('/<!--\s*([\w-]+\s*[<>]|[\w-]+-componente)\s*-->/', $html),
            'placeholders' => $pegar('/(#[a-z][\w-]*#|@\[\[[^\]]+\]\]@)/', $semComentarioLivre),
            'ids' => $pegar('/\sid="([^"#]+)"/', $semComentarioLivre),
            'nomes' => $pegar('/\sname="([^"]+)"/', $semComentarioLivre),
            'abas' => $pegar('/\sdata-tab="([^"]+)"/', $semComentarioLivre),
            'data-id' => $pegar('/\sdata-id="([^"]+)"/', $semComentarioLivre),
        ];
    }

    /** @return iterable<string,array{string,string,list<string>}> */
    public static function pares(): iterable
    {
        foreach (self::COMPONENTES as $id => $ganchos) {
            foreach (['pt-br', 'en'] as $lingua) {
                yield $lingua . ':' . $id => [$lingua, $id, $ganchos];
            }
        }
    }

    /** @dataProvider pares */
    public function testVarianteMantemOContratoDaOriginal(string $lingua, string $id, array $ganchos): void
    {
        $base = self::recursos() . '/' . $lingua . '/components/';
        $original = self::contrato(self::ler($base . $id . '/' . $id . '.html'));
        $htmlVariante = self::ler($base . $id . '-tailwind/' . $id . '-tailwind.html');
        $variante = self::contrato($htmlVariante);

        self::assertSame($original['marcadores'], $variante['marcadores'], 'marcadores que o PHP troca ou remove');
        foreach (['placeholders', 'ids', 'nomes', 'abas', 'data-id'] as $tipo) {
            $sumiram = array_diff($original[$tipo], $variante[$tipo], self::DISPENSADOS[$id] ?? []);
            self::assertSame([], array_values($sumiram), $tipo . ' que sumiram na variante');
        }

        // Placeholder citado em comentário é trocado ali dentro (a troca pega a primeira ocorrência).
        preg_match_all('/<!--(?!\s*[\w-]+\s*[<>]\s*-->)(?!\s*[\w-]+-componente\s*-->)(.*?)-->/s', $htmlVariante, $comentarios);
        foreach ($comentarios[1] as $comentario) {
            self::assertDoesNotMatchRegularExpression('/#[a-z][\w-]*#|@\[\[/', $comentario, 'placeholder dentro de comentário da variante');
        }

        preg_match_all('/\sclass="([^"]*)"/', $htmlVariante, $m);
        $classes = preg_split('/\s+/', implode(' ', $m[1]));
        foreach ($ganchos as $gancho) {
            self::assertContains($gancho, $classes, 'classe-gancho do JS: ' . $gancho);
        }

        // Sem Fomantic na página, modal visível no HTML ficaria aberto até a ponte montá-lo.
        preg_match_all('/class="[^"]*(?<![\w-])modal(?![\w-])[^"]*"/', $htmlVariante, $modais);
        foreach ($modais[0] as $modal) {
            self::assertMatchesRegularExpression('/(?<![\w-])hidden(?![\w-])/', $modal, 'modal da variante nasce escondido');
        }
    }

    public function testVariantesRegistradasComoTailwindNosDoisIdiomas(): void
    {
        foreach (['pt-br', 'en'] as $lingua) {
            $registro = json_decode(self::ler(self::recursos() . '/' . $lingua . '/components.json'), true);
            $porId = array_column($registro, null, 'id');
            foreach (array_keys(self::COMPONENTES) as $id) {
                self::assertArrayHasKey($id . '-tailwind', $porId, $lingua . ': ' . $id . '-tailwind registrado');
                self::assertSame('tailwindcss', $porId[$id . '-tailwind']['framework_css'] ?? null);
            }
        }
    }

    public function testPaginasDoAdminPaginasEmTailwindComDependencias(): void
    {
        $modulo = CONN2FLOW_GESTOR_ROOT . '/modulos/admin-paginas';
        $json = json_decode(self::ler($modulo . '/admin-paginas.json'), true);
        // Sem o flag do runtime, os sidecars dos componentes entram depois do bundle e invertem as responsivas.
        self::assertSame(4, substr_count(self::ler($modulo . '/admin-paginas.php'), "\$_GESTOR['tailwind-page-bundle'] = true;"));
        foreach (['pt-br', 'en'] as $lingua) {
            $paginas = array_column($json['resources'][$lingua]['pages'], null, 'id');
            foreach (['editar', 'adicionar', 'clonar'] as $opcao) {
                $pagina = $paginas['admin-paginas-' . $opcao];
                self::assertSame('layout-administrativo-tailwind', $pagina['layout']);
                self::assertSame('tailwindcss', $pagina['framework_css']);
                self::assertTrue($pagina['tailwind_bundle']);
                $deps = array_column($pagina['tailwind_dependencies'], 'id');
                foreach (array_keys(self::COMPONENTES) as $id) {
                    self::assertContains($id . '-tailwind', $deps, $lingua . '/' . $opcao . ': utilities de ' . $id . '-tailwind no bundle');
                }

                $html = self::ler($modulo . '/resources/' . $lingua . '/pages/admin-paginas-' . $opcao . '/admin-paginas-' . $opcao . '.html');
                self::assertStringNotContainsString('class="ui ', $html, 'página sem marcação do Fomantic');
                foreach (['#select-layout#', '#select-framework-css#', '#select-type#', '#select-module#', '#layout-profile-mapping#', '#html-editor#',
                    'name="pagina-nome"', 'name="paginaCaminho"', 'name="pagina-opcao"', 'name="raiz"', 'name="sem_permissao"',
                    '<!-- permissao-pagina < -->', '<!-- agendamento-datas < -->', 'pagina-modulos-container'] as $contrato) {
                    self::assertStringContainsString($contrato, $html, $lingua . '/' . $opcao . ': ' . $contrato);
                }
                // `.hidden !important` no CSS da página venceria a aba ativa da ponte de controles.
                $css = self::ler($modulo . '/resources/' . $lingua . '/pages/admin-paginas-' . $opcao . '/admin-paginas-' . $opcao . '.css');
                self::assertDoesNotMatchRegularExpression('/\.hidden\s*\{[^}]*!important/', $css);
            }
        }
    }

    /** BATCH-229: módulos do editor (layouts e componentes) e as listagens raiz, já com a listagem da req-220. */
    public function testModulosDoEditorEListagensEmTailwind(): void
    {
        $esperado = [
            'admin-layouts' => ['admin-layouts', 'admin-layouts-adicionar', 'admin-layouts-editar'],
            'admin-componentes' => ['admin-componentes', 'admin-componentes-adicionar', 'admin-componentes-editar'],
            'admin-paginas' => ['admin-paginas'],
        ];
        foreach ($esperado as $modulo => $ids) {
            $base = CONN2FLOW_GESTOR_ROOT . '/modulos/' . $modulo;
            $json = json_decode(self::ler($base . '/' . $modulo . '.json'), true);
            foreach (['pt-br', 'en'] as $lingua) {
                $paginas = array_column($json['resources'][$lingua]['pages'], null, 'id');
                foreach ($ids as $id) {
                    $pagina = $paginas[$id];
                    self::assertSame('layout-administrativo-tailwind', $pagina['layout'], $lingua . '/' . $id);
                    self::assertTrue($pagina['tailwind_bundle'] ?? false, $lingua . '/' . $id);
                    $deps = array_column($pagina['tailwind_dependencies'], 'id');
                    self::assertContains($id === $modulo ? 'interface-listar-tailwind' : 'html-editor-tailwind', $deps, $lingua . '/' . $id);
                    $html = self::ler($base . '/resources/' . $lingua . '/pages/' . $id . '/' . $id . '.html');
                    self::assertStringNotContainsString('class="ui ', $html, $lingua . '/' . $id);
                }
            }
            // adicionar/editar e a listagem ligam o bundle no runtime
            self::assertGreaterThanOrEqual(count($ids) === 1 ? 4 : 3, substr_count(self::ler($base . '/' . $modulo . '.php'), "\$_GESTOR['tailwind-page-bundle'] = true;"), $modulo);
        }
        // A listagem Tailwind guarda o id que os JS dos módulos usam para ligar os filtros da lista.
        foreach (['pt-br', 'en'] as $lingua) {
            self::assertStringContainsString('id="_gestor-interface-listar"', self::ler(self::recursos() . '/' . $lingua . '/components/interface-listar-tailwind/interface-listar-tailwind.html'));
        }
    }

    public function testBibliotecasEscolhemAVariante(): void
    {
        $editor = self::ler(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/html-editor.php');
        foreach (['html-editor', 'html-editor-seo', 'html-editor-visual-modal', 'html-editor-modelos', 'html-editor-page-modification', 'html-editor-modal'] as $id) {
            self::assertStringContainsString("interface_componente_variante('" . $id . "')", $editor, $id);
        }
        $ia = self::ler(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/ia.php');
        foreach (['ia-prompt', 'ia-prompt-modais', 'ia-sem-servidor'] as $id) {
            self::assertStringContainsString("interface_componente_variante('" . $id . "')", $ia, $id);
        }
        $interface = self::ler(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/interface.php');
        self::assertStringContainsString("interface_componente_variante('interface-iframe-modal')", $interface);
        self::assertSame(2, substr_count($interface, "interface_componente_variante('widget-imagem')"));
    }
}
