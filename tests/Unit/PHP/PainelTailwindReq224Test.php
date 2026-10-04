<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-224 (fatia 6 da req-219): usuários, perfis, módulos, operações e painel inicial em Tailwind.
 * Contrato: layout e bundle, dependências, HTML sem Fomantic e com os nomes de campo de antes, flag do
 * bundle no runtime e os ganchos que o JS de cada módulo usa.
 */
final class PainelTailwindReq224Test extends TestCase
{
    private const PAGINAS = [
        'usuarios' => ['usuarios' => 'listar', 'usuarios-adicionar' => 'adicionar', 'usuarios-editar' => 'editar'],
        'usuarios-perfis' => ['usuarios-perfis' => 'listar', 'usuarios-perfis-adicionar' => 'adicionar', 'usuarios-perfis-editar' => 'editar'],
        'modulos' => ['modulos' => 'listar', 'modulos-adicionar' => 'adicionar', 'modulos-editar' => 'editar'],
        'modulos-operacoes' => ['modulos-operacoes' => 'listar', 'modulos-operacoes-adicionar' => 'adicionar', 'modulos-operacoes-editar' => 'editar'],
        'dashboard' => ['dashboard' => 'inicio'],
    ];

    /** Nomes de campo (e ganchos) que o PHP e o JS esperam em cada página de formulário. */
    private const CONTRATO = [
        'usuarios-adicionar' => ['name="nome"', 'name="email"', 'name="email-2"', 'name="usuario"', 'name="senha"', 'name="senha-2"', '#select-user-profile#', 'class="first-name'],
        'usuarios-editar' => ['name="nome_conta"', 'name="nome"', 'name="email"', 'name="usuario"', 'name="senha-atualizar"', '<!-- usuario-pai < -->', '<!-- senha-campos < -->', '<!-- senha-botao < -->', 'id="senha-campos"'],
        'usuarios-perfis-adicionar' => ['name="nome"', 'name="padrao"', 'name="modulo-#num#"', 'name="operacao-#operacao-num#"', '<!-- grupo < -->', '<!-- items < -->', '<!-- operacoes < -->', '<!-- operacoes-items < -->', 'selectAll', 'unselectAll', 'profile-tabs', '#home-page-autocomplete#'],
        'usuarios-perfis-editar' => ['data-checked="#padrao-checked#"', 'data-checked="#checked#"', 'data-checked="#operacao-checked#"', '#home-page-autocomplete#'],
        'modulos-adicionar' => ['name="nome"', 'name="titulo"', 'name="host"', 'name="icone"', 'name="icone_tailwind"', 'name="icone2"', 'name="icone2_tailwind"', '#select-grup#', '#select-plugin#', '#select-menu#'],
        'modulos-editar' => ['value="#icone_tailwind#"', 'data-checked="#checked#"'],
        'modulos-operacoes-adicionar' => ['name="nome"', 'name="operacao"', '#select-module#'],
    ];

    public function testPaginasNoLayoutTailwindComBundleEDependencias(): void
    {
        foreach (self::PAGINAS as $modulo => $paginas) {
            $base = CONN2FLOW_GESTOR_ROOT . '/modulos/' . $modulo;
            $json = json_decode((string)file_get_contents($base . '/' . $modulo . '.json'), true);
            foreach (['pt-br', 'en'] as $lingua) {
                $porId = array_column($json['resources'][$lingua]['pages'], null, 'id');
                foreach ($paginas as $id => $opcao) {
                    $pagina = $porId[$id];
                    self::assertSame('layout-administrativo-tailwind', $pagina['layout'], "$lingua/$id");
                    self::assertTrue($pagina['tailwind_bundle'] ?? false, "$lingua/$id");
                    $deps = array_column($pagina['tailwind_dependencies'], 'id');
                    if ($opcao === 'listar') self::assertContains('interface-listar-tailwind', $deps, "$lingua/$id");
                    $html = (string)file_get_contents($base . '/resources/' . $lingua . '/pages/' . $id . '/' . $id . '.html');
                    self::assertStringNotContainsString('class="ui ', $html, "$lingua/$id");
                    foreach (self::CONTRATO[$id] ?? [] as $trecho) {
                        self::assertStringContainsString($trecho, $html, "$lingua/$id: $trecho");
                    }
                }
            }
        }
    }

    public function testRuntimeLigaOBundleEEscolheAsVariantes(): void
    {
        foreach (['usuarios', 'usuarios-perfis', 'modulos', 'modulos-operacoes'] as $modulo) {
            $php = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/' . $modulo . '/' . $modulo . '.php');
            self::assertSame(3, substr_count($php, "\$_GESTOR['tailwind-page-bundle'] = true;"), $modulo);
        }
        $perfis = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/usuarios-perfis/usuarios-perfis.php');
        self::assertStringContainsString("interface_componente_variante('home-page-autocomplete')", $perfis);
        $dash = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/dashboard.php');
        self::assertStringContainsString("interface_componente_variante('dashboard-cards')", $dash);
        foreach (['pt-br', 'en'] as $lingua) {
            $cards = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/resources/' . $lingua . '/components/dashboard-cards-tailwind/dashboard-cards-tailwind.html');
            foreach (['id="dashboard-sortable-cards"', 'dashboard-module-card', 'dashboard-card-drag-handle', 'id="dashboard-search-input"', 'id="dashboard-search-reset"', 'id="dashboard-update-notification"', 'dashboard-update-dismiss', 'close icon', 'class="category"', '<!-- card < -->', '<!-- svg < -->'] as $gancho) {
                self::assertStringContainsString($gancho, $cards, "$lingua: $gancho");
            }
            $home = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/usuarios-perfis/resources/' . $lingua . '/components/home-page-autocomplete-tailwind/home-page-autocomplete-tailwind.html');
            foreach (['id="pagina-inicial-busca"', 'id="pagina-inicial-suggestions"', 'name="pagina_inicial"', 'home-page-clear', 'home-page-search'] as $gancho) {
                self::assertStringContainsString($gancho, $home, "$lingua: $gancho");
            }
        }
        // O JS de usuários não depende mais do interface.js legado para o atraso de digitação.
        self::assertStringContainsString("typeof \$.input_delay_to_change !== 'function'", (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/usuarios/usuarios.js'));
    }
}
