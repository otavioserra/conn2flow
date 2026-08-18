<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'usuario.php';

/**
 * req-118 (BATCH-119) — auditoria de sessões ativas.
 *
 * As três funções de contrato (`usuario_sessoes_listar`, `usuario_sessao_revogar`,
 * `usuario_sessoes_revogar_outras`) tocam o banco e ficam para a validação em runtime. O que está
 * coberto aqui é o que DECIDE o que o usuário lê e no que ele clica: o reconhecimento do User-Agent
 * e a marcação da sessão atual. Marcar o cartão errado como "este dispositivo" faz o usuário revogar
 * o próprio acesso achando que derrubava outro — é um defeito silencioso e destrutivo.
 */
final class PerfilUsuarioSessoesTest extends TestCase
{
    // ===== Navegador: derivados do Chromium têm de vencer o Chrome

    public function testChromeNoWindows(): void
    {
        $agente = usuario_user_agent_analisar(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );

        self::assertSame('Chrome', $agente['navegador']);
        self::assertSame('Windows', $agente['sistema']);
        self::assertSame('desktop', $agente['dispositivo']);
    }

    public function testEdgeNaoEhConfundidoComChrome(): void
    {
        // O UA do Edge contém "Chrome/120.0.0.0" — a ordem das comparações é o que separa os dois.
        $agente = usuario_user_agent_analisar(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0'
        );

        self::assertSame('Edge', $agente['navegador']);
    }

    public function testOperaNaoEhConfundidoComChrome(): void
    {
        $agente = usuario_user_agent_analisar(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0'
        );

        self::assertSame('Opera', $agente['navegador']);
    }

    public function testSafariNoIphoneNaoEhConfundidoComChrome(): void
    {
        $agente = usuario_user_agent_analisar(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1'
        );

        self::assertSame('Safari', $agente['navegador']);
        self::assertSame('iOS', $agente['sistema']);
        self::assertSame('mobile', $agente['dispositivo']);
    }

    public function testChromeNoIosEhReconhecidoPeloCriOS(): void
    {
        // No iOS todo navegador usa WebKit e o UA continua dizendo "Safari"; só o CriOS distingue.
        $agente = usuario_user_agent_analisar(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0.0.0 Mobile/15E148 Safari/604.1'
        );

        self::assertSame('Chrome', $agente['navegador']);
        self::assertSame('iOS', $agente['sistema']);
    }

    public function testFirefoxNoLinux(): void
    {
        $agente = usuario_user_agent_analisar('Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0');

        self::assertSame('Firefox', $agente['navegador']);
        self::assertSame('Linux', $agente['sistema']);
    }

    // ===== Sistema: Android antes de Linux

    public function testAndroidNaoEhClassificadoComoLinux(): void
    {
        $agente = usuario_user_agent_analisar(
            'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36'
        );

        self::assertSame('Android', $agente['sistema']);
        self::assertSame('mobile', $agente['dispositivo']);
    }

