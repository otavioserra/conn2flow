<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Cobre os novos tipos de campo e diretivas de limite do renderer público de Forms
 * (BATCH-071): password, date, url, hidden + atributos min/max/step/minlength/maxlength.
 * Exercita apenas funções puras de forms.widget.php (sem banco).
 */
final class FormsWidgetFieldTypesTest extends TestCase
{
    private static string $itemTemplate;

    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'forms.widget.php';

        // Template mínimo espelhando a estrutura real do bloco item dos templates de forms.
        self::$itemTemplate = ''
            . '<div class="conn2flow-form-field">'
            . '<label for="f-@[[item#name]]@">@[[item#label]]@</label>'
            . '<!-- type-input < -->'
            . '<input class="base" type="@[[item#type]]@" id="f-@[[item#name]]@" name="@[[item#name]]@" placeholder="@[[item#placeholder]]@" value="@[[item#value]]@" @[[item#required]]@>'
            . '<!-- type-input > -->'
            . '<!-- type-textarea < -->'
            . '<textarea class="base" name="@[[item#name]]@" @[[item#required]]@></textarea>'
            . '<!-- type-textarea > -->'
            . '<!-- type-select < -->'
            . '<select name="@[[item#name]]@">@[[item#options]]@</select>'
            . '<!-- type-select > -->'
            . '</div>';
    }

    public function testParseLimitsLeMinMaxStep(): void
    {
        $limits = forms_widget_parse_limits("min:5\nmax:15\nstep:0.5");
        self::assertSame('5', $limits['min']);
        self::assertSame('15', $limits['max']);
        self::assertSame('0.5', $limits['step']);

        $vazio = forms_widget_parse_limits([]);
        self::assertNull($vazio['min']);
        self::assertNull($vazio['max']);
        self::assertNull($vazio['step']);
    }

    public function testHiddenDefaultAceitaArrayEString(): void
    {
        self::assertSame('valor-padrao', forms_widget_hidden_default(['valor-padrao']));
        self::assertSame('abc', forms_widget_hidden_default('  abc  '));
        self::assertSame('', forms_widget_hidden_default([]));
    }

    public function testTextInjetaMinlengthMaxlength(): void
    {
        $html = forms_widget_render_field(self::$itemTemplate, [
            'type' => 'text', 'name' => 'nome', 'label' => 'Nome', 'options' => ['min:5', 'max:15'],
        ]);
        self::assertStringContainsString('minlength="5"', $html);
        self::assertStringContainsString('maxlength="15"', $html);
        self::assertStringContainsString('type="text"', $html);
    }

    public function testTextareaInjetaMinlengthMaxlength(): void
    {
        $html = forms_widget_render_field(self::$itemTemplate, [
            'type' => 'textarea', 'name' => 'msg', 'label' => 'Mensagem', 'options' => ['min:10', 'max:200'],
        ]);
        self::assertMatchesRegularExpression('/<textarea\b[^>]*minlength="10"/', $html);
        self::assertMatchesRegularExpression('/<textarea\b[^>]*maxlength="200"/', $html);
    }

    public function testNumberInjetaMinMaxStep(): void
    {
        $html = forms_widget_render_field(self::$itemTemplate, [
            'type' => 'number', 'name' => 'idade', 'label' => 'Idade', 'options' => ['min:18', 'max:100', 'step:1'],
        ]);
        self::assertStringContainsString('type="number"', $html);
        self::assertStringContainsString('min="18"', $html);
        self::assertStringContainsString('max="100"', $html);
        self::assertStringContainsString('step="1"', $html);
    }

    public function testDateInjetaFaixaEClassePicker(): void
    {
        $html = forms_widget_render_field(self::$itemTemplate, [
            'type' => 'date', 'name' => 'data', 'label' => 'Data', 'options' => ['min:2026-01-01', 'max:2026-12-31'],
        ]);
        self::assertStringContainsString('type="date"', $html);
        self::assertStringContainsString('min="2026-01-01"', $html);
        self::assertStringContainsString('max="2026-12-31"', $html);
        self::assertStringContainsString('forms-date-picker', $html);
    }

    public function testUrlRenderizaTipoUrl(): void
    {
        $html = forms_widget_render_field(self::$itemTemplate, [
            'type' => 'url', 'name' => 'site', 'label' => 'Site', 'options' => [],
        ]);
        self::assertStringContainsString('type="url"', $html);
    }

    public function testHiddenRenderizaSomenteInputComValorPadrao(): void
    {
        $html = forms_widget_render_field(self::$itemTemplate, [
            'type' => 'hidden', 'name' => 'origem', 'label' => 'Origem', 'options' => ['campanha-2026'],
        ]);
        // Apenas a tag input, sem label nem wrapper visual.
        self::assertStringStartsWith('<input', trim($html));
        self::assertStringNotContainsString('<label', $html);
        self::assertStringNotContainsString('conn2flow-form-field', $html);
        self::assertStringContainsString('type="hidden"', $html);
        self::assertStringContainsString('value="campanha-2026"', $html);
    }

    public function testPasswordEnvolveComToggle(): void
    {
        $html = forms_widget_render_field(self::$itemTemplate, [
            'type' => 'password', 'name' => 'senha', 'label' => 'Senha', 'options' => [],
        ]);
        self::assertStringContainsString('type="password"', $html);
        self::assertStringContainsString('forms-password-wrapper', $html);
        self::assertStringContainsString('forms-password-toggle', $html);
        self::assertStringContainsString('forms-password-icon-eye', $html);
    }
}
