<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-144 (req-141 / CR-002) — invalidação da procedência na atualização de sistema.
 *
 * O caso relatado no `perfil-usuario`: um recurso com `user_modified=1` tem a AUTORIA preservada
 * (o `html` do usuário fica, e o do sistema vai para a coluna espelho), mas o `css_precompiled` do
 * sistema ENTRA, porque não é campo preservado. O registro fica com HTML de uma origem e CSS
 * derivado de outra — estado que ninguém compilou e que era servido sem erro nenhum.
 *
 * A política não apaga CSS: marca a procedência como desconhecida, para o recurso aparecer como
 * stale na auditoria e na regeneração, mantendo o estilo atual enquanto isso.
 */
final class CssProcedenciaPoliticaTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }

        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . 'controladores' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR
            . 'atualizacoes-banco-de-dados.php';
    }

    /** @return array<string,bool> */
    private static function colunas(bool $comHash = true): array
    {
        $cols = ['id' => true, 'html' => true, 'css' => true, 'css_precompiled' => true,
                 'css_compiled' => true, 'html_updated' => true, 'user_modified' => true];
        if ($comHash) {
            $cols['css_source_hash'] = true;
        }
        return $cols;
    }

    private static function existente(?string $hash = 'v1:abc'): array
    {
        return ['id' => 'pagina-x', 'html' => 'HTML DO USUARIO', 'css_source_hash' => $hash];
    }

    public function testCssPrecompiledDoSistemaInvalidaAProcedencia(): void
    {
        // O caso `perfil-usuario`: autoria preservada, derivado do sistema entrando.
        $diff = ['css_precompiled' => 'CSS NOVO DO SISTEMA'];
        aplicarPoliticaProcedenciaCss($diff, self::existente(), self::colunas());

        self::assertArrayHasKey('css_source_hash', $diff);
        self::assertNull($diff['css_source_hash']);
    }

    public function testAutoriaPreservadaComDivergenciaInvalidaPelaColunaEspelho(): void
    {
        // `html` saiu do diff (preservado) e a versão do sistema foi para `html_updated`: é o sinal
        // de que existem DUAS versões e o derivado atual não corresponde mais ao par.
        $diff = ['html_updated' => 'HTML NOVO DO SISTEMA', 'system_updated' => 1];
        aplicarPoliticaProcedenciaCss($diff, self::existente(), self::colunas());

        self::assertNull($diff['css_source_hash']);
    }

    public function testAlteracaoDeAutoriaInvalida(): void
    {
        foreach (['html', 'css', 'css_compiled'] as $campo) {
            $diff = [$campo => 'valor novo'];
            aplicarPoliticaProcedenciaCss($diff, self::existente(), self::colunas());
            self::assertNull($diff['css_source_hash'], 'campo ' . $campo);
        }
    }

    public function testAtualizacaoQueNaoTocaEmHtmlNemCssNaoInvalida(): void
    {
        // Mudar `nome` ou `status` não altera a relação entre autoria e derivado.
        $diff = ['nome' => 'Outro nome', 'status' => 'A'];
        aplicarPoliticaProcedenciaCss($diff, self::existente(), self::colunas());

        self::assertArrayNotHasKey('css_source_hash', $diff);
    }

    public function testNaoSobrescreveAProcedenciaGravadaNaMesmaOperacao(): void
    {
        // Quem gerou o CSS já carimbou; invalidar aqui apagaria uma assinatura válida.
        $diff = ['html' => 'novo', 'css_source_hash' => 'v1:recem-assinado'];
        aplicarPoliticaProcedenciaCss($diff, self::existente(), self::colunas());

        self::assertSame('v1:recem-assinado', $diff['css_source_hash']);
    }

    public function testRecursoSemAssinaturaNaoRecebeInvalidacaoRedundante(): void
    {
        // Já conta como stale: gravar NULL sobre NULL só engordaria o UPDATE.
        $diff = ['css_precompiled' => 'novo'];
        aplicarPoliticaProcedenciaCss($diff, self::existente(null), self::colunas());

        self::assertArrayNotHasKey('css_source_hash', $diff);
    }

    public function testTabelaSemAColunaSegueExatamenteComoAntes(): void
    {
        // Acervo anterior à migração: projetar a coluna daria "Unknown column" no UPDATE.
        $diff = ['css_precompiled' => 'novo'];
        aplicarPoliticaProcedenciaCss($diff, self::existente(), self::colunas(false));

        self::assertArrayNotHasKey('css_source_hash', $diff);
        self::assertSame(['css_precompiled' => 'novo'], $diff);
    }

    public function testSchemaDesconhecidoUsaALinhaComoReferencia(): void
    {
        // Sem `SHOW COLUMNS`, a existência da chave na linha atual é o que resta para decidir.
        $diff = ['css_precompiled' => 'novo'];
        aplicarPoliticaProcedenciaCss($diff, ['id' => 'x'], null);
        self::assertArrayNotHasKey('css_source_hash', $diff);

        $diff2 = ['css_precompiled' => 'novo'];
        aplicarPoliticaProcedenciaCss($diff2, self::existente(), null);
        self::assertNull($diff2['css_source_hash']);
    }

    public function testDiffVazioPermaneceVazio(): void
    {
        // Um diff vazio não gera UPDATE; introduzir a coluna aqui criaria escrita do nada.
        $diff = [];
        aplicarPoliticaProcedenciaCss($diff, self::existente(), self::colunas());

        self::assertSame([], $diff);
    }
}
