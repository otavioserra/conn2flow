<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FormsDomainReq273Test extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/formulario.php';
        require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/modelo.php';
        require_once CONN2FLOW_GESTOR_ROOT . '/modulos/forms/forms.widget.php';
    }

    public function testNormalizationAndFqdnBoundaries(): void
    {
        foreach (['https://MEUSITE.com.br/', ' HTTP://MEUSITE.COM.BR/// ', 'meusite.com.br'] as $raw) {
            self::assertSame('meusite.com.br', formulario_domain_normalizar($raw));
            self::assertTrue(formulario_domain_valido(formulario_domain_normalizar($raw)));
        }
        foreach (['localhost', 'foo..com', '-foo.com', 'foo-.com', 'foo_bar.com', 'a b.com', 'olá.com',
            'foo.com/path', 'foo.com:80', 'ftp://foo.com', 'foo@bar.com', 'foo.c', 'foo.123',
            str_repeat('a', 64) . '.com', implode('.', array_map(fn($n) => str_repeat('a', $n), [63, 63, 63, 62])),
            "foo.com\nbar.com", ['foo.com']] as $raw) {
            self::assertFalse(formulario_domain_valido(formulario_domain_normalizar($raw)), json_encode($raw));
        }
        self::assertSame('', formulario_domain_normalizar('https:///'));
        self::assertNull(formulario_domain_normalizar(['foo.com']));
        self::assertTrue(formulario_domain_valido(str_repeat('a', 63) . '.com'));
        self::assertTrue(formulario_domain_valido(implode('.', array_map(fn($n) => str_repeat('a', $n), [63, 63, 63, 61]))));
    }

    public function testWidgetAndBilingualEditors(): void
    {
        $html = forms_widget_render_field('<label>@[[item#label]]@</label><!-- type-input < --><input type="@[[item#type]]@" name="@[[item#name]]@" @[[item#required]]@><!-- type-input > -->',
            ['type' => 'domain', 'name' => 'host', 'label' => 'Host', 'required' => true]);
        foreach (['type="text"', 'autocapitalize="none"', 'spellcheck="false"', 'autocomplete="off"', 'required'] as $attr) {
            self::assertStringContainsString($attr, $html);
        }
        $metadata = json_decode(file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/forms/forms.json'), true);
        foreach (['pt-br' => 'Domínio', 'en' => 'Domain'] as $lang => $label) {
            $vars = array_column($metadata['resources'][$lang]['variables'], 'value', 'id');
            self::assertSame($label, $vars['field-type-domain']);
            foreach (['forms-adicionar', 'forms-editar', 'forms-clonar'] as $page) {
                self::assertStringContainsString('<option value="domain">@[[field-type-domain]]@</option>',
                    file_get_contents(CONN2FLOW_GESTOR_ROOT . "/modulos/forms/resources/$lang/pages/$page/$page.html"));
            }
            $ui = file_get_contents(CONN2FLOW_GESTOR_ROOT . "/resources/$lang/components/form-ui/form-ui.html");
            self::assertNotEmpty(modelo_tag_val($ui, '<!-- prompt-domain < -->', '<!-- prompt-domain > -->'));
            self::assertNotEmpty(modelo_tag_val($ui, '<!-- ajax-message-invalid-domain < -->', '<!-- ajax-message-invalid-domain > -->'));
        }
    }
}
