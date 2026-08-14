<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'sitemap.php';

/**
 * req-110 (BATCH-110) — elegibilidade das páginas e manipulação do `sitemap.xml`.
 *
 * As funções cobertas aqui são PURAS: decidem o que entra no sitemap e montam/editam o XML sem
 * tocar no banco nem no disco. O caminho com banco (`sitemap_gerar_completo`) apenas orquestra estas.
 */
final class SitemapTest extends TestCase
{
    /** Página pública, ativa, dentro da janela de publicação. */
    private function paginaValida(array $sobrescrever = []): array
    {
        return array_merge([
            'caminho' => 'sobre-nos/',
            'language' => 'pt-br',
            'tipo' => 'pagina',
            'status' => 'A',
            'sem_permissao' => 1,
            'data_modificacao' => '2026-08-10 12:00:00',
            'data_publicacao_inicio' => null,
            'data_publicacao_fim' => null,
        ], $sobrescrever);
    }

    // ===== Elegibilidade

    public function testPaginaPublicaAtivaEntraNoSitemap(): void
    {
        self::assertTrue(sitemap_pagina_elegivel($this->paginaValida()));
    }

    public function testPaginaPublicaDeSistemaAGORAEntra(): void
    {
        // req-112: o `tipo` deixou de ser critério — `/signin/` e afins são `tipo='sistema'` e
        // públicas, e passam a ser indexáveis. O painel administrativo continua fora por permissão.
        self::assertTrue(sitemap_pagina_elegivel($this->paginaValida([
            'tipo' => 'sistema',
            'caminho' => 'signin/',
        ])));

        self::assertTrue(sitemap_pagina_elegivel($this->paginaValida([
            'tipo' => 'sistema',
            'caminho' => 'forgot-password/',
        ])));
    }

    public function testPainelAdministrativoContinuaForaPorExigirPermissao(): void
    {
        self::assertFalse(sitemap_pagina_elegivel($this->paginaValida([
            'tipo' => 'sistema',
            'caminho' => 'admin-paginas/',
            'sem_permissao' => null,
        ])));
    }

    public function testRotasPublicasQueNaoSaoConteudoFicamDeFora(): void
    {
        // req-112: abrir o sitemap para `tipo='sistema'` deixaria entrar callback de OAuth,
        // processador de formulário e tela de confirmação — que não são página de navegação.
        $naoIndexaveis = [
            'oauth-callback/',
            'oauth-authenticate/',
            'social-login/',
            'signin-2fa/',
            'validate-user/',
            'email-confirmation/',
            'forms-submissions-process/',
            'pagina-de-impressao/',
            'dashboard-site-toolbar/',
            'forgot-password-confirmation/',
            'redefine-password-confirmation/',
            'contact/success/',
            'admin-arquivos/emissao-teste/',
        ];

        foreach ($naoIndexaveis as $caminho) {
            self::assertFalse(
                sitemap_pagina_elegivel($this->paginaValida(['tipo' => 'sistema', 'caminho' => $caminho])),
                'Não deveria indexar: ' . $caminho
            );
        }
    }

    public function testCaminhoNaoIndexavelEhFuncaoPura(): void
    {
        self::assertTrue(sitemap_caminho_nao_indexavel('oauth-callback'));
        self::assertTrue(sitemap_caminho_nao_indexavel('/OAuth-Callback/'));
        self::assertTrue(sitemap_caminho_nao_indexavel('qualquer-coisa-confirmation'));
        self::assertTrue(sitemap_caminho_nao_indexavel('contato/success'));
        self::assertFalse(sitemap_caminho_nao_indexavel('signin'));
        self::assertFalse(sitemap_caminho_nao_indexavel('sobre-nos'));
        self::assertFalse(sitemap_caminho_nao_indexavel(''));
    }

    public function testPaginaQueExigePermissaoFicaDeFora(): void
    {
        self::assertFalse(sitemap_pagina_elegivel($this->paginaValida(['sem_permissao' => null])));
        self::assertFalse(sitemap_pagina_elegivel($this->paginaValida(['sem_permissao' => 0])));
    }

    public function testPaginaInativaOuExcluidaFicaDeFora(): void
    {
        self::assertFalse(sitemap_pagina_elegivel($this->paginaValida(['status' => 'I'])));
        self::assertFalse(sitemap_pagina_elegivel($this->paginaValida(['status' => 'D'])));
    }

