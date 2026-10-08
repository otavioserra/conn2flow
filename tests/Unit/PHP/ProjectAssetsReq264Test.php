<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Conn2Flow\Cli\Commands\AssetsPublishCommand;
use Conn2Flow\Cli\Console\Input;
use Conn2Flow\Cli\Contracts\OutputInterface;

foreach (['Contracts/CommandInterface', 'Contracts/InputInterface', 'Contracts/OutputInterface', 'Console/Input', 'Support/ProjectEnvironmentResolver', 'Support/SshRemoteTransport', 'Commands/AssetsPublishCommand'] as $file) {
    require_once CONN2FLOW_ROOT . '/cli/src/' . $file . '.php';
}
require_once CONN2FLOW_ROOT . '/gestor/bibliotecas/recursos.php';

final class ProjectAssetsReq264Test extends TestCase
{
    public function testProjectOverlayPublishesOwnAssetsWithoutExposingPhp(): void
    {
        $root = sys_get_temp_dir() . '/c2f-assets-req264-' . bin2hex(random_bytes(8));
        foreach (['gestor/assets/images', 'project/assets/images', 'project/modulos/subscriptions', 'dev-environment/data'] as $dir) mkdir($root . '/' . $dir, 0775, true);
        try {
            file_put_contents($root . '/gestor/assets/images/logo.png', 'core');
            file_put_contents($root . '/project/assets/images/logo.png', 'project');
            file_put_contents($root . '/project/modulos/subscriptions/subscriptions.js', 'authored');
            file_put_contents($root . '/project/modulos/subscriptions/subscriptions.min.js', 'minified');
            file_put_contents($root . '/project/modulos/subscriptions/secret.php', '<?php secret();');
            file_put_contents($root . '/dev-environment/data/environment.json', json_encode(['devProjects' => ['fixture' => [
                'path' => $root . '/project', 'deploy_mode' => 'ssh', 'ssh_host' => '192.0.2.10', 'ssh_user' => 'deploy',
                'ssh_target_path' => '/home/tenant/gestor', 'ssh_public_path' => '/home/tenant/public_html',
            ]]]));
            $output = $this->createMock(OutputInterface::class);
            $status = (new AssetsPublishCommand($root))->execute(new Input(['c2f', 'assets:publish', '--project=fixture', '--simular-remoto']), $output);
            self::assertSame(0, $status);
            $dist = $root . '/temp/assets-publish/fixture/dist/';
            self::assertSame('project', file_get_contents($dist . 'images/logo.png'));
            self::assertSame('minified', file_get_contents($dist . 'subscriptions/js.js'));
            self::assertFileDoesNotExist($dist . 'subscriptions/secret.php');
            self::assertFileDoesNotExist($dist . 'subscriptions/subscriptions.min.js');
            $manifest = json_decode(file_get_contents($dist . '.manifest.json'), true);
            self::assertSame('assets/images/logo.png', $manifest['arquivos']['images/logo.png']['fonte']);
        } finally {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            rmdir($root);
        }
    }
}
