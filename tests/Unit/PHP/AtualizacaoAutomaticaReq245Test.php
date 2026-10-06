<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-245 (BATCH-254) — atualização automática do sistema: decisão do ciclo, versões, vencimento por período,
 * configuração por instalação e as salvaguardas que não dependem de tela.
 */
final class AtualizacaoAutomaticaReq245Test extends TestCase
{
    private string $pasta;

    protected function setUp(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/atualizacoes-automatica.php';
        require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/atualizacoes-execucao.php';
        $this->pasta = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-req245-' . bin2hex(random_bytes(4));
        mkdir($this->pasta);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->pasta . DIRECTORY_SEPARATOR . '*') ?: [] as $arquivo) unlink($arquivo);
        rmdir($this->pasta);
    }

    /** Configuração ligada, na hora 3, semanal, sem histórico. */
    private function ligada(array $estado = [], array $extra = []): array
    {
        return atualizacao_automatica_normalizar(['ativo' => true, 'periodo' => 'semanal', 'hora' => 3, 'estado' => $estado] + $extra);
    }

    public function testNasceDesligadaSemanalComBackup(): void
    {
        $c = atualizacao_automatica_ler($this->pasta);
        $this->assertFalse($c['ativo']);
        $this->assertSame('semanal', $c['periodo']);
        $this->assertSame(3, $c['hora']);
        $this->assertTrue($c['backup']);
        $this->assertSame(['diario' => 1, 'semanal' => 7, 'mensal' => 30], ATUALIZACAO_AUTOMATICA_PERIODOS);
    }

    public function testGravaELeDeVoltaDescartandoOQueNaoConhece(): void
    {
        $this->assertTrue(atualizacao_automatica_gravar($this->pasta, [
            'ativo' => 'true', 'periodo' => 'mensal', 'hora' => '22', 'backup' => '0', 'wipe' => true, 'no_rollback' => true,
            'estado' => ['recusadas' => ['gestor-v9.9.9' => ['quando' => 10, 'motivo' => 'rolled_back'], '--wipe' => []], 'pendente' => ['run' => 'x', 'tag' => '; rm -rf /']],
        ]));
        $c = atualizacao_automatica_ler($this->pasta);
        $this->assertTrue($c['ativo']);
        $this->assertSame('mensal', $c['periodo']);
        $this->assertSame(22, $c['hora']);
        $this->assertFalse($c['backup']);
        $this->assertArrayNotHasKey('wipe', $c);
        $this->assertArrayNotHasKey('no_rollback', $c);
        $this->assertSame(['gestor-v9.9.9'], array_keys($c['estado']['recusadas']));
        $this->assertNull($c['estado']['pendente']);
        $this->assertSame([], glob($this->pasta . DIRECTORY_SEPARATOR . '*.tmp'));
    }

    public function testValoresForaDaFaixaVoltamAoPadrao(): void
    {
        $c = atualizacao_automatica_normalizar(['periodo' => 'a-cada-minuto', 'hora' => 99]);
        $this->assertSame('semanal', $c['periodo']);
        $this->assertSame(3, $c['hora']);
    }

    public function testComparacaoDeVersoes(): void
    {
        $this->assertTrue(atualizacao_automatica_mais_nova('gestor-v2.10.14', '2.10.13'));
        $this->assertTrue(atualizacao_automatica_mais_nova('gestor-v2.10.0', 'v2.9.39'));
        $this->assertFalse(atualizacao_automatica_mais_nova('gestor-v2.10.13', '2.10.13'));
        $this->assertFalse(atualizacao_automatica_mais_nova('gestor-v2.9.39', '2.10.13'));
        $this->assertFalse(atualizacao_automatica_mais_nova('gestor-v2.10.14', ''));
        $this->assertFalse(atualizacao_automatica_tag_valida('gestor-v2.10.14; rm -rf /'));
        $this->assertFalse(atualizacao_automatica_tag_valida('instalador-v1.5.6'));
    }

    public function testUltimaEstavelIgnoraRascunhoPreLancamentoEOutrosPacotes(): void
    {
        $releases = [
            ['tag_name' => 'gestor-v3.0.0', 'prerelease' => true],
            ['tag_name' => 'gestor-v2.99.0', 'draft' => true],
            ['tag_name' => 'instalador-v9.0.0'],
            ['tag_name' => 'gestor-v2.9.39'],
            ['tag_name' => 'gestor-v2.10.13'],
            'lixo',
        ];
        $this->assertSame('gestor-v2.10.13', atualizacao_automatica_ultima_estavel($releases));
        $this->assertNull(atualizacao_automatica_ultima_estavel([['tag_name' => 'gestor-v3.0.0', 'prerelease' => true]]));
    }

    public function testVencimentoPorPeriodo(): void
    {
        $agora = mktime(3, 0, 0, 10, 20, 2026);
        foreach (['diario' => 1, 'semanal' => 7, 'mensal' => 30] as $periodo => $dias) {
            $base = ['ativo' => true, 'periodo' => $periodo, 'hora' => 3];
            // Checagem feita cinco minutos depois da hora, no ciclo anterior: já está vencida na hora certa.
            $vencida = atualizacao_automatica_normalizar($base + ['estado' => ['ultima_automatica' => $agora - $dias * 86400 + 300]]);
            $this->assertTrue(atualizacao_automatica_vencida($vencida, $agora), $periodo);
            $emDia = atualizacao_automatica_normalizar($base + ['estado' => ['ultima_automatica' => $agora - $dias * 86400 + 3 * 3600]]);
            $this->assertFalse(atualizacao_automatica_vencida($emDia, $agora), $periodo);
        }
        $this->assertTrue(atualizacao_automatica_vencida($this->ligada(), $agora));
    }

    public function testQuandoConferir(): void
    {
        $tres = mktime(3, 20, 0, 10, 20, 2026);
        $dez = mktime(10, 0, 0, 10, 20, 2026);
        $this->assertSame('desligada', atualizacao_automatica_deve_conferir(atualizacao_automatica_padrao(), $tres)['motivo']);
        $this->assertSame('fora-da-hora', atualizacao_automatica_deve_conferir($this->ligada(), $dez)['motivo']);
        $this->assertSame('em-dia', atualizacao_automatica_deve_conferir($this->ligada(['ultima_automatica' => $tres - 86400]), $tres)['motivo']);
        $pendente = $this->ligada(['pendente' => ['run' => 'run-1', 'tag' => 'gestor-v2.10.14', 'quando' => 1]]);
        $this->assertSame('execucao-pendente', atualizacao_automatica_deve_conferir($pendente, $tres)['motivo']);
        $this->assertTrue(atualizacao_automatica_deve_conferir($this->ligada(), $tres)['conferir']);
    }

    public function testChecagemManualNaoAdiaADaRotina(): void
    {
        $tres = mktime(3, 20, 0, 10, 20, 2026);
        // "Verificar agora" feito há uma hora: a rotina continua vencida e confere na hora preferida.
        $manual = $this->ligada(['ultima_checagem' => $tres - 3600]);
        $this->assertTrue(atualizacao_automatica_deve_conferir($manual, $tres)['conferir']);
        $this->assertSame($tres - 3600, $manual['estado']['ultima_checagem']);
        $this->assertNull($manual['estado']['ultima_automatica']);
    }

    public function testProximaChecagemCaiNaHoraPreferida(): void
    {
        $agora = mktime(10, 0, 0, 10, 20, 2026);
        $this->assertNull(atualizacao_automatica_proxima(atualizacao_automatica_padrao(), $agora));
        $nunca = atualizacao_automatica_proxima($this->ligada(), $agora);
        $this->assertSame('2026-10-21 03:00', date('Y-m-d H:i', $nunca));
        $semana = atualizacao_automatica_proxima($this->ligada(['ultima_automatica' => mktime(3, 5, 0, 10, 20, 2026)]), $agora);
        $this->assertSame('2026-10-27 03:00', date('Y-m-d H:i', $semana));
    }

    public function testDecisaoDoCiclo(): void
    {
        $contexto = ['instalada' => '2.10.13', 'publicada' => 'gestor-v2.10.14', 'choques_pendentes' => 0, 'trava_ocupada' => false];
        $this->assertSame(['atualizar' => true, 'motivo' => 'versao-nova', 'tag' => 'gestor-v2.10.14'], atualizacao_automatica_decidir($this->ligada(), $contexto));
        $motivo = fn (array $config, array $troca = []) => atualizacao_automatica_decidir($config, $troca + $contexto);
        $this->assertSame('desligada', $motivo(atualizacao_automatica_padrao())['motivo']);
        $this->assertSame('sem-versao-publicada', $motivo($this->ligada(), ['publicada' => null])['motivo']);
        $this->assertSame('sem-versao-publicada', $motivo($this->ligada(), ['publicada' => 'gestor-v2.10.14 --wipe'])['motivo']);
        $this->assertSame('ja-atualizado', $motivo($this->ligada(), ['publicada' => 'gestor-v2.10.13'])['motivo']);
        $this->assertSame('choque-pendente', $motivo($this->ligada(), ['choques_pendentes' => 2])['motivo']);
        $this->assertSame('trava-ocupada', $motivo($this->ligada(), ['trava_ocupada' => true])['motivo']);
        $recusada = $this->ligada(['recusadas' => ['gestor-v2.10.14' => ['quando' => 1, 'motivo' => 'rolled_back']]]);
        $this->assertSame('versao-recusada', $motivo($recusada)['motivo']);
        foreach (['desligada', 'sem-versao-publicada', 'ja-atualizado', 'choque-pendente', 'trava-ocupada', 'versao-recusada'] as $m) {
            $this->assertNotSame('versao-nova', $m);
        }
        // Uma versão mais nova que a recusada é tentada.
        $this->assertTrue($motivo($recusada, ['publicada' => 'gestor-v2.10.15'])['atualizar']);
        // Liberada à mão, a recusada volta a ser tentada.
        $this->assertTrue($motivo(atualizacao_automatica_liberar($recusada, 'gestor-v2.10.14'))['atualizar']);
    }

    public function testResultadoDaExecucaoPendente(): void
    {
        $pendente = fn () => $this->ligada(['pendente' => ['run' => 'run-1', 'tag' => 'gestor-v2.10.14', 'quando' => 100]]);
        $rodando = atualizacao_automatica_aplicar_resultado($pendente(), 'running', 200);
        $this->assertNotNull($rodando['estado']['pendente']);

        $sucesso = atualizacao_automatica_aplicar_resultado($pendente(), 'success', 200);
        $this->assertNull($sucesso['estado']['pendente']);
        $this->assertSame([], $sucesso['estado']['recusadas']);
        $this->assertSame(['tag' => 'gestor-v2.10.14', 'quando' => 100, 'resultado' => 'success'], $sucesso['estado']['ultima_tentativa']);

        foreach (['rolled_back', 'error', 'error-download', 'error-integrity', 'error-database'] as $falha) {
            $c = atualizacao_automatica_aplicar_resultado($pendente(), $falha, 200);
            $this->assertNull($c['estado']['pendente'], $falha);
            $this->assertSame(['quando' => 200, 'motivo' => $falha], $c['estado']['recusadas']['gestor-v2.10.14'], $falha);
        }
        // Trava ocupada no momento do disparo: nada foi tentado, a versão não é recusada.
        $travada = atualizacao_automatica_aplicar_resultado($pendente(), 'locked', 200);
        $this->assertSame([], $travada['estado']['recusadas']);
        $this->assertNull($travada['estado']['pendente']);
    }

    public function testOpcoesEntreguesAoAtualizadorSaoSoTagEBackup(): void
    {
        $this->assertSame(['tag' => 'gestor-v2.10.14', 'backup' => true], atualizacao_automatica_opcoes($this->ligada(), 'gestor-v2.10.14'));
        $semBackup = $this->ligada([], ['backup' => false]);
        $this->assertSame(['tag' => 'gestor-v2.10.14'], atualizacao_automatica_opcoes($semBackup, 'gestor-v2.10.14'));
        // Configuração adulterada à mão: as opções proibidas não chegam à linha de comando.
        $adulterada = ['ativo' => true, 'backup' => true, 'wipe' => true, 'no_health' => true, 'no_rollback' => true, 'no_verify' => true, 'dry_run' => true, 'only_files' => true];
        $argv = atualizacoes_execucao_argv(atualizacao_automatica_opcoes($adulterada, 'gestor-v2.10.14'), 'exemplo.local');
        $this->assertSame([], $argv['recusadas']);
        $this->assertSame(['--domain=exemplo.local', '--log-stdout', '--tag=gestor-v2.10.14', '--backup'], $argv['argv']);
        foreach (['--wipe', '--no-health', '--no-rollback', '--no-verify', '--dry-run', '--only-files', '--only-db'] as $proibida) {
            $this->assertNotContains($proibida, $argv['argv']);
        }
    }

    public function testTarefaDeclaradaNoManifestoDoModuloComCallbackNoArquivoDeCron(): void
    {
        $manifesto = json_decode((string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/admin-atualizacoes/admin-atualizacoes.json'), true);
        $this->assertIsArray($manifesto['cron'] ?? null);
        $tarefa = $manifesto['cron'][0];
        $this->assertSame('admin-atualizacoes-automatica', $tarefa['id']);
        $this->assertSame('horario', $tarefa['frequencia']);
        $this->assertFalse($tarefa['ativo']);
        $this->assertSame('admin_atualizacoes_cron_automatica', $tarefa['funcao']);
        $fonte = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/admin-atualizacoes/admin-atualizacoes.cron.php');
        $this->assertStringContainsString('function admin_atualizacoes_cron_automatica(', $fonte);
        // O arquivo de cron não pode carregar o controlador do módulo, que renderiza a interface.
        $this->assertStringNotContainsString('admin-atualizacoes.php', $fonte);
        // A consulta de versões verifica o certificado.
        $biblioteca = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/atualizacoes-automatica.php');
        $this->assertStringContainsString('CURLOPT_SSL_VERIFYPEER => true', $biblioteca);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idiomas')]
    public function testTelaComAbasManualEAutomatico(string $idioma): void
    {
        $html = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . "/modulos/admin-atualizacoes/resources/$idioma/components/atualizacoes-lista-tailwind/atualizacoes-lista-tailwind.html");
        $this->assertSame(1, substr_count($html, 'data-c2f-aba="manual"'));
        $this->assertSame(1, substr_count($html, 'data-c2f-aba="automatico"'));
        $manual = substr($html, (int)strpos($html, 'data-c2f-painel="manual"'), (int)strpos($html, 'data-c2f-painel="automatico"') - (int)strpos($html, 'data-c2f-painel="manual"'));
        // O modo manual guarda os três modos, as opções e o botão de execução, com os ganchos do JS.
        $this->assertSame(3, substr_count($manual, 'upd-mode-btn'));
        foreach (['id="atualizacoes-buttons"', 'id="atualizacoes-start-btn"', 'id="flag-backup"', 'id="flag-wipe"', 'id="atualizacoes-progress-bar"'] as $gancho) {
            $this->assertStringContainsString($gancho, $manual, $gancho);
        }
        $auto = substr($html, (int)strpos($html, 'data-c2f-painel="automatico"'));
        $this->assertSame(3, substr_count($auto, 'data-auto-periodo='));
        foreach (['diario', 'semanal', 'mensal'] as $periodo) $this->assertStringContainsString('data-auto-periodo="' . $periodo . '"', $auto);
        foreach (['id="auto-ativo"', 'id="auto-hora"', 'id="auto-backup"', 'id="auto-salvar"', 'id="auto-verificar"'] as $gancho) {
            $this->assertStringContainsString($gancho, $auto, $gancho);
        }
        // Toda variável usada na tela existe no manifesto, no idioma.
        $manifesto = json_decode((string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/admin-atualizacoes/admin-atualizacoes.json'), true);
        $variaveis = array_column($manifesto['resources'][$idioma]['variables'], 'value', 'id');
        preg_match_all('/@\[\[([a-z0-9-]+)\]\]@/', $html, $usadas);
        foreach (array_unique($usadas[1]) as $id) {
            $this->assertNotSame('', trim((string)($variaveis[$id] ?? '')), "$id ($idioma)");
        }
    }

    /** @return array<int, array{0: string}> */
    public static function idiomas(): array
    {
        return [['pt-br'], ['en']];
    }
}