    public function testRotasUtilitariasDoGestorFicamDeFora(): void
    {
        // req-112: `signin/` e `signup/` saíram desta lista — agora são indexáveis.
        foreach (['cookies-is-mandatory/', '404/', '_gestor-cookie-verify/abc/'] as $caminho) {
            self::assertFalse(
                sitemap_pagina_elegivel($this->paginaValida(['caminho' => $caminho])),
                'Deveria excluir: ' . $caminho
            );
        }
    }

    public function testCaminhoVazioFicaDeFora(): void
    {
        self::assertFalse(sitemap_pagina_elegivel($this->paginaValida(['caminho' => ''])));
        self::assertFalse(sitemap_pagina_elegivel('não é array'));
    }

    public function testJanelaDePublicacaoEhRespeitada(): void
    {
        $agora = strtotime('2026-08-13 12:00:00');

        // Agendada para o futuro: ainda não responde, então não entra.
        self::assertFalse(sitemap_pagina_elegivel(
            $this->paginaValida(['data_publicacao_inicio' => '2026-09-01 00:00:00']),
            $agora
        ));

        // Já expirada.
        self::assertFalse(sitemap_pagina_elegivel(
            $this->paginaValida(['data_publicacao_fim' => '2026-08-01 00:00:00']),
            $agora
        ));

        // Dentro da janela.
        self::assertTrue(sitemap_pagina_elegivel(
            $this->paginaValida([
                'data_publicacao_inicio' => '2026-08-01 00:00:00',
                'data_publicacao_fim' => '2026-12-31 23:59:59',
            ]),
            $agora
        ));
    }

    public function testDataZeradaDoMysqlNaoInvalidaAPagina(): void
    {
        self::assertTrue(sitemap_pagina_elegivel($this->paginaValida([
            'data_publicacao_inicio' => '0000-00-00 00:00:00',
            'data_publicacao_fim' => '0000-00-00 00:00:00',
        ])));
    }

    // ===== Montagem do XML

    public function testMontaSitemapValidoComLastmod(): void
    {
        $xml = sitemap_xml_montar([
            ['loc' => 'https://site.test/', 'lastmod' => '2026-08-10 12:00:00'],
            ['loc' => 'https://site.test/sobre-nos/', 'lastmod' => null],
        ]);

        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        self::assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        self::assertStringContainsString('<loc>https://site.test/</loc>', $xml);
        self::assertStringContainsString('<loc>https://site.test/sobre-nos/</loc>', $xml);
        self::assertSame(1, substr_count($xml, '<lastmod>'));
        self::assertNotFalse(simplexml_load_string($xml), 'O XML gerado deve ser válido.');
    }

    public function testUrlVaziaNaoGeraEntrada(): void
    {
        $xml = sitemap_xml_montar([['loc' => '   '], ['loc' => 'https://site.test/ok/']]);

        self::assertSame(1, substr_count($xml, '<url>'));
    }

    public function testSitemapSemUrlsContinuaValido(): void
    {
        $xml = sitemap_xml_montar([]);

        self::assertNotFalse(simplexml_load_string($xml));
        self::assertStringNotContainsString('<url>', $xml);
    }

    public function testDataW3c(): void
    {
        self::assertSame(date('c', strtotime('2026-08-10 12:00:00')), sitemap_data_w3c('2026-08-10 12:00:00'));
        self::assertNull(sitemap_data_w3c(null));
        self::assertNull(sitemap_data_w3c(''));
        self::assertNull(sitemap_data_w3c('0000-00-00 00:00:00'));
        self::assertNull(sitemap_data_w3c('data inválida'));
    }

    // ===== Upsert e remoção incrementais

    public function testUpsertAcrescentaUrlNova(): void
    {
        $xml = sitemap_xml_montar([['loc' => 'https://site.test/a/']]);
        $novo = sitemap_xml_upsert($xml, 'https://site.test/b/', '2026-08-13 09:00:00');

        self::assertStringContainsString('<loc>https://site.test/a/</loc>', $novo);
        self::assertStringContainsString('<loc>https://site.test/b/</loc>', $novo);
        self::assertSame(2, substr_count($novo, '<url>'));
        self::assertNotFalse(simplexml_load_string($novo));
    }

