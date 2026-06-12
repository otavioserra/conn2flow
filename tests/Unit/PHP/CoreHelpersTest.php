<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CoreHelpersTest extends TestCase
{
    public function testExisteDiferenciaValoresVaziosDeValoresPreenchidos(): void
    {
        self::assertFalse(existe(''));
        self::assertFalse(existe([]));
        self::assertFalse(existe(0));

        self::assertTrue(existe('0'));
        self::assertTrue(existe(['item']));
        self::assertTrue(existe(1));
    }

    public function testQueryStringRemoveERecuperaVariaveis(): void
    {
        $query = 'pagina=2&busca=Conn2Flow&token=abc';

        self::assertSame('Conn2Flow', gestor_querystring_variavel($query, 'busca'));
        self::assertSame('', gestor_querystring_variavel($query, 'inexistente'));
        self::assertSame('pagina=2&busca=Conn2Flow', gestor_querystring_remover_variavel($query, 'token'));
    }

    public function testCriptografiaBasicaComChavesRsa(): void
    {
        if (!extension_loaded('openssl')) {
            self::markTestSkipped('A extensao OpenSSL nao esta habilitada.');
        }

        $chaves = autenticacao_openssl_gerar_chaves(['tipo' => 'RSA', 'senha' => 'conn2flow-tests']);

        self::assertIsArray($chaves);
        self::assertArrayHasKey('publica', $chaves);
        self::assertArrayHasKey('privada', $chaves);

        $original = 'payload seguro de teste';
        $criptografia = autenticacao_encriptar_chave_publica([
            'valor' => $original,
            'chavePublica' => $chaves['publica'],
        ]);

        self::assertIsString($criptografia);
        self::assertNotSame($original, $criptografia);

        $decriptado = autenticacao_decriptar_chave_privada([
            'criptografia' => $criptografia,
            'chavePrivada' => $chaves['privada'],
            'chavePrivadaSenha' => 'conn2flow-tests',
        ]);

        self::assertSame($original, $decriptado);
    }
}
