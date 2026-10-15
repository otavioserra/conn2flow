<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-279 (BATCH-288) — a sessão de banco da atualização não cai com `wait_timeout` curto no servidor
 * (temp.conn2flow.com, 15/10: 10 s no MariaDB de produção → "MySQL server has gone away" na sincronização).
 * A instrução foi conferida contra o MariaDB 11.8 do Lab: com ela a sessão sobrevive a 4 s parada com
 * `wait_timeout = 2`; sem ela, cai com o erro 2006.
 */
final class AtualizadorWaitTimeoutReq279Test extends TestCase
{
    private static function fonte(string $relativo): string
    {
        return (string)file_get_contents(CONN2FLOW_ROOT . '/' . $relativo);
    }

    public function testConexaoDaSincronizacaoPedeWaitTimeoutLongoSoNaSessao(): void
    {
        $f = self::fonte('gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php');

        self::assertStringContainsString("define('ATUALIZACOES_DB_WAIT_TIMEOUT', 28800)", $f);
        $conexao = strpos($f, '$pdo = new PDO($dsn, $user, $pass');
        $ajuste = strpos($f, 'SET SESSION wait_timeout = GREATEST(@@SESSION.wait_timeout, ".ATUALIZACOES_DB_WAIT_TIMEOUT.")');
        self::assertNotFalse($ajuste);
        self::assertGreaterThan($conexao, $ajuste, 'ajuste logo depois de abrir a conexão');
        self::assertLessThan(strpos($f, 'return $pdo;', $conexao), $ajuste);
        self::assertStringNotContainsString('SET GLOBAL wait_timeout', $f, 'nunca muda o valor do servidor');
    }

    public function testEtapasWebAjustamAConexaoDoGestor(): void
    {
        $f = self::fonte('gestor/controladores/atualizacoes/atualizacoes-sistema.php');
        $inicio = strpos($f, 'function atualizacoes_web_recursos(): void {');
        $corpo = substr($f, $inicio, 900);

        self::assertStringContainsString("banco_query('SET SESSION wait_timeout = GREATEST(@@SESSION.wait_timeout, '", $corpo);
        self::assertStringContainsString("case 'db': atualizacoes_web_recursos();", $f);
    }

    public function testInstrucaoSemPdoNaoQuebraSemPermissao(): void
    {
        $f = self::fonte('gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php');
        self::assertMatchesRegularExpression('/try \{ \$pdo->exec\("SET SESSION wait_timeout[^}]+\}\s*catch \(Throwable \$e\)/', $f);
    }
}
