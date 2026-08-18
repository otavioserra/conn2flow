<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'usuario.php';

/**
 * req-119 (BATCH-120) — códigos de recuperação de 2FA.
 */
final class UsuarioRecoveryCodesTest extends TestCase
{
    public function testGeraAQuantidadePedidaNoFormatoLegivel(): void
    {
        $codigos = usuario_recovery_codes_gerar(10);

        self::assertCount(10, $codigos);

        foreach ($codigos as $codigo) {
            self::assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $codigo);
        }
    }

    public function testAlfabetoExcluiCaracteresAmbiguos(): void
    {
        // O código é feito para ser copiado à mão de um papel: 0/O e 1/I/L são os que o usuário
        // digita errado, e cada erro custaria uma das dez chances dele.
        $juntos = implode('', usuario_recovery_codes_gerar(40));

        self::assertSame(0, preg_match('/[01OIL]/', $juntos));
    }

    public function testCodigosNaoSeRepetemNaMesmaGeracao(): void
    {
        $codigos = usuario_recovery_codes_gerar(10);

        self::assertCount(10, array_unique($codigos));
    }

    public function testQuantidadeInvalidaDevolveListaVazia(): void
    {
        self::assertSame([], usuario_recovery_codes_gerar(0));
        self::assertSame([], usuario_recovery_codes_gerar(-3));
    }

    // ===== Normalização

    public function testNormalizacaoIgnoraHifenEspacoECaixa(): void
    {
        // O usuário digita lendo de um papel; nenhuma dessas variações deveria custar uma chance.
        self::assertSame('ABCD2345', usuario_recovery_code_normalizar('abcd-2345'));
        self::assertSame('ABCD2345', usuario_recovery_code_normalizar('ABCD 2345'));
        self::assertSame('ABCD2345', usuario_recovery_code_normalizar('  aBcD-2345  '));
    }

    public function testHashEhCalculadoSobreAFormaNormalizada(): void
    {
        // Sem isso, `abcd-2345` e `ABCD2345` virariam códigos diferentes.
        self::assertSame(
            usuario_recovery_code_hash('ABCD2345'),
            usuario_recovery_code_hash('abcd-2345')
        );
    }

    // ===== Consumo de uso único

    public function testCodigoValidoEhConsumidoESaiDaLista(): void
    {
        $codigos = ['ABCD-2345', 'EFGH-6789', 'JKMN-3456'];
        $hashes = array_map('usuario_recovery_code_hash', $codigos);

        $resultado = usuario_recovery_code_consumir('EFGH-6789', $hashes);

        self::assertTrue($resultado['valido']);
        self::assertCount(2, $resultado['restantes']);
        self::assertNotContains(usuario_recovery_code_hash('EFGH-6789'), $resultado['restantes']);
    }

    public function testOMesmoCodigoNaoFuncionaDuasVezes(): void
    {
        // É a propriedade que define "código de recuperação": um que sobrevive ao uso é uma
        // segunda senha permanente.
        $hashes = array_map('usuario_recovery_code_hash', ['ABCD-2345', 'EFGH-6789']);

        $primeiro = usuario_recovery_code_consumir('ABCD-2345', $hashes);
        self::assertTrue($primeiro['valido']);

        $segundo = usuario_recovery_code_consumir('ABCD-2345', $primeiro['restantes']);
        self::assertFalse($segundo['valido']);
    }

    public function testConsumoAceitaOCodigoDigitadoSemHifenEEmMinusculas(): void
    {
        $hashes = array_map('usuario_recovery_code_hash', ['ABCD-2345']);

        self::assertTrue(usuario_recovery_code_consumir('abcd2345', $hashes)['valido']);
    }

    public function testCodigoInvalidoNaoConsomeNenhumaChance(): void
    {
        $hashes = array_map('usuario_recovery_code_hash', ['ABCD-2345', 'EFGH-6789']);

        $resultado = usuario_recovery_code_consumir('ZZZZ-9999', $hashes);

        self::assertFalse($resultado['valido']);
        self::assertCount(2, $resultado['restantes']);
    }

    public function testCodigoVazioNaoConsomeNadaENaoValida(): void
    {
        $hashes = array_map('usuario_recovery_code_hash', ['ABCD-2345']);

        foreach (['', '   ', '----'] as $entrada) {
            $resultado = usuario_recovery_code_consumir($entrada, $hashes);

            self::assertFalse($resultado['valido'], "Entrada: '{$entrada}'");
            self::assertCount(1, $resultado['restantes']);
        }
    }

    public function testListaVaziaNaoValidaNada(): void
    {
        $resultado = usuario_recovery_code_consumir('ABCD-2345', []);

        self::assertFalse($resultado['valido']);
        self::assertSame([], $resultado['restantes']);
    }

    public function testEntradasSujasNaListaDeHashesSaoDescartadas(): void
    {
        $resultado = usuario_recovery_code_consumir('ABCD-2345', ['', null, usuario_recovery_code_hash('ABCD-2345')]);

        self::assertTrue($resultado['valido']);
        self::assertSame([], $resultado['restantes']);
    }

    public function testDuplicataNaListaConsomeApenasUmaOcorrencia(): void
    {
        // Regravar a lista inteira zerada por um código repetido gastaria chances que o usuário
        // ainda tinha.
        $hash = usuario_recovery_code_hash('ABCD-2345');
        $resultado = usuario_recovery_code_consumir('ABCD-2345', [$hash, $hash]);

        self::assertTrue($resultado['valido']);
        self::assertCount(1, $resultado['restantes']);
    }
}