    public function testAndroidSemMobileEhTablet(): void
    {
        $agente = usuario_user_agent_analisar(
            'Mozilla/5.0 (Linux; Android 13; SM-X710) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );

        self::assertSame('tablet', $agente['dispositivo']);
    }

    public function testIpadEhTablet(): void
    {
        $agente = usuario_user_agent_analisar(
            'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/604.1'
        );

        self::assertSame('tablet', $agente['dispositivo']);
        self::assertSame('iOS', $agente['sistema']);
    }

    public function testMacOsDesktop(): void
    {
        $agente = usuario_user_agent_analisar(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );

        self::assertSame('macOS', $agente['sistema']);
        self::assertSame('desktop', $agente['dispositivo']);
    }

    // ===== Não reconhecido sai VAZIO, nunca com rótulo traduzido

    public function testUserAgentVazioNaoInventaRotulo(): void
    {
        // A biblioteca é core e não tem idioma: quem exibe resolve a variável traduzida.
        $agente = usuario_user_agent_analisar('');

        self::assertSame('', $agente['navegador']);
        self::assertSame('', $agente['sistema']);
        self::assertSame('desktop', $agente['dispositivo']);
    }

    public function testUserAgentDesconhecidoNaoQuebra(): void
    {
        $agente = usuario_user_agent_analisar('curl/8.4.0');

        self::assertSame('', $agente['navegador']);
        self::assertSame('', $agente['sistema']);
    }

    // ===== Marcação da sessão atual

    public function testSessaoDoTokenCorrenteEhMarcadaComoAtual(): void
    {
        $sessao = usuario_sessao_formatar([
            'pubID' => 'abc123',
            'ip' => '10.0.0.8',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0.0.0 Safari/537.36',
            'expiration' => 1770000000,
            'data_criacao' => '2026-08-18 09:00:00',
        ], 'abc123');

        self::assertTrue($sessao['atual']);
        self::assertFalse($sessao['sessao']);
    }

    public function testOutraSessaoNaoEhMarcadaComoAtual(): void
    {
        $sessao = usuario_sessao_formatar(['pubID' => 'outro'], 'abc123');

        self::assertFalse($sessao['atual']);
    }

    public function testSemTokenCorrenteNenhumaSessaoEhMarcada(): void
    {
        // Sem o token da requisição, marcar qualquer linha seria um chute — e o chute errado leva o
        // usuário a revogar a própria sessão.
        self::assertFalse(usuario_sessao_formatar(['pubID' => 'abc123'], null)['atual']);
        self::assertFalse(usuario_sessao_formatar(['pubID' => 'abc123'], '')['atual']);
    }

    public function testRegistroSemPubIdNuncaEhAtual(): void
    {
        self::assertFalse(usuario_sessao_formatar(['pubID' => ''], '')['atual']);
    }

    public function testExpirationZeroEhCookieDeSessaoENaoTokenExpirado(): void
    {
        self::assertTrue(usuario_sessao_formatar(['pubID' => 'x', 'expiration' => 0], null)['sessao']);
    }

    public function testCamposAusentesViramStringVazia(): void
    {
        $sessao = usuario_sessao_formatar(['pubID' => 'x'], null);

        self::assertSame('', $sessao['ip']);
        self::assertSame('', $sessao['origem']);
        self::assertSame('', $sessao['data_criacao']);
    }

    public function testColunasNulasDoBancoNaoViramTextoNull(): void
    {
        // `usuarios_tokens` permite NULL em ip, origem e user_agent; sem a normalização o painel
        // imprimiria a string "null" no lugar do valor.
        $sessao = usuario_sessao_formatar([
            'pubID' => 'x',
            'ip' => null,
            'origem' => null,
            'user_agent' => null,
            'data_criacao' => null,
        ], null);

        self::assertSame('', $sessao['ip']);
        self::assertSame('', $sessao['origem']);
        self::assertSame('', $sessao['user_agent']);
        self::assertSame('', $sessao['data_criacao']);
    }

    // ===== Guardas dos parâmetros de revogação (não chegam ao banco)

    public function testRevogarSemPubIdNaoExecuta(): void
    {
        self::assertFalse(usuario_sessao_revogar('', 10));
        self::assertFalse(usuario_sessao_revogar('   ', 10));
    }

    public function testRevogarSemUsuarioNaoExecuta(): void
    {
        // O `pubID` chega do cliente; sem o id do usuário no WHERE, revogar sessão alheia seria
        // possível com um identificador adivinhado.
        self::assertFalse(usuario_sessao_revogar('abc123', 0));
    }

    public function testRevogarOutrasSemTokenAtualNaoExecuta(): void
    {
        // Sem o token a preservar, a operação viraria um logout global disfarçado.
        self::assertFalse(usuario_sessoes_revogar_outras('', 10));
    }

    public function testRevogarOutrasSemUsuarioNaoExecuta(): void
    {
        self::assertFalse(usuario_sessoes_revogar_outras('abc123', 0));
    }

    public function testListarSemUsuarioDevolveVazioSemConsultarBanco(): void
    {
        self::assertSame([], usuario_sessoes_listar(0));
        self::assertSame([], usuario_sessoes_listar(-1));
    }
}
