<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'manutencao.php';

/**
 * req-210 / BATCH-218 — modo de manutenção durante deploy.
 *
 * Cobre a biblioteca pura: ligar, desligar, validade, isenções, idioma, formato da resposta e a
 * tela. A resposta HTTP em si (503, cabeçalhos, `exit`) é conferida no navegador.
 */
final class ManutencaoReq210Test extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        $this->base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-manutencao-' . bin2hex(random_bytes(6));
        mkdir($this->base, 0777, true);
    }

    protected function tearDown(): void
    {
        @unlink(manutencao_arquivo($this->base));
        @rmdir($this->base . DIRECTORY_SEPARATOR . 'temp');
        @rmdir($this->base);
    }

    public function testLigarCriaOArquivoComValidadeEDesligarRemove(): void
    {
        self::assertNull(manutencao_estado($this->base));
        self::assertTrue(manutencao_ligar($this->base, ['owner' => 'pipeline', 'detail' => 'teste'], 600));

        $estado = manutencao_estado($this->base);
        self::assertSame('pipeline', $estado['owner']);
        self::assertSame('teste', $estado['detail']);
        self::assertSame(600, $estado['expires_at'] - $estado['started_at']);

        self::assertTrue(manutencao_desligar($this->base));
        self::assertNull(manutencao_estado($this->base));
        // Desligar o que já está desligado não é erro.
        self::assertTrue(manutencao_desligar($this->base));
    }

    public function testManutencaoVencidaOuIlegivelNaoBloqueia(): void
    {
        manutencao_ligar($this->base, [], 600);
        $estado = manutencao_estado($this->base);
        // Deploy que morreu sem desligar: passado o prazo, o site volta sozinho.
        self::assertNull(manutencao_estado($this->base, $estado['expires_at']));
        self::assertNotNull(manutencao_estado($this->base, $estado['expires_at'] - 1));

        file_put_contents(manutencao_arquivo($this->base), '{quebrado');
        self::assertNull(manutencao_estado($this->base));
        file_put_contents(manutencao_arquivo($this->base), json_encode(['owner' => 'x']));
        self::assertNull(manutencao_estado($this->base));
    }

    public function testLinhaDeComandoEApiFicamForaDoBloqueio(): void
    {
        self::assertTrue(manutencao_requisicao_isenta('cli', '/qualquer/'));
        self::assertTrue(manutencao_requisicao_isenta('fpm-fcgi', '/_api/project/update'));
        self::assertTrue(manutencao_requisicao_isenta('fpm-fcgi', '/instalacao/_api/system/run-status?x=1'));

        self::assertFalse(manutencao_requisicao_isenta('fpm-fcgi', '/'));
        self::assertFalse(manutencao_requisicao_isenta('fpm-fcgi', '/store/?q=_api/'));
        self::assertFalse(manutencao_requisicao_isenta('fpm-fcgi', '/minha_api/'));
        self::assertFalse(manutencao_requisicao_isenta('apache2handler', '/dashboard/'));
    }

    public function testIdiomaVemDoEnderecoEDepoisDoNavegador(): void
    {
        self::assertSame('en', manutencao_idioma('/en/docs/', 'pt-BR,pt;q=0.9'));
        self::assertSame('pt-br', manutencao_idioma('/docs/', 'pt-BR,pt;q=0.9,en;q=0.8'));
        self::assertSame('en', manutencao_idioma('/docs/', 'en-US,en;q=0.9'));
        self::assertSame('pt-br', manutencao_idioma('/docs/', ''));
        self::assertSame('pt-br', manutencao_idioma('/entrada/', 'fr-FR'));
    }

    public function testRequisicaoAjaxRecebeJsonComOsTextos(): void
    {
        self::assertTrue(manutencao_espera_json([], ['ajax' => 'sim']));
        self::assertTrue(manutencao_espera_json(['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], []));
        self::assertTrue(manutencao_espera_json(['HTTP_ACCEPT' => 'application/json, text/plain'], []));
        self::assertFalse(manutencao_espera_json(['HTTP_ACCEPT' => 'text/html'], []));

        $json = json_decode(manutencao_json('pt-br'), true);
        self::assertSame('maintenance', $json['status']);
        self::assertTrue($json['maintenance']);
        self::assertSame(MANUTENCAO_RETRY, $json['retry_after']);
        foreach (['title', 'text', 'retry', 'message'] as $chave) {
            self::assertNotSame('', trim((string)$json[$chave]), $chave);
        }
        self::assertNotSame($json['title'], json_decode(manutencao_json('en'), true)['title']);
    }

    public function testTelaVoltaParaOMesmoEnderecoESoDentroDoSite(): void
    {
        self::assertSame('/store/?plan=x', manutencao_destino('/store/?plan=x'));
        self::assertSame('/', manutencao_destino('//evil.example/x'));
        self::assertSame('/', manutencao_destino('https://evil.example/'));
        self::assertSame('/', manutencao_destino(''));

        $html = manutencao_html('pt-br', '/cart/?a=1&b="><script>alert(1)</script>');
        self::assertStringContainsString('data-c2f-maintenance', $html);
        self::assertStringContainsString('href="/cart/?a=1&amp;b=&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        // A tela não depende de nenhum arquivo do sistema, que pode estar sendo trocado.
        self::assertDoesNotMatchRegularExpression('/<link\b|<script[^>]+src=|<img\b/i', $html);
        self::assertStringContainsString('X-C2F-Maintenance', $html);

        // Com logo, ela vai embutida: um endereço de arquivo também receberia a tela de manutenção.
        $comLogo = manutencao_html('pt-br', '/', null, 'data:image/png;base64,AAAA');
        self::assertStringContainsString('<img class="c2f-logo" src="data:image/png;base64,AAAA" alt="">', $comLogo);
        self::assertDoesNotMatchRegularExpression('/<img[^>]+src="(?!data:)/i', $comLogo);
    }

    public function testLogoDoProjetoTemPrecedenciaSobreADoCore(): void
    {
        self::assertSame('', manutencao_logo($this->base));

        mkdir($this->base . '/assets/images', 0777, true);
        file_put_contents($this->base . '/assets/images/Logomarca200.png', 'core');
        self::assertSame('data:image/png;base64,' . base64_encode('core'), manutencao_logo($this->base));

        mkdir($this->base . '/assets/manutencao', 0777, true);
        file_put_contents($this->base . '/assets/manutencao/logo.svg', '<svg/>');
        self::assertSame('data:image/svg+xml;base64,' . base64_encode('<svg/>'), manutencao_logo($this->base));

        // Arquivo grande demais não entra numa resposta que precisa ser leve.
        file_put_contents($this->base . '/assets/manutencao/logo.svg', str_repeat('x', 153601));
        self::assertSame('data:image/png;base64,' . base64_encode('core'), manutencao_logo($this->base));

        self::assertSame('data:image/png;base64,AAAA', json_decode(manutencao_json('pt-br', null, 'data:image/png;base64,AAAA'), true)['logo']);

        foreach (['assets/manutencao/logo.svg', 'assets/images/Logomarca200.png'] as $arquivo) {
            unlink($this->base . '/' . $arquivo);
        }
        rmdir($this->base . '/assets/manutencao');
        rmdir($this->base . '/assets/images');
        rmdir($this->base . '/assets');
    }

    public function testMensagemPersonalizadaSubstituiOTexto(): void
    {
        $estado = ['message' => ['pt-br' => 'Voltamos às 10h.', 'en' => '']];
        self::assertSame('Voltamos às 10h.', manutencao_textos('pt-br', $estado)['text']);
        self::assertSame(manutencao_textos('en')['text'], manutencao_textos('en', $estado)['text']);
        self::assertStringContainsString('Voltamos às 10h.', manutencao_html('pt-br', '/', $estado));
    }

    public function testGestorApiEPipelineUsamAManutencao(): void
    {
        $gestor = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/gestor.php');
        // Antes da configuração: durante o deploy ela pode estar pela metade.
        self::assertLessThan(strpos($gestor, "require_once(__DIR__ . '/config.php');"), strpos($gestor, 'manutencao_verificar(__DIR__);'));

        $api = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/api/api.php');
        self::assertStringContainsString('manutencao_ligar($manutencao_base', $api);
        self::assertStringContainsString('manutencao_desligar($manutencao_base)', $api);

        $pipeline = (string)file_get_contents(dirname(CONN2FLOW_GESTOR_ROOT) . '/cli/src/Commands/ProjectUpdateAllCommand.php');
        self::assertStringContainsString("\$this->maintenance((string)\$project, true, \$output)", $pipeline);
        self::assertStringContainsString("\$this->maintenance((string)\$project, false, \$output)", $pipeline);
        self::assertStringContainsString('no-maintenance', $pipeline);

        $front = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/assets/global/global.js');
        self::assertStringContainsString('X-C2F-Maintenance', $front);
        self::assertStringContainsString('tratarManutencaoXhr(xhr);', $front);
        self::assertStringContainsString('tratarManutencaoFetch(response);', $front);
    }
}
