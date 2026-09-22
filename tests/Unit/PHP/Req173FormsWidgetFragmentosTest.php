<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-173 / BATCH-178 — o widget de formulários não vaza os blocos-modelo do template.
 *
 * Os templates de `target=forms` guardam no fim os blocos que o widget consome pela STRING para
 * montar opções e o botão de senha (`option-choice`, `option-select`, `password-toggle`). O que está
 * entre os marcadores é markup normal, e o renderizador nunca os removia da saída: o navegador
 * desenhava `<label>`, `<option>` e o botão soltos no fim do formulário, com marcadores crus
 * (`@[[option#type]]@`, `@[[password#input]]@`) à mostra na página pública.
 *
 * O que este teste protege:
 *
 * 1. A remoção é do bloco INTEIRO (marcadores e miolo) para os três blocos conhecidos.
 * 2. O que o cliente precisa continua no HTML: `<option>`, rádios, checkboxes e o botão de senha
 *    seguem no lugar depois da limpeza — uma remoção por regex ampla demais os levaria junto.
 * 3. Nenhum marcador do widget sobrevive na saída — é o que o usuário final enxergaria.
 * 4. Template que embrulha os blocos em `<template>` (contorno do conn2flow-site) não deixa
 *    invólucro vazio para trás.
 * 5. Marcador de bloco desconhecido perde o comentário, não o conteúdo: o template é de quem o
 *    escreveu, e apagar markup alheio seria pior que o vazamento.
 *
 * Nota apurada ao escrever o teste: `forms_widget_options_html()` e `forms_widget_wrap_password()`
 * procuram esses blocos DENTRO do bloco `item`. Nos templates do core eles estão fora, então o
 * widget sempre caiu nos modelos embutidos — os blocos do fim do arquivo nunca foram usados para
 * renderizar coisa alguma, só vazavam para a tela.
 */
final class Req173FormsWidgetFragmentosTest extends TestCase
{
    private static string $template;

    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'forms.widget.php';

        // Espelha a estrutura real dos templates do core: bloco `item` + blocos-modelo no fim.
        self::$template = ''
            . '<form class="conn2flow-form" data-form-id="@[[form_id]]@" action="@[[form_action]]@" method="post">'
            . '<!-- item < -->'
            . '<div class="campo">'
            . '<label for="@[[form_id]]@-@[[item#name]]@">@[[item#label]]@</label>'
            . '<!-- type-input < -->'
            . '<input type="@[[item#type]]@" id="@[[form_id]]@-@[[item#name]]@" name="@[[item#name]]@" @[[item#required]]@>'
            . '<!-- type-input > -->'
            . '<!-- type-select < -->'
            . '<select name="@[[item#name]]@">@[[item#options]]@</select>'
            . '<!-- type-select > -->'
            . '<!-- type-radio < -->'
            . '<div class="radios">@[[item#options]]@</div>'
            . '<!-- type-radio > -->'
            . '<!-- type-checkbox < -->'
            . '<div class="checks">@[[item#options]]@</div>'
            . '<!-- type-checkbox > -->'
            . '</div>'
            . '<!-- item > -->'
            . '<button type="submit">Enviar</button>'
            . '</form>'
            . '<!-- option-choice < --><label class="op"><input type="@[[option#type]]@" name="@[[option#name]]@" value="@[[option#value]]@" @[[option#required]]@><span>@[[option#label]]@</span></label><!-- option-choice > -->'
            . '<!-- option-select < --><option value="@[[option#value]]@">@[[option#label]]@</option><!-- option-select > -->'
            . '<!-- password-toggle < --><div class="forms-password-wrapper">@[[password#input]]@<button type="button" class="forms-password-toggle" aria-label="@[[password#aria]]@"></button></div><!-- password-toggle > -->';
    }

    private static function renderizar(string $template): string
    {
        return forms_widget_render_inline([
            'form_id' => 'form-teste',
            'html' => $template,
            'fields_schema' => json_encode([
                'form_action' => 'destino/',
                'fields' => [
                    ['type' => 'text', 'name' => 'nome', 'label' => 'Nome', 'required' => true],
                    ['type' => 'password', 'name' => 'senha', 'label' => 'Senha', 'required' => true],
                    ['type' => 'select', 'name' => 'plano', 'label' => 'Plano', 'options' => ['starter', 'pro']],
                    ['type' => 'radio', 'name' => 'ciclo', 'label' => 'Ciclo', 'options' => ['mensal', 'anual']],
                    ['type' => 'checkbox', 'name' => 'extras', 'label' => 'Extras', 'options' => ['backup']],
                ],
            ]),
        ]);
    }

    public function testSaidaNaoTemBlocosModeloNemMarcadoresCrus(): void
    {
        $html = self::renderizar(self::$template);

        foreach (['<!-- option-choice', '<!-- option-select', '<!-- password-toggle'] as $bloco) {
            self::assertStringNotContainsString($bloco, $html, "Bloco {$bloco} vazou para a saída.");
        }

        self::assertDoesNotMatchRegularExpression('/@?\[\[[a-z0-9_#-]+\]\]@?/i', $html, 'Marcador cru na saída.');
        self::assertStringNotContainsString('<!--', $html, 'Comentário de bloco remanescente na saída.');
    }

    public function testCamposContinuamOperacionais(): void
    {
        $html = self::renderizar(self::$template);

        // Limpeza ampla demais (por exemplo, apagar tudo entre `<!--` e `-->`) levaria o campo junto.
        self::assertStringContainsString('<option value="starter">starter</option>', $html);
        self::assertStringContainsString('<option value="pro">pro</option>', $html);
        self::assertStringContainsString('type="radio"', $html);
        self::assertStringContainsString('name="ciclo"', $html);
        self::assertStringContainsString('type="checkbox"', $html);
        // Checkbox de múltipla escolha sai com `[]` no name — é assim que o PHP recebe a lista.
        self::assertStringContainsString('name="extras[]"', $html);

        // Botão de exibir senha e o input que ele embrulha.
        self::assertStringContainsString('forms-password-toggle', $html);
        self::assertStringContainsString('forms-password-wrapper', $html);
        self::assertStringContainsString('name="senha"', $html);

        // Variáveis do formulário seguem resolvidas.
        self::assertStringContainsString('data-form-id="form-teste"', $html);
        self::assertStringNotContainsString('@[[form_id]]@', $html);
    }

    public function testTemplateComInvolucroTemplateNaoDeixaTagVazia(): void
    {
        $comInvolucro = str_replace(
            '<!-- option-choice < -->',
            '<template data-forms-fragments><!-- option-choice < -->',
            self::$template
        ) . '</template>';

        $html = self::renderizar($comInvolucro);

        self::assertStringNotContainsString('<template', $html, 'Invólucro vazio sobrou na saída.');
        self::assertStringContainsString('<option value="starter">starter</option>', $html);
    }

    public function testBlocoDesconhecidoPerdeOMarcadorMasMantemOConteudo(): void
    {
        $html = forms_widget_limpar_fragmentos(
            '<div>antes</div><!-- rodape-custom < --><p>conteudo do autor</p><!-- rodape-custom > --><div>depois</div>'
        );

        self::assertStringContainsString('<p>conteudo do autor</p>', $html);
        self::assertStringNotContainsString('<!-- rodape-custom', $html);
    }

    public function testLimpezaEIdempotenteEToleraEntradaVazia(): void
    {
        $html = self::renderizar(self::$template);

        self::assertSame($html, forms_widget_limpar_fragmentos($html));
        self::assertSame('', forms_widget_limpar_fragmentos(''));
    }
}
