<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . 'menus' . DIRECTORY_SEPARATOR . 'menus.widget.php';

final class MenusWidgetConditionalVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        global $_GESTOR;

        $_GESTOR['recursos-incluidos-hashes'] = [];
        $_GESTOR['html-extra-head'] = [];
        $_GESTOR['css'] = [];
        $_GESTOR['css-compiled'] = [];
    }

    public function testRenderizaMenuPublicoParaUsuarioAnonimo(): void
    {
        $html = $this->templateComBlocosCondicionais();
        $schema = $this->schemaCondicional([
            ['type' => 'publico', 'slug' => 'publico'],
        ], [
            'publico' => [$this->link('Public Home', '/public')],
        ]);

        $out = menus_widget_render_inline([
            'html' => $html,
            'fields_schema' => json_encode($schema),
            '_user_data' => ['id_usuarios' => 0, 'id' => '_anonimo', 'id_usuarios_perfis' => 0],
        ]);

        self::assertStringContainsString('data-menu="publico"', $out);
        self::assertStringContainsString('Public Home', $out);
        self::assertStringNotContainsString('Logged Home', $out);
    }

    public function testRenderizaMenuLogadoParaUsuarioAutenticado(): void
    {
        $html = $this->templateComBlocosCondicionais();
        $schema = $this->schemaCondicional([
            ['type' => 'logado', 'slug' => 'logado'],
        ], [
            'logado' => [$this->link('Logged Home', '/app')],
        ]);

        $out = menus_widget_render_inline([
            'html' => $html,
            'fields_schema' => json_encode($schema),
            '_user_data' => ['id_usuarios' => 12, 'id' => 'maria', 'id_usuarios_perfis' => 2],
        ]);

        self::assertStringContainsString('data-menu="logado"', $out);
        self::assertStringContainsString('Logged Home', $out);
    }

    public function testRenderizaMenuDePerfilQuandoSlugDoPerfilCombina(): void
    {
        $html = $this->templateComBlocosCondicionais();
        $schema = $this->schemaCondicional([
            ['type' => 'perfil_usuario', 'slug' => 'cliente'],
        ], [
            'cliente' => [$this->link('Client Area', '/cliente')],
        ]);

        $out = menus_widget_render_inline([
            'html' => $html,
            'fields_schema' => json_encode($schema),
            '_user_data' => ['id_usuarios' => 23, 'id' => 'ana', 'id_usuarios_perfis' => 7],
            '_profile_slug' => 'cliente',
        ]);

        self::assertStringContainsString('data-menu="cliente"', $out);
        self::assertStringContainsString('Client Area', $out);
    }

    public function testRenderizaMenuDePerfilQuandoIdEstaEntreMultiplosPerfisPermitidos(): void
    {
        $html = $this->templateComBlocosCondicionais();
        $schema = $this->schemaCondicional([
            ['type' => 'perfil_usuario', 'slug' => 'cliente', 'profile_ids' => ['3', '7', '9']],
        ], [
            'cliente' => [$this->link('Client Multi Area', '/cliente-multi')],
            'visible_to_all' => [$this->link('Visible Home', '/')],
        ]);

        $out = menus_widget_render_inline([
            'html' => $html,
            'fields_schema' => json_encode($schema),
            '_user_data' => ['id_usuarios' => 23, 'id' => 'ana', 'id_usuarios_perfis' => 7],
        ]);

        self::assertStringContainsString('data-menu="cliente"', $out);
        self::assertStringContainsString('Client Multi Area', $out);
        self::assertStringNotContainsString('Visible Home', $out);
    }

    public function testPerfilForaDaListaNaoSatisfazCondicaoECaiNoFallbackVisible(): void
    {
        $html = $this->templateComBlocosCondicionais();
        $schema = $this->schemaCondicional([
            ['type' => 'perfil_usuario', 'slug' => 'cliente', 'profile_ids' => ['3', '7', '9']],
        ], [
            'cliente' => [$this->link('Client Multi Area', '/cliente-multi')],
            'visible_to_all' => [$this->link('Visible Home', '/')],
        ]);

        $out = menus_widget_render_inline([
            'html' => $html,
            'fields_schema' => json_encode($schema),
            '_user_data' => ['id_usuarios' => 24, 'id' => 'bia', 'id_usuarios_perfis' => 11],
        ]);

        self::assertStringContainsString('data-menu="visible"', $out);
        self::assertStringContainsString('Visible Home', $out);
        self::assertStringNotContainsString('Client Multi Area', $out);
    }

    public function testPreviewSlugForcaBlocoEItensDaCondicaoAtiva(): void
    {
        $html = $this->templateComBlocosCondicionais();
        $schema = $this->schemaCondicional([
            ['type' => 'publico', 'slug' => 'publico'],
            ['type' => 'logado', 'slug' => 'logado'],
            ['type' => 'perfil_usuario', 'slug' => 'cliente', 'profile_ids' => ['7']],
        ], [
            'publico' => [$this->link('Public Home', '/public')],
            'logado' => [$this->link('Logged Home', '/app')],
            'cliente' => [$this->link('Preview Client Area', '/preview-cliente')],
            'visible_to_all' => [$this->link('Visible Home', '/')],
        ]);

        $out = menus_widget_render_inline([
            'html' => $html,
            'fields_schema' => json_encode($schema),
            'preview_slug' => 'cliente',
            '_user_data' => ['id_usuarios' => 0, 'id' => '_anonimo', 'id_usuarios_perfis' => 0],
        ]);

        self::assertStringContainsString('data-menu="cliente"', $out);
        self::assertStringContainsString('Preview Client Area', $out);
        self::assertStringNotContainsString('Public Home', $out);
        self::assertStringNotContainsString('Visible Home', $out);
    }

    public function testFallbackUsaMenuVisibleQuandoBlocoCondicionalNaoExiste(): void
    {
        $html = <<<'HTML'
<!-- menu-visible < -->
<nav data-menu="visible"><!-- item < --><a href="[[item#url]]">[[item#label]]</a><!-- item > --></nav>
<!-- menu-visible > -->
HTML;
        $schema = $this->schemaCondicional([
            ['type' => 'logado', 'slug' => 'logado'],
        ], [
            'logado' => [$this->link('Fallback Logged', '/fallback')],
        ]);

        $out = menus_widget_render_inline([
            'html' => $html,
            'fields_schema' => json_encode($schema),
            '_user_data' => ['id_usuarios' => 44, 'id' => 'joao', 'id_usuarios_perfis' => 1],
        ]);

        self::assertStringContainsString('data-menu="visible"', $out);
        self::assertStringContainsString('Fallback Logged', $out);
    }

    private function templateComBlocosCondicionais(): string
    {
        return <<<'HTML'
<!-- menu-visible < -->
<nav data-menu="visible"><!-- item < --><a href="[[item#url]]">[[item#label]]</a><!-- item > --></nav>
<!-- menu-visible > -->
<!-- menu-conditional-publico < -->
<nav data-menu="publico"><!-- item < --><a href="[[item#url]]">[[item#label]]</a><!-- item > --></nav>
<!-- menu-conditional-publico > -->
<!-- menu-conditional-logado < -->
<nav data-menu="logado"><!-- item < --><a href="[[item#url]]">[[item#label]]</a><!-- item > --></nav>
<!-- menu-conditional-logado > -->
<!-- menu-conditional-cliente < -->
<nav data-menu="cliente"><!-- item < --><a href="[[item#url]]">[[item#label]]</a><!-- item > --></nav>
<!-- menu-conditional-cliente > -->
HTML;
    }

    /**
     * @param array<int, array<string, mixed>> $conditions
     * @param array<string, array<int, array<string, mixed>>> $menus
     * @return array<string, mixed>
     */
    private function schemaCondicional(array $conditions, array $menus): array
    {
        return [
            'availability' => 'condicional',
            'conditions' => $conditions,
            'menus' => array_merge(['visible_to_all' => []], $menus),
            'template_id' => 'menus-horizontal-navbar',
            'selected_items' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function link(string $label, string $url): array
    {
        return [
            'type' => 'link-custom',
            'label' => $label,
            'url' => $url,
            'target' => '_self',
            'css_classes' => '',
            'children' => [],
        ];
    }
}
