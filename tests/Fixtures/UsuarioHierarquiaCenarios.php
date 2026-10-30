<?php
/**
 * REQ-297 — cenários da hierarquia de usuários. Cada `test*` roda num processo à parte, disparado
 * por UsuarioHierarquiaReq297Test: uso `php UsuarioHierarquiaCenarios.php <nome>`, saída 0 = aprovado.
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/UsuarioHierarquiaHarness.php';

final class UsuarioHierarquiaCenarios extends PHPUnit\Framework\Assert
{
    private const TITULAR = 10;
    private const OUTRO_TITULAR = 20;
    private const SENHA = 'senha-bem-longa-1';
    private const PERFIL_EQUIPE = 100;
    private const PERFIL_OUTRA_EQUIPE = 200;

    public function preparar(): void
    {
        $this->sql("INSERT INTO usuarios_perfis (id_usuarios_perfis, id, language, max_sub_users, sub_users_active, id_usuarios_dono, status) VALUES
            (1, 'sem-equipe', 'pt-br', 0, 0, NULL, 'A'), (2, 'clinica', 'pt-br', 2, 1, NULL, 'A'), (3, 'ilimitado', 'pt-br', -1, 1, NULL, 'A'),
            (" . self::PERFIL_EQUIPE . ", 'equipe-10-recepcao', 'pt-br', 0, 0, " . self::TITULAR . ", 'A'),
            (" . self::PERFIL_OUTRA_EQUIPE . ", 'equipe-20-recepcao', 'pt-br', 0, 0, " . self::OUTRO_TITULAR . ", 'A')");
        $this->sql("INSERT INTO usuarios (id_usuarios, id_usuarios_perfis, nome, id, usuario, email, status) VALUES
            (" . self::TITULAR . ", 2, 'Clínica', 'clinica', 'clinica', 'clinica@teste.local', 'A'),
            (" . self::OUTRO_TITULAR . ", 3, 'Outra', 'outra', 'outra', 'outra@teste.local', 'A'),
            (30, 1, 'Solo', 'solo', 'solo', 'solo@teste.local', 'A')");
        $this->sql("INSERT INTO modulos (id, nome, modulo_grupo_id, language, status) VALUES
            ('agenda', 'Agenda', 'g', 'pt-br', 'A'), ('pacientes', 'Pacientes', 'g', 'pt-br', 'A'), ('financeiro', 'Financeiro', 'g', 'pt-br', 'A'),
            ('equipe', 'Equipe', 'g', 'pt-br', 'A'), ('perfil-usuario', 'Perfil', 'g', 'pt-br', 'A')");
        $this->sql("INSERT INTO modulos_operacoes (id, nome, modulo_id, language, status) VALUES
            ('agenda-exportar', 'Exportar', 'agenda', 'pt-br', 'A'), ('financeiro-baixar', 'Baixar', 'financeiro', 'pt-br', 'A')");
        $this->sql("INSERT INTO usuarios_perfis_modulos (perfil, modulo) VALUES
            ('clinica', 'agenda'), ('clinica', 'pacientes'), ('clinica', 'equipe'), ('clinica', 'perfil-usuario'),
            ('equipe-10-recepcao', 'agenda'), ('equipe-10-recepcao', 'perfil-usuario')");
        $this->sql("INSERT INTO usuarios_perfis_modulos_operacoes (perfil, operacao) VALUES ('clinica', 'agenda-exportar')");
        $GLOBALS['_GESTOR']['linguagem-codigo'] = 'pt-br';
    }

    private function sql(string $sql): void
    {
        $GLOBALS['pdo']->exec($sql);
    }

    private function valor(string $sql)
    {
        return $GLOBALS['pdo']->query($sql)->fetch(PDO::FETCH_NUM)[0] ?? null;
    }

    private function criar(string $email = 'membro@teste.local', int $titular = self::TITULAR, int $perfil = self::PERFIL_EQUIPE): array
    {
        return usuario_equipe_membro_criar($titular, ['nome' => 'Ana Maria Souza', 'email' => $email, 'senha' => self::SENHA, 'id_perfil' => $perfil]);
    }

    private function filho(int $id, string $status = 'A', int $pai = self::TITULAR, int $perfil = self::PERFIL_EQUIPE): int
    {
        $this->sql("INSERT INTO usuarios (id_usuarios, id_usuarios_pai, id_usuarios_perfis, nome, id, usuario, email, status)
            VALUES ({$id}, {$pai}, {$perfil}, 'Filho {$id}', 'filho-{$id}', 'f{$id}', 'f{$id}@teste.local', '{$status}')");
        return $id;
    }

    /** Executa o filtro de permissão como as leituras reais do sistema. */
    private function modulosEfetivos(int $id_usuario, string $perfil): array
    {
        $sql = "SELECT modulo FROM usuarios_perfis_modulos WHERE perfil='{$perfil}'" . usuario_equipe_permissao_sql($id_usuario);
        return $GLOBALS['pdo']->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function testOrganizacaoDeContaPropriaEOProprioUsuario(): void
    {
        $this->assertSame(self::TITULAR, usuario_organizacao_id(self::TITULAR));
    }

    public function testOrganizacaoDoFilhoEOTitular(): void
    {
        $this->assertSame(self::TITULAR, usuario_organizacao_id($this->filho(40)));
    }

    public function testOrganizacaoSemArgumentoUsaUsuarioAutenticado(): void
    {
        $GLOBALS['_GESTOR']['usuario-id'] = $this->filho(40);

        $this->assertSame(self::TITULAR, usuario_organizacao_id());
    }

    public function testOrganizacaoSemAutenticacaoEZero(): void
    {
        $this->assertSame(0, usuario_organizacao_id());
    }

    public function testBancoSemAColunaMantemOContratoDoProprioUsuario(): void
    {
        $this->sql('ALTER TABLE usuarios DROP COLUMN id_usuarios_pai');

        $this->assertSame(self::TITULAR, usuario_organizacao_id(self::TITULAR));
        $this->assertFalse(usuario_hierarquia_bloqueado(self::TITULAR));
        $this->assertSame('', usuario_equipe_permissao_sql(self::TITULAR));
    }

    public function testCriaMembroComOPerfilDaEquipe(): void
    {
        $resultado = $this->criar();

        $this->assertTrue($resultado['ok']);
        $id = $resultado['id_usuarios'];
        $this->assertSame(self::TITULAR, (int)$this->valor("SELECT id_usuarios_pai FROM usuarios WHERE id_usuarios={$id}"));
        $this->assertSame(self::PERFIL_EQUIPE, (int)$this->valor("SELECT id_usuarios_perfis FROM usuarios WHERE id_usuarios={$id}"));
        $this->assertSame('membro@teste.local', $this->valor("SELECT usuario FROM usuarios WHERE id_usuarios={$id}"));
    }

    public function testMembroNaoNasceComOPerfilDoTitularNemDeOutraConta(): void
    {
        foreach ([2, 1, self::PERFIL_OUTRA_EQUIPE, 0] as $perfil) {
            $this->assertSame(['ok' => false, 'erro' => 'team-profile-required'], $this->criar('a@teste.local', self::TITULAR, $perfil), "perfil {$perfil}");
        }
    }

    public function testSenhaDoMembroValeNoLogin(): void
    {
        $id = $this->criar()['id_usuarios'];

        // O login compara a senha depois de banco_escape_field().
        $hash = $this->valor("SELECT senha FROM usuarios WHERE id_usuarios={$id}");
        $this->assertTrue(password_verify(banco_escape_field(self::SENHA), $hash));
    }

    public function testSeparaNomesDoMembro(): void
    {
        $id = $this->criar()['id_usuarios'];

        $this->assertSame('Ana', $this->valor("SELECT primeiro_nome FROM usuarios WHERE id_usuarios={$id}"));
        $this->assertSame('Maria', $this->valor("SELECT nome_do_meio FROM usuarios WHERE id_usuarios={$id}"));
        $this->assertSame('Souza', $this->valor("SELECT ultimo_nome FROM usuarios WHERE id_usuarios={$id}"));
    }

    public function testCotaTravaNoLimiteDoPerfil(): void
    {
        $this->assertTrue($this->criar('a@teste.local')['ok']);
        $this->assertTrue($this->criar('b@teste.local')['ok']);

        $this->assertSame(['ok' => false, 'erro' => 'team-quota-reached'], $this->criar('c@teste.local'));
        $this->assertSame(2, (int)$this->valor('SELECT COUNT(*) FROM usuarios WHERE id_usuarios_pai=' . self::TITULAR));
    }

    public function testPerfilSemEquipeNaoCriaMembro(): void
    {
        $this->assertSame(['ok' => false, 'erro' => 'team-not-allowed'], $this->criar('a@teste.local', 30));
        $this->assertFalse(usuario_equipe_habilitada(30));
    }

    public function testCotaMenosUmEIlimitada(): void
    {
        foreach (range(1, 5) as $n) {
            $this->assertTrue($this->criar("m{$n}@teste.local", self::OUTRO_TITULAR, self::PERFIL_OUTRA_EQUIPE)['ok']);
        }

        $cota = usuario_equipe_cota(self::OUTRO_TITULAR);
        $this->assertTrue($cota['ilimitado']);
        $this->assertTrue($cota['disponivel']);
        $this->assertSame(5, $cota['usados']);
    }

    public function testChaveDesligadaDesligaAEquipeMesmoComLimite(): void
    {
        $this->sql("UPDATE usuarios_perfis SET sub_users_active=0 WHERE id='clinica'");

        $cota = usuario_equipe_cota(self::TITULAR);
        $this->assertFalse($cota['ativa']);
        $this->assertFalse($cota['disponivel']);
        $this->assertSame(2, $cota['limite']);
        $this->assertFalse(usuario_equipe_habilitada(self::TITULAR));
    }

    public function testLimiteZeroDesligaAEquipeMesmoComAChaveLigada(): void
    {
        $this->sql("UPDATE usuarios_perfis SET max_sub_users=0 WHERE id='clinica'");

        $this->assertFalse(usuario_equipe_cota(self::TITULAR)['ativa']);
        $this->assertFalse(usuario_equipe_habilitada(self::TITULAR));
    }

    public function testChaveDesligadaBloqueiaMembroExistenteEReligarDevolve(): void
    {
        $filho = $this->filho(40);
        $this->assertFalse(usuario_hierarquia_bloqueado($filho));

        $this->sql("UPDATE usuarios_perfis SET sub_users_active=0 WHERE id='clinica'");
        $this->assertTrue(usuario_hierarquia_bloqueado($filho));

        $this->sql("UPDATE usuarios_perfis SET sub_users_active=1 WHERE id='clinica'");
        $this->assertFalse(usuario_hierarquia_bloqueado($filho));
        $this->assertSame('A', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$filho}"));
    }

    public function testLimiteZeroBloqueiaMembroExistente(): void
    {
        $filho = $this->filho(40);
        $this->sql("UPDATE usuarios_perfis SET max_sub_users=0 WHERE id='clinica'");

        $this->assertTrue(usuario_hierarquia_bloqueado($filho));
    }

    public function testMembroInativoNaoOcupaAssento(): void
    {
        $this->filho(40, 'I');
        $this->filho(41, 'I');
        $this->filho(42);

        $cota = usuario_equipe_cota(self::TITULAR);

        $this->assertSame(1, $cota['usados']);
        $this->assertTrue($cota['disponivel']);
    }

    public function testMembroExcluidoNaoApareceNemOcupaAssento(): void
    {
        $this->filho(40, 'D');

        $this->assertSame([], usuario_equipe_membros(self::TITULAR));
        $this->assertSame(0, usuario_equipe_cota(self::TITULAR)['usados']);
    }

    public function testAtivarAcimaDaCotaERecusado(): void
    {
        $this->filho(40);
        $this->filho(41);
        $inativo = $this->filho(42, 'I');

        $this->assertSame(['ok' => false, 'erro' => 'team-quota-reached'], usuario_equipe_membro_status(self::TITULAR, $inativo, 'A'));
        $this->assertSame('I', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$inativo}"));
    }

    public function testAtivarComAssentoLivreFunciona(): void
    {
        $inativo = $this->filho(40, 'I');

        $this->assertTrue(usuario_equipe_membro_status(self::TITULAR, $inativo, 'A')['ok']);
        $this->assertSame('A', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$inativo}"));
    }

    public function testDesativarIgnoraACotaMesmoAcimaDoLimite(): void
    {
        $this->filho(40);
        $this->filho(41);
        $excedente = $this->filho(42);

        $this->assertTrue(usuario_equipe_membro_status(self::TITULAR, $excedente, 'I')['ok']);
        $this->assertSame('I', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$excedente}"));
    }

    public function testTitularNaoAlteraMembroDeOutraOrganizacao(): void
    {
        $alheio = $this->filho(40, 'A', self::OUTRO_TITULAR, self::PERFIL_OUTRA_EQUIPE);

        $this->assertSame(['ok' => false, 'erro' => 'team-member-not-found'], usuario_equipe_membro_status(self::TITULAR, $alheio, 'I'));
        $this->assertSame(['ok' => false, 'erro' => 'team-member-not-found'], usuario_equipe_membro_perfil(self::TITULAR, $alheio, self::PERFIL_EQUIPE));
        $this->assertSame('A', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$alheio}"));
    }

    public function testStatusFicaRestritoAAtivoEInativo(): void
    {
        $membro = $this->filho(40);

        foreach (['D', '', "A' OR '1'='1"] as $status) {
            $this->assertSame(['ok' => false, 'erro' => 'team-not-allowed'], usuario_equipe_membro_status(self::TITULAR, $membro, $status));
        }
        $this->assertSame('A', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$membro}"));
    }

    public function testSubUsuarioNaoGereEquipe(): void
    {
        $filho = $this->filho(40);

        $this->assertSame(['ok' => false, 'erro' => 'team-not-allowed'], $this->criar('a@teste.local', $filho));
        $this->assertFalse(usuario_equipe_habilitada($filho));
        $this->assertSame(['ok' => false, 'erro' => 'team-not-allowed'], usuario_equipe_perfil_salvar($filho, ['nome' => 'X', 'modulos' => ['agenda']]));
    }

    public function testTitularInativoNaoCriaMembro(): void
    {
        $this->sql("UPDATE usuarios SET status='I' WHERE id_usuarios=" . self::TITULAR);

        $this->assertSame(['ok' => false, 'erro' => 'team-not-allowed'], $this->criar());
    }

    public function testValidaOsCampos(): void
    {
        $base = ['nome' => 'Ana', 'email' => 'a@teste.local', 'senha' => self::SENHA, 'id_perfil' => self::PERFIL_EQUIPE];
        $casos = [
            'team-name-required' => ['nome' => '   '] + $base,
            'team-email-invalid' => ['email' => 'nao-e-email'] + $base,
            'team-password-weak' => ['senha' => 'curta'] + $base,
        ];

        foreach ($casos as $erro => $dados) {
            $this->assertSame(['ok' => false, 'erro' => $erro], usuario_equipe_membro_criar(self::TITULAR, $dados));
        }
    }

    public function testEmailRepetidoERecusado(): void
    {
        $this->assertSame(['ok' => false, 'erro' => 'team-email-taken'], $this->criar('outra@teste.local'));
    }

    public function testEmailComAspasNaoQuebraAConsulta(): void
    {
        $this->assertTrue($this->criar("o'neil@teste.local")['ok']);
        $this->assertEmpty($GLOBALS['erros_sql'] ?? []);
    }

    public function testTravaOcupadaNaoGravaESempreLibera(): void
    {
        $GLOBALS['trava_ocupada'] = true;

        $this->assertSame(['ok' => false, 'erro' => 'team-busy'], $this->criar());
        $this->assertSame(0, (int)$this->valor('SELECT COUNT(*) FROM usuarios WHERE id_usuarios_pai=' . self::TITULAR));

        unset($GLOBALS['trava_ocupada']);
        $this->filho(40);
        $this->filho(41);
        $this->assertFalse($this->criar()['ok']);
        $this->assertSame(['c2f-equipe-' . self::TITULAR], $GLOBALS['liberadas']);
    }

    public function testContaPropriaNuncaEBloqueadaPelaHierarquia(): void
    {
        $this->sql("UPDATE usuarios SET status='I' WHERE id_usuarios=" . self::TITULAR);

        $this->assertFalse(usuario_hierarquia_bloqueado(self::TITULAR));
    }

    public function testFilhoEBloqueadoEnquantoOPaiEstiverInativo(): void
    {
        $filho = $this->filho(40);

        foreach (['I', 'D'] as $status) {
            $this->sql("UPDATE usuarios SET status='{$status}' WHERE id_usuarios=" . self::TITULAR);
            $this->assertTrue(usuario_hierarquia_bloqueado($filho), "pai com status {$status}");
        }

        $this->sql("UPDATE usuarios SET status='A' WHERE id_usuarios=" . self::TITULAR);
        $this->assertFalse(usuario_hierarquia_bloqueado($filho));
        $this->assertSame('A', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$filho}"));
    }

    public function testFilhoDesativadoEBloqueado(): void
    {
        $this->assertTrue(usuario_hierarquia_bloqueado($this->filho(40, 'I')));
    }

    public function testDisponivelSoTrazOQueOTitularRecebeu(): void
    {
        $d = usuario_equipe_permissoes_disponiveis(self::TITULAR);

        $this->assertSame(['agenda', 'pacientes'], array_keys($d['modulos']));
        $this->assertSame(['agenda-exportar'], array_keys($d['operacoes']));
    }

    public function testEquipeEPerfilUsuarioFicamForaDaEscolha(): void
    {
        $d = usuario_equipe_permissoes_disponiveis(self::TITULAR);

        $this->assertArrayNotHasKey('equipe', $d['modulos']);
        $this->assertArrayNotHasKey('perfil-usuario', $d['modulos']);
    }

    public function testCriaPerfilDaEquipeESempreIncluiPerfilUsuario(): void
    {
        $r = usuario_equipe_perfil_salvar(self::TITULAR, ['nome' => 'Fisio', 'modulos' => ['pacientes'], 'operacoes' => []]);

        $this->assertTrue($r['ok']);
        $id = $this->valor("SELECT id FROM usuarios_perfis WHERE id_usuarios_perfis={$r['id_perfil']}");
        $this->assertStringContainsString('equipe-10', $id);
        $this->assertSame(self::TITULAR, (int)$this->valor("SELECT id_usuarios_dono FROM usuarios_perfis WHERE id_usuarios_perfis={$r['id_perfil']}"));
        $modulos = $GLOBALS['pdo']->query("SELECT modulo FROM usuarios_perfis_modulos WHERE perfil='{$id}' ORDER BY modulo")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame(['pacientes', 'perfil-usuario'], $modulos);
    }

    public function testNaoDistribuiModuloQueOTitularNaoTem(): void
    {
        foreach (['financeiro', 'equipe', 'inexistente'] as $modulo) {
            $this->assertSame(['ok' => false, 'erro' => 'team-permission-denied'], usuario_equipe_perfil_salvar(self::TITULAR, ['nome' => 'X', 'modulos' => ['agenda', $modulo]]), $modulo);
        }
        $this->assertSame(0, (int)$this->valor("SELECT COUNT(*) FROM usuarios_perfis WHERE nome='X'"));
    }

    public function testNaoDistribuiOperacaoQueOTitularNaoTem(): void
    {
        $this->assertSame(['ok' => false, 'erro' => 'team-permission-denied'], usuario_equipe_perfil_salvar(self::TITULAR, ['nome' => 'X', 'modulos' => ['agenda'], 'operacoes' => ['financeiro-baixar']]));
    }

    public function testPerfilExigeNomeEModulo(): void
    {
        $this->assertSame(['ok' => false, 'erro' => 'team-profile-name-required'], usuario_equipe_perfil_salvar(self::TITULAR, ['nome' => ' ', 'modulos' => ['agenda']]));
        $this->assertSame(['ok' => false, 'erro' => 'team-profile-modules-required'], usuario_equipe_perfil_salvar(self::TITULAR, ['nome' => 'X', 'modulos' => []]));
    }

    public function testAlterarPerfilTrocaAsPermissoes(): void
    {
        $r = usuario_equipe_perfil_salvar(self::TITULAR, ['id_perfil' => self::PERFIL_EQUIPE, 'nome' => 'Recepção 2', 'modulos' => ['pacientes'], 'operacoes' => []]);

        $this->assertTrue($r['ok']);
        $this->assertSame('Recepção 2', $this->valor('SELECT nome FROM usuarios_perfis WHERE id_usuarios_perfis=' . self::PERFIL_EQUIPE));
        $this->assertSame(['pacientes', 'perfil-usuario'], $GLOBALS['pdo']->query("SELECT modulo FROM usuarios_perfis_modulos WHERE perfil='equipe-10-recepcao' ORDER BY modulo")->fetchAll(PDO::FETCH_COLUMN));
    }

    public function testNaoAlteraNemExcluiPerfilDeOutraConta(): void
    {
        $this->assertSame(['ok' => false, 'erro' => 'team-profile-not-found'], usuario_equipe_perfil_salvar(self::TITULAR, ['id_perfil' => self::PERFIL_OUTRA_EQUIPE, 'nome' => 'X', 'modulos' => ['agenda']]));
        $this->assertSame(['ok' => false, 'erro' => 'team-profile-not-found'], usuario_equipe_perfil_salvar(self::TITULAR, ['id_perfil' => 2, 'nome' => 'X', 'modulos' => ['agenda']]));
        $this->assertSame(['ok' => false, 'erro' => 'team-profile-not-found'], usuario_equipe_perfil_excluir(self::TITULAR, self::PERFIL_OUTRA_EQUIPE));
        $this->assertSame(['ok' => false, 'erro' => 'team-profile-not-found'], usuario_equipe_perfil_excluir(self::TITULAR, 2));
    }

    public function testNaoExcluiPerfilEmUso(): void
    {
        $this->filho(40, 'I');

        $this->assertSame(['ok' => false, 'erro' => 'team-profile-in-use'], usuario_equipe_perfil_excluir(self::TITULAR, self::PERFIL_EQUIPE));
    }

    public function testExcluirPerfilLivreRemoveAsPermissoes(): void
    {
        $this->assertTrue(usuario_equipe_perfil_excluir(self::TITULAR, self::PERFIL_EQUIPE)['ok']);

        $this->assertSame('D', $this->valor('SELECT status FROM usuarios_perfis WHERE id_usuarios_perfis=' . self::PERFIL_EQUIPE));
        $this->assertSame(0, (int)$this->valor("SELECT COUNT(*) FROM usuarios_perfis_modulos WHERE perfil='equipe-10-recepcao'"));
        $this->assertSame([], usuario_equipe_perfis(self::TITULAR));
    }

    public function testMembroPodeTrocarSoParaPerfilDaEquipe(): void
    {
        $membro = $this->filho(40);

        $this->assertSame(['ok' => false, 'erro' => 'team-profile-required'], usuario_equipe_membro_perfil(self::TITULAR, $membro, 2));
        $this->assertSame(['ok' => false, 'erro' => 'team-profile-required'], usuario_equipe_membro_perfil(self::TITULAR, $membro, self::PERFIL_OUTRA_EQUIPE));

        $novo = usuario_equipe_perfil_salvar(self::TITULAR, ['nome' => 'Fisio', 'modulos' => ['pacientes']])['id_perfil'];

        $this->assertTrue(usuario_equipe_membro_perfil(self::TITULAR, $membro, $novo)['ok']);
        $this->assertSame($novo, (int)$this->valor("SELECT id_usuarios_perfis FROM usuarios WHERE id_usuarios={$membro}"));
    }

    public function testContaPropriaNaoRecebeFiltroDePermissao(): void
    {
        $this->assertSame('', usuario_equipe_permissao_sql(self::TITULAR));
        $this->assertSame('', usuario_equipe_permissao_sql(0));
    }

    public function testMembroPerdeModuloJuntoComOTitular(): void
    {
        $membro = $this->filho(40);
        $this->assertContains('agenda', $this->modulosEfetivos($membro, 'equipe-10-recepcao'));

        $this->sql("DELETE FROM usuarios_perfis_modulos WHERE perfil='clinica' AND modulo='agenda'");
        $GLOBALS['_GESTOR']['usuario-organizacao'] = [];

        $this->assertNotContains('agenda', $this->modulosEfetivos($membro, 'equipe-10-recepcao'));
        $this->assertContains('perfil-usuario', $this->modulosEfetivos($membro, 'equipe-10-recepcao'));
    }

    public function testFiltroDeOperacaoUsaAsOperacoesDoTitular(): void
    {
        $sql = usuario_equipe_permissao_sql($this->filho(40), 'operacao');

        $this->assertStringContainsString("usuarios_perfis_modulos_operacoes WHERE perfil='clinica'", $sql);
        $this->assertStringContainsString(' AND operacao IN ', $sql);
    }
}

$cenarios = new UsuarioHierarquiaCenarios();
$cenarios->preparar();
$cenarios->{$argv[1]}();
