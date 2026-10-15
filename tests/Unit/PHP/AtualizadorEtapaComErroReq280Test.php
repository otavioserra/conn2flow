<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-280 (BATCH-289) — etapa com erro comum (não fatal) não deixa a trava presa: a tela cancela a sessão.
 * temp.conn2flow.com, 15/10 16:44: "Erro banco" (wait_timeout) e a tela parou sem finalize/cancel; a trava
 * ficou até ser removida à mão. A detecção de dono morto por `posix_kill` está no AtualizadorHotfixReq277Test.
 */
final class AtualizadorEtapaComErroReq280Test extends TestCase
{
    public function testEtapaComErroCancelaASessao(): void
    {
        $js = (string)file_get_contents(CONN2FLOW_ROOT . '/gestor/modulos/admin-atualizacoes/admin-atualizacoes.js');

        self::assertStringContainsString("log('Erro deploy: ' + (resp.erro || '')); cancelar(); return;", $js);
        self::assertStringContainsString("log('Erro deploy: ' + data.error); cancelar(); return;", $js);
        self::assertStringContainsString("log('Falha deploy'); cancelar();", $js);
        self::assertStringContainsString("log('Erro banco: ' + (resp.erro || '')); cancelar(); return;", $js);
        self::assertStringContainsString("log('Falha banco'); cancelar();", $js);
        self::assertStringContainsString("log('Cancelado.'); currentSid = null;", $js, 'cancelada, a tela pode iniciar de novo');
    }

    public function testCancelarLiberaATravaNoServidor(): void
    {
        $f = (string)file_get_contents(CONN2FLOW_ROOT . '/gestor/controladores/atualizacoes/atualizacoes-sistema.php');
        self::assertStringContainsString("case 'cancel': \$r = webCancel(\$_REQUEST['sid']??''); webLiberarTrava(\$_REQUEST['sid']??'');", $f);
    }

    public function testDonoMortoUsaPosixAntesDoProc(): void
    {
        $f = (string)file_get_contents(CONN2FLOW_ROOT . '/gestor/bibliotecas/deploy-lock.php');
        self::assertLessThan(strpos($f, "if (!@is_dir(\$proc . '/self')) return false;"), strpos($f, '$sinal = $sinal ?? deploy_lock_sinal_padrao();'));
        self::assertStringContainsString('posix_get_last_error() === 3', $f);
    }
}
