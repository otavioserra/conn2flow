<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
    . DIRECTORY_SEPARATOR . 'presentations' . DIRECTORY_SEPARATOR . 'presentations.widget.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
    . DIRECTORY_SEPARATOR . 'cookie-consent' . DIRECTORY_SEPARATOR . 'cookie-consent.widget.php';

/**
 * req-208 / BATCH-216 — widgets `presentations` e `cookie-consent`.
 *
 * Cobre o que o servidor decide: contagem de slides, blocos de controle, normalização do schema,
 * escape do que o painel grava e o contrato entre o modelo e o controlador público.
 */
final class PresentationsAndCookieConsentReq208Test extends TestCase
{
    private static function modelo(string $modulo, string $id, string $lang, string $ext): string
    {
        return (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . $modulo
            . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . 'templates'
            . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . $id . '.' . $ext);
    }

    // ----- presentations

    public function testContaSoSecoesComDataSlide(): void
    {
        $html = '<section data-slide class="a"></section><section class="b"></section>'
            . '<SECTION class="c" data-slide></SECTION><section data-slide="x"></section><section data-slider></section>';

        self::assertSame(3, presentations_widget_contar_slides($html));
        self::assertSame(0, presentations_widget_contar_slides('<div data-slide></div>'));
        // Comentário que cita a marcação não é slide.
        self::assertSame(1, presentations_widget_contar_slides('<!-- cada <section data-slide> é um slide --><section data-slide></section>'));
    }

    public function testSchemaDescartaValorForaDaFaixa(): void
    {
        $schema = presentations_schema_decode(json_encode([
            'mode' => 'popup', 'transition' => '"><script>', 'height' => 10, 'autoplay_speed' => 5,
            'loop' => 'true', 'keyboard' => '0', 'desconhecida' => 'x',
        ]));

        self::assertSame('fullscreen', $schema['mode']);
        self::assertSame('random', $schema['transition']);
        self::assertSame(600, $schema['height']);
        self::assertSame(8000, $schema['autoplay_speed']);
        self::assertTrue($schema['loop']);
        self::assertFalse($schema['keyboard']);
        self::assertArrayNotHasKey('desconhecida', $schema);
        self::assertSame(presentations_schema_default(), presentations_schema_decode('não é json'));
    }

    public function testModeloRendeUmPontoPorSlideEOsTotais(): void
    {
        foreach (['pt-br', 'en'] as $lang) {
            $html = presentations_widget_render_inline([
                'html' => self::modelo('presentations', 'presentations-deck', $lang, 'html'),
                'fields_schema' => json_encode(['mode' => 'embedded', 'height' => 480, 'transition' => 'zoom']),
            ]);

            self::assertSame(3, substr_count($html, 'data-c2f-deck-dot="'), $lang);
            self::assertStringContainsString('data-c2f-deck-dot="2"', $html);
            self::assertStringContainsString('data-mode="embedded"', $html);
            self::assertStringContainsString('data-transition="zoom"', $html);
            self::assertStringContainsString('--c2f-deck-height: 480px', $html);
            self::assertStringContainsString('</span> / 3</span>', $html);
            self::assertSame(1, substr_count($html, 'data-c2f-deck-prev'));
            self::assertSame(1, substr_count($html, 'data-c2f-deck-next'));
            // Nenhum marcador de bloco nem variável sobra na saída.
            self::assertDoesNotMatchRegularExpression('/<!--\s*(controls-|dot-item)|\[\[[a-z_#-]+\]\]/', $html);
        }
    }

    public function testOpcoesDesligadasRemovemOsControles(): void
    {
        $html = presentations_widget_render_inline([
            'html' => self::modelo('presentations', 'presentations-deck', 'pt-br', 'html'),
            'fields_schema' => json_encode([
                'show_arrows' => false, 'show_dots' => false, 'show_counter' => false, 'show_progress' => false, 'show_fullscreen' => false,
            ]),
        ]);

        foreach (['data-c2f-deck-prev', 'data-c2f-deck-next', 'data-c2f-deck-dot=', 'data-c2f-deck-current', 'data-c2f-deck-progress', 'data-c2f-deck-fullscreen'] as $controle) {
            self::assertStringNotContainsString($controle, $html, $controle);
        }
        self::assertSame(3, presentations_widget_contar_slides($html));
    }

    public function testUmSlideSoNaoTemNavegacao(): void
    {
        $html = presentations_widget_render_inline([
            'html' => '<div data-c2f-deck><section data-slide></section><!-- controls-arrows < --><b data-c2f-deck-next></b><!-- controls-arrows > -->'
                . '<!-- controls-dots < --><!-- dot-item < --><i data-c2f-deck-dot="[[dot#index]]"></i><!-- dot-item > --><!-- controls-dots > -->'
                . '<!-- controls-fullscreen < --><u data-c2f-deck-fullscreen></u><!-- controls-fullscreen > --></div>',
        ]);

        self::assertStringNotContainsString('data-c2f-deck-next', $html);
        self::assertStringNotContainsString('data-c2f-deck-dot', $html);
        self::assertStringContainsString('data-c2f-deck-fullscreen', $html);
    }

