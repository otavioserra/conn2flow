<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-130 — fallbacks de e-mail, action pública e configurações do módulo Forms.
 */
final class Req130FormsHardeningTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'modelo.php';
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'formulario.php';
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'forms.widget.php';
    }

    public function testActionVaziaUsaProcessadorCanonico(): void
    {
        self::assertSame('/forms-submissions-process/', formulario_form_action_resolver('', '/'));
        self::assertSame('/site/forms-submissions-process/', formulario_form_action_resolver('   ', '/site/'));
        self::assertSame('/site/forms-submissions-process/', formulario_form_action_resolver(null, '/site'));
    }

    public function testActionCustomizadaEPreservadaENormalizada(): void
    {
        self::assertSame('/site/api/forms/submit', formulario_form_action_resolver(' /api/forms/submit ', '/site/'));
        self::assertSame('/site/custom/', forms_widget_form_action('/custom/', '/site/'));
    }

    public function testWidgetRenderizaFallbackNoAtributoAction(): void
    {
        global $_GESTOR;

        $urlRaizAnterior = $_GESTOR['url-raiz'] ?? null;
        $_GESTOR['url-raiz'] = '/projeto/';

        try {
            $html = forms_widget_render_inline([
                'form_id' => 'contato',
                'html' => '<form action="@[[form_action]]@"><!-- item < --><input><!-- item > --></form>',
                'fields_schema' => json_encode(['form_action' => '', 'fields' => []]),
            ]);
        } finally {
            $_GESTOR['url-raiz'] = $urlRaizAnterior;
        }

        self::assertStringContainsString('action="/projeto/forms-submissions-process/"', $html);
    }

    public function testAssuntoVazioRecuaParaPadraoECabecalhoEBlindado(): void
    {
        self::assertSame('Assunto padrão', formulario_email_assunto_resolver('   ', 'Assunto padrão'));
        self::assertSame('Contato #code#', formulario_email_assunto_resolver(' Contato #code# ', 'Padrão'));
        self::assertSame('Ataque Cc: vítima', formulario_email_assunto_resolver("Ataque\r\nCc: vítima", 'Padrão'));
    }

    public function testReplyToIgnoraValorInvalidoEUsaFallbackSeguro(): void
    {
        self::assertSame(
            'sistema@example.com',
            formulario_email_reply_to_resolver('invalido', "ataque@example.com\r\nBcc: alvo@example.com", 'sistema@example.com')
        );
        self::assertSame(
            'Visitante Nome',
            formulario_email_reply_to_nome_resolver('', "<b>Visitante</b>\r\nNome", 'Sistema')
        );
        self::assertNull(formulario_email_reply_to_resolver('', null, []));
    }

    public function testTemplateDeEmailPreencheVariaveisECelulasComDadosSanitizados(): void
    {
        $template = '<h1>#code# — #formName#</h1>'
            . '<!-- cel < --><p>#label#|#valor#|#valor_full#</p><!-- cel > -->';
        $fields = [
            ['name' => 'nome', 'label' => 'Nome', 'type' => 'text'],
            ['name' => 'email', 'label' => 'E-mail', 'type' => 'email'],
            ['name' => 'interesses', 'label' => 'Interesses', 'type' => 'checkbox'],
            ['name' => 'senha', 'label' => 'Senha', 'type' => 'password'],
        ];

        $html = formulario_email_template_processar($template, '2026082412', 'Contato', $fields, [
            'nome' => '<script>alert(1)</script>Maria',
            'email' => 'maria@example.com',
            'interesses' => ['IA', 'Automação'],
            'senha' => 'segredo',
        ]);

        self::assertStringContainsString('2026082412 — Contato', $html);
        self::assertStringContainsString('Nome|&lt;script&gt;alert(1)&lt;/script&gt;Maria', $html);
        self::assertStringContainsString('mailto:maria@example.com', $html);
        self::assertStringContainsString('Interesses|IA, Automação|IA, Automação', $html);
        self::assertStringNotContainsString('segredo', $html);
        self::assertStringNotContainsString('#code#', $html);
        self::assertStringNotContainsString('#label#', $html);
    }

    public function testComponenteInexistenteTambemRecuaParaTemplatePadrao(): void
    {
        $carregados = [];
        $carregar = static function (string $id) use (&$carregados): string {
            $carregados[] = $id;

            return $id === 'forms-prepared-email' ? '<p>Template canônico</p>' : '';
        };

        self::assertSame(
            '<p>Template canônico</p>',
            formulario_email_mensagem_resolver(['message_component' => 'componente-inexistente'], $carregar)
        );
        self::assertSame(['componente-inexistente', 'forms-prepared-email'], $carregados);

        $carregados = [];
        self::assertSame('<p>Template canônico</p>', formulario_email_mensagem_resolver([], $carregar));
        self::assertSame(['forms-prepared-email'], $carregados);
    }
}
