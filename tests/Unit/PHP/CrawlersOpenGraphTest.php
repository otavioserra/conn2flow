<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-109 (BATCH-109) — isenção de crawlers, sanitização de páginas de sistema e OpenGraph.
 *
 * Cobre as funções PURAS da biblioteca `gestor`, que são o contrato consumido pelo bootstrap
 * (`gestor/gestor.php`): detecção de robôs sociais, identificação de rotas de sistema e montagem
 * das metatags OpenGraph.
 *
 * req-111 (CR-001): os casos de `gestor_rastreamento_remover()` saíram junto com a função — o
 * bloqueio de analytics do req-109 §3/§4 foi revertido.
 */
final class CrawlersOpenGraphTest extends TestCase
{
    // ===== Detecção de crawlers

    public function testDetectaOsScrapersSociaisQueGeramPreviewDeLink(): void
    {
        $agentes = [
            'WhatsApp/2.23.20.0 A',
            'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
            'Mozilla/5.0 (compatible; Twitterbot/1.0)',
            'LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com)',
            'TelegramBot (like TwitterBot)',
            'Mozilla/5.0 (compatible; Discordbot/2.0; +https://discordapp.com)',
            'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
            'Pinterest/0.2 (+https://www.pinterest.com/bot.html)',
            'meta-externalagent/1.1',
        ];

        foreach ($agentes as $agente) {
            self::assertTrue(gestor_crawler_detectar($agente), 'Deveria reconhecer: ' . $agente);
        }
    }

    public function testDetectaOsBuscadores(): void
    {
        self::assertTrue(gestor_crawler_detectar('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'));
        self::assertTrue(gestor_crawler_detectar('Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'));
        self::assertTrue(gestor_crawler_detectar('Mozilla/5.0 (compatible; YandexBot/3.0)'));
    }

    public function testNaoConfundeNavegadorHumanoComCrawler(): void
    {
        $humanos = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Gecko/20100101 Firefox/125.0',
        ];

