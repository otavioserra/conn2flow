<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'usuario.php';

/**
 * req-119 (BATCH-120) — Personal Access Tokens.
 *
 * A geração e a validação tocam o banco e ficam para a validação em runtime. O que está coberto aqui
 * é o que decide se uma credencial vale: o formato que separa PAT de token OAuth no MESMO cabeçalho
 * `Authorization: Bearer`, e a situação (ativo/revogado/expirado) que a API consulta a cada
 * requisição. Os códigos de recuperação estão em `UsuarioRecoveryCodesTest`.
 */
final class UsuarioApiTokensTest extends TestCase
{
    // ===== Formato: o desempate entre PAT e OAuth no mesmo `Authorization: Bearer`

    public function testTokenComPrefixoDoSistemaEhReconhecido(): void
    {
        self::assertTrue(usuario_api_token_formato('c2f_pat_abcdef0123456789'));
    }

    public function testJwtNaoEhConfundidoComPat(): void
    {
        // Sem esta separação, todo PAT passaria pelo validador de JWT e o usuário receberia
        // "token inválido" sem nenhuma pista do motivo.
        self::assertFalse(usuario_api_token_formato('eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.abc.def'));
    }

    public function testPrefixoSozinhoNaoEhTokenValido(): void
    {
        self::assertFalse(usuario_api_token_formato('c2f_pat_'));
    }

    public function testValoresNaoTextuaisNaoQuebram(): void
    {
        self::assertFalse(usuario_api_token_formato(''));
        self::assertFalse(usuario_api_token_formato(null));
        self::assertFalse(usuario_api_token_formato(['c2f_pat_x']));
    }

    // ===== Hash e prefixo exibível

    public function testHashEhDeterministicoEDeSha256(): void
    {
        $token = 'c2f_pat_abcdef0123456789';

        self::assertSame(usuario_api_token_hash($token), usuario_api_token_hash($token));
        self::assertSame(64, strlen(usuario_api_token_hash($token)));
        self::assertNotSame(usuario_api_token_hash($token), usuario_api_token_hash($token . 'x'));
    }

    public function testPrefixoExibivelMostraOitoCaracteresDaParteAleatoria(): void
    {
        // É o que permite reconhecer a chave na listagem sem que o segredo seja recuperável.
        self::assertSame('c2f_pat_abcdef01', usuario_api_token_prefixo('c2f_pat_abcdef0123456789'));
    }

    public function testPrefixoNaoDuplicaOPrefixoDoSistema(): void
    {
        self::assertStringStartsWith('c2f_pat_', usuario_api_token_prefixo('c2f_pat_abcdef0123456789'));
        self::assertSame(16, strlen(usuario_api_token_prefixo('c2f_pat_abcdef0123456789')));
    }

    public function testPrefixoNaoVazaOTokenInteiro(): void
    {
        $token = 'c2f_pat_' . str_repeat('a', 64);

        self::assertNotSame($token, usuario_api_token_prefixo($token));
        self::assertLessThan(strlen($token), strlen(usuario_api_token_prefixo($token)));
    }

    // ===== Situação do token

    public function testTokenAtivoSemExpiracaoEhPerpetuo(): void
    {
        // Expiração vazia é decisão do usuário na criação, não defeito de dado.
        self::assertSame('ativo', usuario_api_token_situacao(['status' => 'A', 'expiracao' => null]));
        self::assertSame('ativo', usuario_api_token_situacao(['status' => 'A', 'expiracao' => '']));
    }

    public function testTokenRevogadoNuncaVolta(): void
    {
        // Nem com expiração futura: `R` é decisão explícita do usuário.
        self::assertSame('revogado', usuario_api_token_situacao([
            'status' => 'R',
            'expiracao' => date('Y-m-d H:i:s', time() + 86400),
        ]));
    }

    public function testStatusDesconhecidoNaoEhTratadoComoAtivo(): void
    {
        // Falhar fechado: dado corrompido não pode virar credencial válida.
        self::assertSame('revogado', usuario_api_token_situacao(['status' => 'X']));
        self::assertSame('revogado', usuario_api_token_situacao([]));
    }

    public function testExpiracaoPassadaTornaOTokenExpirado(): void
    {
        self::assertSame('expirado', usuario_api_token_situacao([
            'status' => 'A',
            'expiracao' => date('Y-m-d H:i:s', time() - 60),
        ]));
    }

    public function testExpiracaoFuturaMantemOTokenAtivo(): void
    {
        self::assertSame('ativo', usuario_api_token_situacao([
            'status' => 'A',
            'expiracao' => date('Y-m-d H:i:s', time() + 60),
        ]));
    }

    public function testExpiradoEhDistintoDeRevogado(): void
    {
        // O usuário resolve os dois de formas diferentes: um recria, o outro não.
        $expirado = usuario_api_token_situacao(['status' => 'A', 'expiracao' => '2000-01-01 00:00:00']);
        $revogado = usuario_api_token_situacao(['status' => 'R']);

        self::assertNotSame($expirado, $revogado);
    }

    public function testDataDeExpiracaoIlegivelNaoDerrubaOToken(): void
    {
        // Falhar aberto aqui é o certo: o defeito é do dado, e invalidar a credencial de produção
        // por um formato inesperado seria pior que ignorá-lo.
        self::assertSame('ativo', usuario_api_token_situacao(['status' => 'A', 'expiracao' => 'nao-e-data']));
    }

    public function testSituacaoAceitaTimestampDeReferencia(): void
    {
        $registro = ['status' => 'A', 'expiracao' => '2026-01-01 00:00:00'];

        self::assertSame('ativo', usuario_api_token_situacao($registro, strtotime('2025-12-31 23:00:00')));
        self::assertSame('expirado', usuario_api_token_situacao($registro, strtotime('2026-01-02 00:00:00')));
    }

    // ===== Guardas de parâmetro (não chegam ao banco)

    public function testGerarSemUsuarioOuSemNomeNaoExecuta(): void
    {
        self::assertFalse(usuario_api_token_gerar(0, 'CLI'));
        self::assertFalse(usuario_api_token_gerar(10, ''));
        self::assertFalse(usuario_api_token_gerar(10, '   '));
    }

    public function testRevogarExigeTokenEUsuario(): void
    {
        // O id do token viaja pelo formulário; sem o dono no WHERE, um id adivinhado revogaria a
        // chave de outra conta.
        self::assertFalse(usuario_api_token_revogar(0, 10));
        self::assertFalse(usuario_api_token_revogar(5, 0));
    }

    public function testListarSemUsuarioDevolveVazioSemConsultarBanco(): void
    {
        self::assertSame([], usuario_api_tokens_listar(0));
    }

    public function testValidarRejeitaTokenForaDoFormatoSemConsultarBanco(): void
    {
        self::assertFalse(usuario_api_token_validar('eyJhbGciOiJSUzI1NiJ9.x.y'));
        self::assertFalse(usuario_api_token_validar(''));
    }
}
