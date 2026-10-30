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

    public function preparar(): void
    {
        $this->sql("INSERT INTO usuarios_perfis (id_usuarios_perfis, id, language, max_sub_users) VALUES
            (1, 'sem-equipe', 'pt-br', 0), (2, 'clinica', 'pt-br', 2), (3, 'ilimitado', 'pt-br', -1)");
        $this->sql("INSERT INTO usuarios (id_usuarios, id_usuarios_perfis, nome, id, usuario, email, status) VALUES
            (" . self::TITULAR . ", 2, 'Clínica', 'clinica', 'clinica', 'clinica@teste.local', 'A'),
            (" . self::OUTRO_TITULAR . ", 3, 'Outra', 'outra', 'outra', 'outra@teste.local', 'A'),
            (30, 1, 'Solo', 'solo', 'solo', 'solo@teste.local', 'A')");
    }

    private function sql(string $sql): void
    {
        $GLOBALS['pdo']->exec($sql);
    }

    private function valor(string $sql)
    {
        return $GLOBALS['pdo']->query($sql)->fetch(PDO::FETCH_NUM)[0] ?? null;
    }

    private function criar(string $email = 'membro@teste.local', int $titular = self::TITULAR): array
    {
        return \usuario_equipe_membro_criar($titular, ['nome' => 'Ana Maria Souza', 'email' => $email, 'senha' => self::SENHA]);
    }

    private function filho(int $id, string $status = 'A', int $pai = self::TITULAR): int
    {
        $this->sql("INSERT INTO usuarios (id_usuarios, id_usuarios_pai, id_usuarios_perfis, nome, id, usuario, email, status)
            VALUES ({$id}, {$pai}, 2, 'Filho {$id}', 'filho-{$id}', 'f{$id}', 'f{$id}@teste.local', '{$status}')");
        return $id;
    }

    public function testOrganizacaoDeContaPropriaEOProprioUsuario(): void
    {
        $this->assertSame(self::TITULAR, \usuario_organizacao_id(self::TITULAR));
    }

    public function testOrganizacaoDoFilhoEOTitular(): void
    {
        $this->assertSame(self::TITULAR, \usuario_organizacao_id($this->filho(40)));
    }

    public function testOrganizacaoSemArgumentoUsaUsuarioAutenticado(): void
    {
        $GLOBALS['_GESTOR']['usuario-id'] = $this->filho(40);

        $this->assertSame(self::TITULAR, \usuario_organizacao_id());
    }

    public function testOrganizacaoSemAutenticacaoEZero(): void
    {
        $this->assertSame(0, \usuario_organizacao_id());
    }

    public function testBancoSemAColunaMantemOContratoDoProprioUsuario(): void
    {
        $this->sql('ALTER TABLE usuarios DROP COLUMN id_usuarios_pai');

        $this->assertSame(self::TITULAR, \usuario_organizacao_id(self::TITULAR));
        $this->assertFalse(\usuario_hierarquia_bloqueado(self::TITULAR));
    }

    public function testCriaMembroComOPerfilDoTitular(): void
    {
        $resultado = $this->criar();

        $this->assertTrue($resultado['ok']);
        $id = $resultado['id_usuarios'];
        $this->assertSame(self::TITULAR, (int)$this->valor("SELECT id_usuarios_pai FROM usuarios WHERE id_usuarios={$id}"));
        $this->assertSame(2, (int)$this->valor("SELECT id_usuarios_perfis FROM usuarios WHERE id_usuarios={$id}"));
        $this->assertSame('membro@teste.local', $this->valor("SELECT usuario FROM usuarios WHERE id_usuarios={$id}"));
        $this->assertSame('A', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$id}"));
    }

    public function testSenhaDoMembroValeNoLogin(): void
    {
        $id = $this->criar()['id_usuarios'];

        // O login compara a senha depois de banco_escape_field().
        $hash = $this->valor("SELECT senha FROM usuarios WHERE id_usuarios={$id}");
        $this->assertTrue(password_verify(\banco_escape_field(self::SENHA), $hash));
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

    public function testPerfilSemCotaNaoCriaMembro(): void
    {
        $this->assertSame(['ok' => false, 'erro' => 'team-quota-reached'], $this->criar('a@teste.local', 30));
        $this->assertFalse(\usuario_equipe_habilitada(30));
    }

    public function testCotaMenosUmEIlimitada(): void
    {
        foreach (range(1, 5) as $n) {
            $this->assertTrue($this->criar("m{$n}@teste.local", self::OUTRO_TITULAR)['ok']);
        }

        $cota = \usuario_equipe_cota(self::OUTRO_TITULAR);
        $this->assertTrue($cota['ilimitado']);
        $this->assertTrue($cota['disponivel']);
        $this->assertSame(5, $cota['usados']);
    }

    public function testMembroInativoNaoOcupaAssento(): void
    {
        $this->filho(40, 'I');
        $this->filho(41, 'I');
        $this->filho(42);

        $cota = \usuario_equipe_cota(self::TITULAR);

        $this->assertSame(1, $cota['usados']);
        $this->assertTrue($cota['disponivel']);
    }

    public function testMembroExcluidoNaoApareceNemOcupaAssento(): void
    {
        $this->filho(40, 'D');

        $this->assertSame([], \usuario_equipe_membros(self::TITULAR));
        $this->assertSame(0, \usuario_equipe_cota(self::TITULAR)['usados']);
    }

    public function testAtivarAcimaDaCotaERecusado(): void
    {
        $this->filho(40);
        $this->filho(41);
        $inativo = $this->filho(42, 'I');

        $this->assertSame(['ok' => false, 'erro' => 'team-quota-reached'], \usuario_equipe_membro_status(self::TITULAR, $inativo, 'A'));
        $this->assertSame('I', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$inativo}"));
    }

    public function testAtivarComAssentoLivreFunciona(): void
    {
        $inativo = $this->filho(40, 'I');

        $this->assertTrue(\usuario_equipe_membro_status(self::TITULAR, $inativo, 'A')['ok']);
        $this->assertSame('A', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$inativo}"));
    }

    public function testDesativarIgnoraACotaMesmoAcimaDoLimite(): void
    {
        $this->filho(40);
        $this->filho(41);
        $excedente = $this->filho(42);

        $this->assertTrue(\usuario_equipe_membro_status(self::TITULAR, $excedente, 'I')['ok']);
        $this->assertSame('I', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$excedente}"));
    }

    public function testTitularNaoAlteraMembroDeOutraOrganizacao(): void
    {
        $alheio = $this->filho(40, 'A', self::OUTRO_TITULAR);

        $this->assertSame(['ok' => false, 'erro' => 'team-member-not-found'], \usuario_equipe_membro_status(self::TITULAR, $alheio, 'I'));
        $this->assertSame('A', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$alheio}"));
    }

    public function testStatusFicaRestritoAAtivoEInativo(): void
    {
        $membro = $this->filho(40);

        foreach (['D', '', "A' OR '1'='1"] as $status) {
            $this->assertSame(['ok' => false, 'erro' => 'team-not-allowed'], \usuario_equipe_membro_status(self::TITULAR, $membro, $status));
        }
        $this->assertSame('A', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$membro}"));
    }

    public function testSubUsuarioNaoGereEquipe(): void
    {
        $filho = $this->filho(40);

        $this->assertSame(['ok' => false, 'erro' => 'team-not-allowed'], $this->criar('a@teste.local', $filho));
        $this->assertFalse(\usuario_equipe_habilitada($filho));
    }

    public function testTitularInativoNaoCriaMembro(): void
    {
        $this->sql("UPDATE usuarios SET status='I' WHERE id_usuarios=" . self::TITULAR);

        $this->assertSame(['ok' => false, 'erro' => 'team-not-allowed'], $this->criar());
    }

    public function testValidaOsCampos(): void
    {
        $base = ['nome' => 'Ana', 'email' => 'a@teste.local', 'senha' => self::SENHA];
        $casos = [
            'team-name-required' => ['nome' => '   '] + $base,
            'team-email-invalid' => ['email' => 'nao-e-email'] + $base,
            'team-password-weak' => ['senha' => 'curta'] + $base,
        ];

        foreach ($casos as $erro => $dados) {
            $this->assertSame(['ok' => false, 'erro' => $erro], \usuario_equipe_membro_criar(self::TITULAR, $dados));
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

        $this->assertFalse(\usuario_hierarquia_bloqueado(self::TITULAR));
    }

    public function testFilhoAtivoComPaiAtivoEntra(): void
    {
        $this->assertFalse(\usuario_hierarquia_bloqueado($this->filho(40)));
    }

    public function testFilhoEBloqueadoEnquantoOPaiEstiverInativo(): void
    {
        $filho = $this->filho(40);

        foreach (['I', 'D'] as $status) {
            $this->sql("UPDATE usuarios SET status='{$status}' WHERE id_usuarios=" . self::TITULAR);
            $this->assertTrue(\usuario_hierarquia_bloqueado($filho), "pai com status {$status}");
        }

        $this->sql("UPDATE usuarios SET status='A' WHERE id_usuarios=" . self::TITULAR);
        $this->assertFalse(\usuario_hierarquia_bloqueado($filho));
        $this->assertSame('A', $this->valor("SELECT status FROM usuarios WHERE id_usuarios={$filho}"));
    }

    public function testFilhoDesativadoEBloqueado(): void
    {
        $this->assertTrue(\usuario_hierarquia_bloqueado($this->filho(40, 'I')));
    }
}

$cenarios = new UsuarioHierarquiaCenarios();
$cenarios->preparar();
$cenarios->{$argv[1]}();