    public function testModeloEControladorDaApresentacaoFalamOsMesmosAtributos(): void
    {
        $js = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/presentations/presentations.widget.js');
        $html = self::modelo('presentations', 'presentations-deck', 'pt-br', 'html');
        $css = self::modelo('presentations', 'presentations-deck', 'pt-br', 'css');

        foreach (['data-c2f-deck', 'data-c2f-deck-stage', 'data-slide', 'data-c2f-deck-prev', 'data-c2f-deck-next', 'data-c2f-deck-dot',
            'data-c2f-deck-current', 'data-c2f-deck-progress', 'data-c2f-deck-fullscreen', 'data-c2f-deck-goto'] as $atributo) {
            self::assertStringContainsString($atributo, $html, $atributo);
            self::assertStringContainsString($atributo, $js, $atributo);
        }
        // Classe que o controlador liga tem de ter regra no CSS do modelo.
        foreach (['is-active', 'is-ready', 'c2f-deck-lock', 'c2f-anim-fade', 'c2f-anim-zoom', 'c2f-anim-slide-up', 'c2f-anim-slide-left', 'c2f-anim-slide-right'] as $classe) {
            self::assertStringContainsString($classe, $js, $classe);
            self::assertStringContainsString($classe, $css, $classe);
        }
        // Sem função global nem `onclick`, que era como a apresentação feita à mão navegava.
        self::assertStringNotContainsString('onclick', $html);
    }

    // ----- cookie-consent

    public function testCategoriasInvalidasCaemNasDeFabrica(): void
    {
        $padrao = cookie_consent_schema_decode('{}');
        self::assertSame(['necessary', 'preferences', 'analytics', 'marketing'], array_column($padrao['categories'], 'id'));
        self::assertSame([true, false, false, false], array_column($padrao['categories'], 'required'));

        $schema = cookie_consent_schema_decode(json_encode([
            'position' => 'top', 'expiry_days' => 9999, 'version' => '  ',
            'categories' => [
                ['id' => 'Analytics & Ads', 'name' => 'A', 'required' => 'false'],
                ['id' => 'analytics-ads', 'name' => 'repetida'],
                ['id' => '', 'name' => 'sem id'],
                'lixo',
            ],
        ]));

        self::assertSame('bottom-left', $schema['position']);
        self::assertSame(180, $schema['expiry_days']);
        self::assertSame('1', $schema['version']);
        self::assertSame(['analytics-ads'], array_column($schema['categories'], 'id'));
        self::assertFalse($schema['categories'][0]['required']);
    }

    public function testEnderecoSoAceitaHttpAncoraMailtoOuCaminhoDoSite(): void
    {
        $GLOBALS['_GESTOR']['url-raiz'] = '/site/';

        self::assertSame('/site/privacidade/', cookie_consent_widget_url('privacidade/'));
        self::assertSame('/site/privacidade/', cookie_consent_widget_url('/privacidade/'));
        self::assertSame('https://exemplo.com/p', cookie_consent_widget_url('https://exemplo.com/p'));
        self::assertSame('#topo', cookie_consent_widget_url('#topo'));
        self::assertSame('', cookie_consent_widget_url('javascript:alert(1)'));
        self::assertSame('', cookie_consent_widget_url('data:text/html,x'));
        self::assertSame('', cookie_consent_widget_url('  '));
    }

