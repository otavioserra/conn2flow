<?php

declare(strict_types=1);

namespace Conn2Flow\Tests\Unit\PHP;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * REQ-297 — titular e sub-usuários: tenant, cotas por perfil e bloqueio herdado do pai.
 *
 * Os cenários de banco ficam em `tests/Fixtures/UsuarioHierarquiaCenarios.php` e rodam fora do
 * bootstrap: o `banco.php` real exige conexão mysqli, e o arnês simula só a E/S sobre SQLite.
 */
final class UsuarioHierarquiaReq297Test extends TestCase
{
    private const CENARIOS = CONN2FLOW_ROOT . '/tests/Fixtures/UsuarioHierarquiaCenarios.php';

    /**
     * @return array<string, array{0: string}>
     */
    public static function cenarios(): array
    {
        preg_match_all('/public function (test\w+)\(/', (string)file_get_contents(self::CENARIOS), $nomes);

        return array_combine($nomes[1], array_map(fn(string $nome) => [$nome], $nomes[1]));
    }

    #[DataProvider('cenarios')]
    public function testCenario(string $nome): void
    {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(self::CENARIOS) . ' ' . escapeshellarg($nome) . ' 2>&1', $saida, $status);

        $this->assertSame(0, $status, implode("\n", $saida));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function barreiras(): array
    {
        return [
            'sessão web aberta' => ['gestor.php', 'usuario_hierarquia_bloqueado((int)$usuarios_tokens[0][\'id_usuarios\'])'],
            'login web' => ['bibliotecas/usuario.php', 'if(usuario_hierarquia_bloqueado((int)$id_usuarios)) return false;'],
            'token pessoal' => ['bibliotecas/usuario.php', 'if(usuario_hierarquia_bloqueado((int)$usuarios[0][\'id_usuarios\'])) return false;'],
            'emissão OAuth' => ['bibliotecas/oauth2.php', 'if(usuario_hierarquia_bloqueado((int)$id_usuarios)){'],
            'validação OAuth' => ['bibliotecas/oauth2.php', 'if(usuario_hierarquia_bloqueado((int)$usuarios[0][\'id_usuarios\'])){'],
            'login do aplicativo' => ['controladores/api/api-auth.php', '!usuario_hierarquia_bloqueado((int) $usuarios[\'id_usuarios\'])'],
        ];
    }

    #[DataProvider('barreiras')]
    public function testTodaEntradaConfereOBloqueio(string $arquivo, string $trecho): void
    {
        $fonte = file_get_contents(CONN2FLOW_ROOT . '/gestor/' . $arquivo);

        $this->assertStringContainsString($trecho, $fonte);
    }
}
