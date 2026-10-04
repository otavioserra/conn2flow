<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-219: o pipeline minificava o JavaScript DEPOIS de copiar core e arquivos. O destino ficava com o
 * `.min.js` antigo e o `asset-versions.json` novo; o navegador guardava o arquivo velho sob a URL da
 * versão nova e só um Ctrl+F5 resolvia. A minificação tem de vir antes de qualquer cópia.
 */
final class PipelineMinificaAntesReq219Test extends TestCase
{
    private static function fonte(string $comando): string
    {
        return (string)file_get_contents(dirname(__DIR__, 3) . '/cli/src/Commands/' . $comando . '.php');
    }

    public function testProjectUpdateAllMinificaAntesDeCopiar(): void
    {
        $s = self::fonte('ProjectUpdateAllCommand');
        $minifica = strpos($s, 'new AssetsMinifyCommand(');
        self::assertNotFalse($minifica);
        self::assertSame(1, substr_count($s, 'new AssetsMinifyCommand('), 'uma minificação só');
        foreach (['new ProjectSyncCoreCommand(', 'new ProjectSyncResourcesCommand(', 'new ProjectSyncFilesCommand(', 'new AssetsPublishCommand('] as $etapa) {
            self::assertGreaterThan($minifica, strpos($s, $etapa), $etapa . ' depois da minificação');
        }
    }

    public function testManagerUpdateAllMinificaAntesDeCopiar(): void
    {
        $s = self::fonte('ManagerUpdateAllCommand');
        $minifica = strpos($s, 'new AssetsMinifyCommand(');
        self::assertNotFalse($minifica);
        self::assertSame(1, substr_count($s, 'new AssetsMinifyCommand('));
        foreach (['new ResourcesSyncCommand(', 'new ManagerSyncFilesCommand(', 'new AssetsPublishCommand('] as $etapa) {
            self::assertGreaterThan($minifica, strpos($s, $etapa), $etapa . ' depois da minificação');
        }
    }
}