    public function testModeloRendeCategoriasTextosELinksComEscape(): void
    {
        $GLOBALS['_GESTOR']['url-raiz'] = '/';

        foreach (['cookie-consent-card', 'cookie-consent-card-light'] as $modelo) {
            foreach (['pt-br', 'en'] as $lang) {
                $html = cookie_consent_widget_render_inline([
                    'html' => self::modelo('cookie-consent', $modelo, $lang, 'html'),
                    'fields_schema' => json_encode([
                        'version' => '3', 'expiry_days' => 90, 'position' => 'bottom', 'policy_url' => 'privacidade/',
                        'texts' => ['title' => 'Título <b>"x"</b>', 'accept_all' => 'Aceitar'],
                        'categories' => [
                            ['id' => 'necessary', 'name' => 'Necessários', 'description' => 'Base', 'required' => true],
                            ['id' => 'analytics', 'name' => 'Estatísticas <script>', 'description' => '', 'required' => false],
                        ],
                    ]),
                ]);
                $caso = $modelo . '/' . $lang;

                self::assertSame(2, substr_count($html, 'data-cc-category="'), $caso);
                self::assertMatchesRegularExpression('/data-cc-category="necessary"\s+checked\s+disabled/', $html, $caso);
                self::assertMatchesRegularExpression('/data-cc-category="analytics"\s*>/', $html, $caso);
                self::assertStringContainsString('Título &lt;b&gt;&quot;x&quot;&lt;/b&gt;', $html, $caso);
                self::assertStringNotContainsString('<script>', $html, $caso);
                self::assertStringContainsString('data-version="3"', $html, $caso);
                self::assertStringContainsString('data-expiry-days="90"', $html, $caso);
                self::assertStringContainsString('data-position="bottom"', $html, $caso);
                self::assertStringContainsString('href="/privacidade/"', $html, $caso);
                // Sem endereço dos termos, o link dos termos não aparece; o botão flutuante vem por padrão.
                self::assertStringNotContainsString('terms_url', $html, $caso);
                self::assertSame(1, substr_count($html, 'data-cc-open'), $caso);
                self::assertDoesNotMatchRegularExpression('/<!--\s*(category-|policy-link|terms-link|floating-button)|\[\[[a-z_#-]+\]\]/', $html, $caso);
            }
        }
    }

    public function testRecusarTemOMesmoPesoDeAceitar(): void
    {
        // LGPD e GDPR: a recusa não pode ser mais difícil nem menos visível que o aceite.
        $html = self::modelo('cookie-consent', 'cookie-consent-card', 'pt-br', 'html');
        preg_match('/<section class="c2f-cc-banner"[\s\S]*?<\/section>/', $html, $aviso);

        self::assertMatchesRegularExpression('/class="([^"]+)" data-cc-accept/', $aviso[0]);
        preg_match('/class="([^"]+)" data-cc-accept/', $aviso[0], $aceitar);
        preg_match('/class="([^"]+)" data-cc-reject/', $aviso[0], $recusar);
        self::assertSame($aceitar[1], $recusar[1]);
    }

    public function testModeloEControladorDoAvisoFalamOsMesmosAtributos(): void
    {
        $js = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/cookie-consent/cookie-consent.widget.js');
        $html = self::modelo('cookie-consent', 'cookie-consent-card', 'pt-br', 'html');

        foreach (['data-c2f-cookie-consent', 'data-cc-banner', 'data-cc-panel', 'data-cc-accept', 'data-cc-reject', 'data-cc-customize',
            'data-cc-save', 'data-cc-close', 'data-cc-open', 'data-cc-category', 'data-version', 'data-expiry-days', 'data-consent-mode', 'data-preview'] as $atributo) {
            self::assertStringContainsString($atributo, $html, $atributo);
            self::assertStringContainsString($atributo, $js, $atributo);
        }
        // As três peças nascem escondidas: sem JavaScript, nada cobre a página.
        self::assertSame(3, preg_match_all('/data-cc-(banner|panel|open)[^>]*\shidden/', $html));
    }

    // ----- registro dos módulos

    public function testModulosRegistradosNosDoisIdiomasComVariaveisIguais(): void
    {
        foreach (['presentations', 'cookie-consent'] as $modulo) {
            $json = json_decode((string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/' . $modulo . '/' . $modulo . '.json'), true);
            self::assertIsArray($json, $modulo);

            $pt = array_column($json['resources']['pt-br']['variables'], 'id');
            $en = array_column($json['resources']['en']['variables'], 'id');
            self::assertSame($pt, $en, $modulo);
            self::assertContains('js-copied', $pt);

            foreach (['pt-br', 'en'] as $lang) {
                $modulos = json_decode((string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/resources/' . $lang . '/modules.json'), true);
                self::assertContains($modulo, array_column($modulos, 'id'), $modulo . '/' . $lang);

                foreach ($json['resources'][$lang]['templates'] as $t) {
                    self::assertNotSame('', trim(self::modelo($modulo, $t['id'], $lang, 'html')), $t['id']);
                    self::assertNotSame('', trim(self::modelo($modulo, $t['id'], $lang, 'css')), $t['id']);
                }
            }
        }

        // Todo texto padrão do aviso tem variável: schema sem texto não rende cartão mudo.
        $ids = array_column(json_decode((string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/cookie-consent/cookie-consent.json'), true)['resources']['pt-br']['variables'], 'id');
        foreach (cookie_consent_text_keys() as $chave) {
            self::assertContains('default-text-' . str_replace('_', '-', $chave), $ids);
        }
        foreach (cookie_consent_category_ids() as $id) {
            self::assertContains('default-category-' . $id . '-name', $ids);
            self::assertContains('default-category-' . $id . '-description', $ids);
        }
    }
}
