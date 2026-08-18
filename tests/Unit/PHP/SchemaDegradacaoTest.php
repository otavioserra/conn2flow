<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'gestor.php';
require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'usuario.php';

/**
 * req-119 (BATCH-122) — degradação graciosa quando a migração ainda não rodou.
 *
 * O código do sistema chega por ARQUIVOS e o schema por MIGRAÇÃO, e as duas coisas não pousam juntas
 * em toda instalação: a migração pode falhar e o deploy prosseguir, a atualização pode ser só de
 * arquivos, o operador pode usar `--skip-migrate`, e existe a janela entre os arquivos chegarem e a
 * migração terminar — nela, toda requisição roda código novo contra schema velho.
 *
 * O desfecho aceitável é a funcionalidade nova NÃO APARECER; nunca um erro numa tela que já
 * funcionava. Estes testes usam o cache de schema em `$_GESTOR` para simular os dois mundos sem
 * tocar o banco.
 */
final class SchemaDegradacaoTest extends TestCase
{
    protected function setUp(): void
    {
        global $_GESTOR;

        unset($_GESTOR['schema-tabelas'], $_GESTOR['schema-campos']);
    }

    protected function tearDown(): void
    {
        global $_GESTOR;

        unset($_GESTOR['schema-tabelas'], $_GESTOR['schema-campos']);
    }

    /** Simula o banco DEPOIS da migração. */
    private static function comSchema(): void
    {
        global $_GESTOR;

        $_GESTOR['schema-tabelas'] = array_flip(['usuarios', 'usuarios_tokens', 'usuarios_api_tokens']);
        $_GESTOR['schema-campos'] = ['usuarios.two_factor_recovery_codes' => true];
    }

    /** Simula o banco ANTES da migração: código novo, schema velho. */
    private static function semSchema(): void
    {
        global $_GESTOR;

        $_GESTOR['schema-tabelas'] = array_flip(['usuarios', 'usuarios_tokens']);
        $_GESTOR['schema-campos'] = ['usuarios.two_factor_recovery_codes' => false];
    }

    // ===== Detectores

    public function testTabelaPresenteEhReconhecida(): void
    {
        self::comSchema();

        self::assertTrue(gestor_schema_tabela_existe('usuarios_api_tokens'));
    }

    public function testTabelaAusenteEhReconhecida(): void
    {
        self::semSchema();

        self::assertFalse(gestor_schema_tabela_existe('usuarios_api_tokens'));
    }

    public function testNomeVazioNuncaEhTratadoComoTabelaExistente(): void
    {
        self::comSchema();

        self::assertFalse(gestor_schema_tabela_existe(''));
    }

    public function testCampoDeTabelaInexistenteNaoEhConsultado(): void
    {
        // `SHOW COLUMNS` sobre tabela inexistente é erro de SQL — e a ideia do gate é justamente
        // não produzir nenhum.
        self::semSchema();

        self::assertFalse(gestor_schema_campo_existe('qualquer', 'usuarios_api_tokens'));
    }

    public function testResultadoDeCampoEhMemoizado(): void
    {
        global $_GESTOR;

        self::comSchema();

        self::assertTrue(gestor_schema_campo_existe('two_factor_recovery_codes', 'usuarios'));
        self::assertArrayHasKey('usuarios.two_factor_recovery_codes', $_GESTOR['schema-campos']);
    }

    // ===== Personal Access Tokens

    public function testPatEhOferecidoComOSchemaPronto(): void
    {
        self::comSchema();

        self::assertTrue(usuario_api_tokens_disponivel());
    }

    public function testPatNaoEhOferecidoSemOSchema(): void
    {
        self::semSchema();

        self::assertFalse(usuario_api_tokens_disponivel());
    }

    public function testGerarTokenNaoTocaOBancoSemOSchema(): void
    {
        // Sem o gate, esta chamada faria um INSERT numa tabela inexistente.
        self::semSchema();

        self::assertFalse(usuario_api_token_gerar(10, 'CLI Local'));
    }

    public function testListarTokensDevolveVazioSemOSchema(): void
    {
        // É o que faz a aba do perfil sumir em vez de erro na primeira consulta.
        self::semSchema();

        self::assertSame([], usuario_api_tokens_listar(10));
    }

    public function testRevogarTokenNaoExecutaSemOSchema(): void
    {
        self::semSchema();

        self::assertFalse(usuario_api_token_revogar(7, 10));
    }

    public function testValidarTokenDaApiRecusaSemOSchema(): void
    {
        // Na API o desfecho correto é "credencial inválida", não erro 500: o token realmente não
        // vale nada enquanto a tabela não existir.
        self::semSchema();

        self::assertFalse(usuario_api_token_validar('c2f_pat_abcdef0123456789'));
    }

    // ===== Códigos de recuperação

    public function testRecoveryCodesSaoOferecidosComOSchemaPronto(): void
    {
        self::comSchema();

        self::assertTrue(usuario_recovery_codes_disponivel());
    }

    public function testRecoveryCodesNaoSaoOferecidosSemAColuna(): void
    {
        self::semSchema();

        self::assertFalse(usuario_recovery_codes_disponivel());
    }

    public function testGeracaoDeCodigosContinuaPuraEIndependenteDoSchema(): void
    {
        // A geração em si não toca o banco: quem decide se ela é chamada é o gate, no módulo. Isso
        // mantém a função testável e reaproveitável.
        self::semSchema();

        self::assertCount(10, usuario_recovery_codes_gerar(10));
    }
}