        foreach ($humanos as $agente) {
            self::assertFalse(gestor_crawler_detectar($agente), 'Não deveria marcar como bot: ' . $agente);
        }
    }

    public function testUserAgentAusenteOuInvalidoNaoEhCrawler(): void
    {
        self::assertFalse(gestor_crawler_detectar(null));
        self::assertFalse(gestor_crawler_detectar(''));
        self::assertFalse(gestor_crawler_detectar('   '));
        self::assertFalse(gestor_crawler_detectar(false));
        self::assertFalse(gestor_crawler_detectar(['WhatsApp']));
    }

    public function testDeteccaoIgnoraCaixa(): void
    {
        self::assertTrue(gestor_crawler_detectar('WHATSAPP/2.0'));
        self::assertTrue(gestor_crawler_detectar('facebookExternalHit/1.1'));
    }

    // ===== req-111 (CR-001): baseline ampliado + tokens configuráveis

    public function testBaselineCobreBotsDeAnuncioEAuditoria(): void
    {
        $agentes = [
            'AdsBot-Google (+http://www.google.com/adsbot.html)',
            'Mediapartners-Google',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36 Chrome-Lighthouse',
            'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)',
            'Mozilla/5.0 (compatible; SemrushBot/7~bl)',
            'Mozilla/5.0 (compatible; UptimeRobot/2.0; http://www.uptimerobot.com/)',
            'GoogleOther',
        ];

        foreach ($agentes as $agente) {
            self::assertTrue(gestor_crawler_detectar($agente), 'Baseline deveria cobrir: ' . $agente);
        }
    }

    public function testBaselineEhSempreAtivoENaoDependeDeConfiguracao(): void
    {
        // Sem nenhum token extra, o baseline continua reconhecendo os scrapers de preview — é o que
        // impede o OpenGraph de página protegida de regredir quando a lista extra está desligada.
        self::assertTrue(gestor_crawler_detectar('WhatsApp/2.23.20.0 A', []));
        self::assertNotEmpty(gestor_crawler_tokens_padrao());
    }

    public function testTokenExtraPassaAValerSemTocarNoBaseline(): void
    {
        $agente = 'MeuRoboInterno/1.0';

        self::assertFalse(gestor_crawler_detectar($agente, []));
        self::assertTrue(gestor_crawler_detectar($agente, ['meurobointerno']));
        // O baseline segue valendo junto com os extras.
        self::assertTrue(gestor_crawler_detectar('WhatsApp/2.0', ['meurobointerno']));
    }

    public function testNormalizacaoDaListaDigitadaPeloOperador(): void
    {
        self::assertSame(
            ['meurobo', 'outro-bot', 'terceiro'],
            gestor_crawler_tokens_normalizar("MeuRobo, outro-bot\n Terceiro ")
        );

        // Ponto e vírgula, duplicatas e entradas vazias não geram token inútil.
        self::assertSame(['a', 'b'], gestor_crawler_tokens_normalizar('a; b;; a ,'));
        self::assertSame([], gestor_crawler_tokens_normalizar(''));
        self::assertSame([], gestor_crawler_tokens_normalizar('   '));
        self::assertSame([], gestor_crawler_tokens_normalizar(null));
    }

    // ===== req-111 (CR-001): blindagem do laço de redirecionamento de cookie
    //
    // Defeito MEDIDO em produção antes da correção (snapphoton.com e conn2flow.com):
    //   `/` → `_gestor-cookie-verify/<id>/?url=` → `cookies-is-mandatory/` →
    //   `_gestor-cookie-verify/<id>/?url=cookies-is-mandatory%2F` → `cookies-is-mandatory/` → …
    // Cliente sem cookie (Googlebot não persiste cookie) nunca chegava a um 200.

    public function testPaginaPublicaNuncaRedirecionaEmiteOCookieESegue(): void
    {
        self::assertSame('emitir', gestor_cookie_verificacao_desfecho([
            'crawler' => false,
            'tem_cookie' => false,
            'exigir_sessao' => false,
            'caminho' => 'sobre-nos/',
        ]));
    }

    public function testCookiesIsMandatoryNAOReentraNoRedirecionamento(): void
    {
        // Esta é a asserção que impede o laço de voltar: mesmo num fluxo que exigiria sessão, uma
        // rota de sistema apenas emite o cookie e devolve a página.
        foreach (['cookies-is-mandatory/', '_gestor-cookie-verify/abc123/', '404/'] as $caminho) {
            self::assertSame('emitir', gestor_cookie_verificacao_desfecho([
                'crawler' => false,
                'tem_cookie' => false,
                'exigir_sessao' => true,
                'caminho' => $caminho,
            ]), 'Rota de sistema não pode redirecionar: ' . $caminho);
        }
    }

    public function testFluxoDeLoginContinuaProvandoOCookie(): void
    {
        self::assertSame('redirecionar', gestor_cookie_verificacao_desfecho([
            'crawler' => false,
            'tem_cookie' => false,
            'exigir_sessao' => true,
            'caminho' => 'signin/',
        ]));
    }

    public function testRoboEQuemJaTemCookieNaoEntramNoFluxo(): void
    {
        self::assertSame('ignorar', gestor_cookie_verificacao_desfecho([
            'crawler' => true,
            'tem_cookie' => false,
            'exigir_sessao' => true,
            'caminho' => 'signin/',
        ]));

        self::assertSame('ignorar', gestor_cookie_verificacao_desfecho([
            'crawler' => false,
            'tem_cookie' => true,
            'exigir_sessao' => true,
            'caminho' => 'signin/',
        ]));
    }

    public function testDesfechoComEntradaVaziaNaoRedireciona(): void
    {
        // Sem contexto nenhum, o desfecho seguro é emitir o cookie e servir a página.
        self::assertSame('emitir', gestor_cookie_verificacao_desfecho(false));
        self::assertSame('emitir', gestor_cookie_verificacao_desfecho([]));
    }

    // ===== Rotas de sistema (usadas pela elegibilidade do sitemap)

    public function testPaginasDeSistemaSaoIdentificadas(): void
    {
        self::assertTrue(gestor_pagina_rota_sistema('cookies-is-mandatory/'));
        self::assertTrue(gestor_pagina_rota_sistema('/cookies-is-mandatory/'));
        self::assertTrue(gestor_pagina_rota_sistema('COOKIES-IS-MANDATORY/'));
        self::assertTrue(gestor_pagina_rota_sistema('_gestor-cookie-verify/abc123/'));
        self::assertTrue(gestor_pagina_rota_sistema('404/'));
        self::assertTrue(gestor_pagina_rota_sistema('500/'));
    }

    public function testPaginasDeConteudoNaoSaoTratadasComoSistema(): void
    {
        self::assertFalse(gestor_pagina_rota_sistema(''));
        self::assertFalse(gestor_pagina_rota_sistema('/'));
        self::assertFalse(gestor_pagina_rota_sistema('blog/artigo-404-erros-comuns/'));
        self::assertFalse(gestor_pagina_rota_sistema('cookies-is-mandatory-explicacao/'));
        self::assertFalse(gestor_pagina_rota_sistema('dashboard/'));
    }

    // ===== OpenGraph

    public function testMontaAsSeisTagsOpenGraphComEscape(): void
    {
        $tags = gestor_open_graph_tags([
            'title' => 'Notícia "importante" & atual',
            'description' => 'Resumo da notícia',
            'image' => 'https://site.test/contents/banner.jpg',
            'url' => 'https://site.test/noticias/importante/',
            'site_name' => 'Site de Teste',
            'type' => 'article',
        ]);

        $html = implode("\n", $tags);

        self::assertStringContainsString('<meta property="og:title" content="Notícia &quot;importante&quot; &amp; atual">', $html);
        self::assertStringContainsString('<meta property="og:description" content="Resumo da notícia">', $html);
        self::assertStringContainsString('<meta property="og:image" content="https://site.test/contents/banner.jpg">', $html);
        self::assertStringContainsString('<meta property="og:url" content="https://site.test/noticias/importante/">', $html);
        self::assertStringContainsString('<meta property="og:site_name" content="Site de Teste">', $html);
        self::assertStringContainsString('<meta property="og:type" content="article">', $html);
        self::assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
    }

    public function testNaoEmiteTagComValorVazio(): void
    {
        $html = implode("\n", gestor_open_graph_tags([
            'title' => 'Só o título',
            'description' => '',
            'image' => '   ',
            'url' => '',
            'site_name' => '',
        ]));

        self::assertStringContainsString('og:title', $html);
        self::assertStringNotContainsString('og:description', $html);
        self::assertStringNotContainsString('og:image', $html);
        self::assertStringNotContainsString('og:url', $html);
        self::assertStringContainsString('<meta property="og:type" content="website">', $html);
        // Sem imagem, o card do Twitter/X é o resumo pequeno.
        self::assertStringContainsString('<meta name="twitter:card" content="summary">', $html);
    }

    public function testEntradaVaziaNaoGeraNenhumaTag(): void
    {
        self::assertSame([], gestor_open_graph_tags(['title' => '', 'type' => '']));
        self::assertNotEmpty(gestor_open_graph_tags(false)); // só o og:type padrão + twitter:card
    }

    public function testQuebrasDeLinhaEEspacosSaoNormalizados(): void
    {
        $html = implode("\n", gestor_open_graph_tags([
            'title' => "  Título   com \n quebras  ",
        ]));

        self::assertStringContainsString('content="Título com quebras"', $html);
    }

    public function testDetectaOpenGraphJaPresenteNoExtraHead(): void
    {
        self::assertTrue(gestor_open_graph_existe('<meta property="og:title" content="X">'));
        self::assertTrue(gestor_open_graph_existe("<meta property='og:image' content='x.jpg'>"));
        self::assertTrue(gestor_open_graph_existe(['<link rel="canonical" href="/">', '<meta property="og:description" content="y">']));
        self::assertFalse(gestor_open_graph_existe('<meta name="description" content="y">'));
        self::assertFalse(gestor_open_graph_existe([]));
        self::assertFalse(gestor_open_graph_existe(''));
        // `og:type` sozinho não conta: o conjunto relevante para o scraper é title/image/description.
        self::assertFalse(gestor_open_graph_existe('<meta property="og:type" content="website">'));
    }

    // ===== req-110: metadados gravados na página

    public function testMetadadosDaPaginaViramChavesDeOpenGraph(): void
    {
        $og = gestor_pagina_og_do_registro([
            'nome' => 'Sobre nós',
            'og_titulo' => 'Conheça a nossa história',
            'og_descricao' => 'Quem somos e o que fazemos.',
            'imagem_destaque' => 'imagens/banner-sobre.jpg',
        ]);

        self::assertSame('Conheça a nossa história', $og['title']);
        self::assertSame('Quem somos e o que fazemos.', $og['description']);
        self::assertSame('imagens/banner-sobre.jpg', $og['image']);
    }

    public function testColunaVaziaNaoVIRAChaveEDeixaOFallbackAgir(): void
    {
        // Chave presente e vazia venceria o nome da página em gestor_open_graph_dados();
        // por isso o registro vazio precisa sair do array, e não virar string vazia.
        $og = gestor_pagina_og_do_registro([
            'og_titulo' => '',
            'og_descricao' => '   ',
            'imagem_destaque' => null,
        ]);

        self::assertSame([], $og);
    }

    public function testRegistroSemAsColunasNovasNaoQuebra(): void
    {
        self::assertSame([], gestor_pagina_og_do_registro(['nome' => 'Página antiga']));
        self::assertSame([], gestor_pagina_og_do_registro([]));
        self::assertSame([], gestor_pagina_og_do_registro('não é array'));
    }

    // ===== req-112: meta tags clássicas de SEO

    public function testMetaTagsDeBuscaSaoEmitidas(): void
    {
        $html = implode("\n", gestor_meta_seo_tags([
            'description' => 'Resumo para o resultado de busca',
            'keywords' => 'fotografia, ensaio, estúdio',
        ]));

        self::assertStringContainsString('<meta name="description" content="Resumo para o resultado de busca">', $html);
        self::assertStringContainsString('<meta name="keywords" content="fotografia, ensaio, estúdio">', $html);
    }

    public function testKeywordsVaziaNaoEmiteTag(): void
    {
        // Nenhum buscador relevante usa `keywords` para ranquear — emitir vazia é ruído puro.
        $html = implode("\n", gestor_meta_seo_tags(['description' => 'Só a descrição', 'keywords' => '']));

        self::assertStringContainsString('name="description"', $html);
        self::assertStringNotContainsString('name="keywords"', $html);
    }

    public function testSemNadaPreenchidoNaoEmiteNenhumaTag(): void
    {
        self::assertSame([], gestor_meta_seo_tags(['description' => '', 'keywords' => '   ']));
        self::assertSame([], gestor_meta_seo_tags(false));
    }

    public function testDescricaoTemEscapeENormalizacaoDeEspacos(): void
    {
        $html = implode("\n", gestor_meta_seo_tags([
            'description' => "  Aspas \"duplas\" & e   quebras\n de linha  ",
        ]));

        self::assertStringContainsString('content="Aspas &quot;duplas&quot; &amp; e quebras de linha"', $html);
    }

    public function testNormalizacaoDasPalavrasChave(): void
    {
        // Aceita vírgula, ponto e vírgula e quebra de linha; sem duplicatas nem vazios.
        self::assertSame(
            'foto, ensaio, estúdio',
            gestor_meta_keywords_normalizar("foto, ensaio;; \n estúdio ,")
        );

        // Duplicata é comparada sem caixa, mas a grafia original do primeiro é preservada.
        self::assertSame('Conn2Flow, site', gestor_meta_keywords_normalizar('Conn2Flow, site, conn2flow'));

        self::assertSame('', gestor_meta_keywords_normalizar(''));
        self::assertSame('', gestor_meta_keywords_normalizar('  ,  ;  '));
        self::assertSame('', gestor_meta_keywords_normalizar(null));
    }

    public function testPaginaComDescriptionPropriaNaoRecebeADoCore(): void
    {
        self::assertTrue(gestor_meta_seo_existe('<meta name="description" content="minha">'));
        self::assertTrue(gestor_meta_seo_existe(["<meta name='description' content='x'>"]));
        self::assertFalse(gestor_meta_seo_existe('<meta property="og:description" content="x">'));
        self::assertFalse(gestor_meta_seo_existe(''));
        self::assertFalse(gestor_meta_seo_existe([]));
    }

    public function testMetaTagsEntramNoPacoteDeMetadadosDaPagina(): void
    {
        $og = gestor_pagina_og_do_registro([
            'og_descricao' => 'Texto social',
            'meta_descricao' => 'Texto para o Google',
            'meta_keywords' => 'a, b',
        ]);

        self::assertSame('Texto social', $og['description']);
        self::assertSame('Texto para o Google', $og['meta_description']);
        self::assertSame('a, b', $og['meta_keywords']);
    }

    public function testMetadadosDaPaginaAlimentamAsTagsFinais(): void
    {
        $og = gestor_pagina_og_do_registro([
            'og_titulo' => 'Título da página',
            'imagem_destaque' => 'https://site.test/banner.jpg',
        ]);

        $html = implode("\n", gestor_open_graph_tags(array_merge(
            ['site_name' => 'Site de Teste', 'url' => 'https://site.test/sobre/'],
            $og
        )));

        self::assertStringContainsString('<meta property="og:title" content="Título da página">', $html);
        self::assertStringContainsString('<meta property="og:image" content="https://site.test/banner.jpg">', $html);
        self::assertStringContainsString('summary_large_image', $html);
    }
}