    public function testUpsertNaoDuplicaUrlExistente(): void
    {
        $xml = sitemap_xml_montar([['loc' => 'https://site.test/a/', 'lastmod' => '2026-01-01 00:00:00']]);
        $novo = sitemap_xml_upsert($xml, 'https://site.test/a/', '2026-08-13 09:00:00');

        self::assertSame(1, substr_count($novo, '<url>'));
        self::assertStringContainsString(date('c', strtotime('2026-08-13 09:00:00')), $novo);
        self::assertStringNotContainsString(date('c', strtotime('2026-01-01 00:00:00')), $novo);
    }

    public function testUpsertPreservaEntradasQueNaoMudaram(): void
    {
        $xml = sitemap_xml_montar([
            ['loc' => 'https://site.test/a/'],
            ['loc' => 'https://site.test/b/'],
            ['loc' => 'https://site.test/c/'],
        ]);

        $novo = sitemap_xml_upsert($xml, 'https://site.test/b/', '2026-08-13 09:00:00');

        self::assertStringContainsString('<loc>https://site.test/a/</loc>', $novo);
        self::assertStringContainsString('<loc>https://site.test/c/</loc>', $novo);
        self::assertSame(3, substr_count($novo, '<url>'));
    }

    public function testUpsertSobreArquivoInvalidoGeraSitemapNovo(): void
    {
        $novo = sitemap_xml_upsert('lixo que não é xml', 'https://site.test/a/');

        self::assertStringContainsString('<urlset', $novo);
        self::assertStringContainsString('<loc>https://site.test/a/</loc>', $novo);
        self::assertNotFalse(simplexml_load_string($novo));
    }

    public function testRemocaoTiraApenasAUrlAlvo(): void
    {
        $xml = sitemap_xml_montar([
            ['loc' => 'https://site.test/a/'],
            ['loc' => 'https://site.test/b/'],
        ]);

        $novo = sitemap_xml_remover($xml, 'https://site.test/a/');

        self::assertStringNotContainsString('https://site.test/a/', $novo);
        self::assertStringContainsString('https://site.test/b/', $novo);
        self::assertSame(1, substr_count($novo, '<url>'));
        self::assertNotFalse(simplexml_load_string($novo));
    }

    public function testRemocaoDeUrlInexistenteNaoAlteraNada(): void
    {
        $xml = sitemap_xml_montar([['loc' => 'https://site.test/a/']]);

        self::assertSame($xml, sitemap_xml_remover($xml, 'https://site.test/z/'));
    }

    // ===== req-112: troca de slug

    public function testTrocaDeSlugTiraAUrlAntigaEDeixaSoANova(): void
    {
        $xml = sitemap_xml_montar([
            ['loc' => 'https://site.test/pagina-antiga/'],
            ['loc' => 'https://site.test/outra/'],
        ]);

        // Emula o que `sitemap_sincronizar_pagina()` faz: remove a antiga, depois faz upsert da nova.
        $xml = sitemap_xml_remover($xml, 'https://site.test/pagina-antiga/');
        $xml = sitemap_xml_upsert($xml, 'https://site.test/pagina-nova/', '2026-08-14 10:00:00');

        self::assertStringNotContainsString('pagina-antiga', $xml);
        self::assertStringContainsString('<loc>https://site.test/pagina-nova/</loc>', $xml);
        self::assertStringContainsString('<loc>https://site.test/outra/</loc>', $xml);
        self::assertSame(2, substr_count($xml, '<url>'));
        self::assertNotFalse(simplexml_load_string($xml));
    }

    public function testArquivoVaiParaAssets(): void
    {
        // req-112: na raiz dependia da regra `!-f` do .htaccess; em assets/ quem serve é o
        // controlador de estáticos do core.
        self::assertStringContainsString(
            'assets' . DIRECTORY_SEPARATOR . 'sitemap.xml',
            sitemap_caminho_arquivo()
        );
    }

    public function testUrlComQuerystringEscapadaEhReconhecida(): void
    {
        $loc = 'https://site.test/busca/?q=a&b=1';
        $xml = sitemap_xml_montar([['loc' => $loc]]);

        self::assertStringContainsString('&amp;', $xml);
        self::assertSame(0, substr_count(sitemap_xml_remover($xml, $loc), '<url>'));
    }
}
