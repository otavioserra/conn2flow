<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-286: choques superados saem da fila sozinhos e a decisão em lote passa cada choque pelo mesmo motor da
 * decisão individual. A biblioteca roda num processo à parte (tests/Fixtures/ChoquesLoteHarness.php), com o banco
 * simulado em SQLite.
 */
final class ChoquesLoteReq286Test extends TestCase
{
    private static array $r;

    public static function setUpBeforeClass(): void
    {
        $saida = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(CONN2FLOW_ROOT . '/tests/Fixtures/ChoquesLoteHarness.php'));
        self::$r = json_decode((string)$saida, true) ?: ['_saida' => (string)$saida];
    }

    public function testArnesSemErros(): void
    {
        $this->assertArrayHasKey('final', self::$r, json_encode(self::$r));
        $this->assertSame([], self::$r['erros']);
    }

    public function testSuperadosSaemSozinhos(): void
    {
        $this->assertSame(3, self::$r['superados']);
        $this->assertSame('superado', self::$r['apos_superados']['a.php'], 'Disco já igual ao novo.');
        $this->assertSame('superado', self::$r['apos_superados']['c.php'], 'Choque antigo; disco igual à última entrega da camada.');
        $this->assertSame('superado', self::$r['apos_superados']['d.php'], 'Retirada cujo arquivo já não existe.');
        $this->assertSame('', self::$r['apos_superados']['b.php'], 'Editado de verdade continua pendente.');
        $this->assertSame('', self::$r['apos_superados']['e.php'], 'Sobreposto não é superado por ser diferente.');
        $this->assertSame('', self::$r['apos_superados']['db:paginas?id=home&language=pt-br'], 'Registro não entra.');
    }

    public function testEntregasPendentesParaOLote(): void
    {
        $this->assertSame([['versao' => 'p9', 'origem' => 'db-sync', 'camada' => 'projeto', 'total' => 1], ['versao' => 'v3', 'origem' => 'update-system', 'camada' => 'core', 'total' => 3]], self::$r['entregas']);
    }

    public function testLoteSoAceitaManterOuSobrescrever(): void
    {
        $this->assertFalse(self::$r['lote_invalido']['ok']);
    }

    public function testLotePassaPeloMotorDeCadaChoque(): void
    {
        $l = self::$r['lote_core'];
        $this->assertTrue($l['ok']);
        $this->assertSame(1, $l['resolvidos'], 'vendor/x.php recebe a versão guardada.');
        $this->assertSame(1, $l['pulados'], 'Sobreposto não aceita sobrescrever.');
        $this->assertCount(1, $l['falhas'], 'Sem cópia guardada, falha e fica pendente.');
        $this->assertSame('v-2', self::$r['disco_vendor']);
        $this->assertSame(3, self::$r['lote_resto']['resolvidos']);
        $this->assertSame(['a.php' => 'superado', 'b.php' => 'manter', 'c.php' => 'superado', 'd.php' => 'superado', 'e.php' => 'manter',
            'vendor/x.php' => 'sobrescrever', 'db:paginas?id=home&language=pt-br' => 'manter'], self::$r['final']);
        $this->assertSame('A', self::$r['pagina_status'], 'Manter um registro não mexe nele.');
    }

    public function testTelaTemFiltroPaginacaoELote(): void
    {
        $php = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/admin-atualizacoes/admin-atualizacoes.php');
        $this->assertStringNotContainsString('ORDER BY id_atualizacoes_choques DESC LIMIT 50', $php, 'Sem o corte fixo em 50.');
        $this->assertStringContainsString("case 'choques-resolver-lote': admin_atualizacoes_ajax_choques_resolver_lote(); break;", $php);
        $this->assertStringContainsString('data-choques-paginacao', $php);
        $this->assertStringContainsString('data-choque-sem-conteudo', $php);
        $js = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/admin-atualizacoes/admin-atualizacoes.js');
        $this->assertStringContainsString("ajaxOpcao: 'choques-resolver-lote'", $js);
        $this->assertStringContainsString('window.c2fControles.dialogo.confirmar(pergunta', $js, 'Lote sempre com confirmação (DEC-136).');
        $json = json_decode((string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/admin-atualizacoes/admin-atualizacoes.json'), true);
        foreach (['pt-br', 'en'] as $idioma) {
            $ids = array_column($json['resources'][$idioma]['variables'] ?? [], 'id');
            foreach (['updates-clash-res-superado', 'updates-clash-res-automatico', 'updates-clash-bulk-keep', 'updates-clash-bulk-overwrite',
                'updates-clash-filter-pending', 'updates-clash-page-next', 'updates-clash-no-content-retirada', 'updates-clash-retirado'] as $id) {
                $this->assertContains($id, $ids, "$id em $idioma");
            }
        }
    }
}
