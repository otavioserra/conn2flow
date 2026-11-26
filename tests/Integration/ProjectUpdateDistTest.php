<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ProjectUpdateDistTest extends TestCase
{
    public function testDeployMultipartEFinalizacaoDoCorePublicamDistViaHttp(): void
    {
        $raiz = sys_get_temp_dir() . '/c2f-http-dist-' . bin2hex(random_bytes(8));
        mkdir($raiz . '/gestor/assets', 0755, true);
        mkdir($raiz . '/gestor/controladores/atualizacoes', 0755, true);
        mkdir($raiz . '/gestor/bibliotecas');
        mkdir($raiz . '/public_html');
        file_put_contents($raiz . '/gestor/assets/core.js', 'core instalado');
        file_put_contents($raiz . '/gestor/controladores/atualizacoes/atualizacoes-banco-de-dados.php', '<?php');
        $biblioteca = CONN2FLOW_GESTOR_ROOT . '/bibliotecas/dist-publicacao.php';
        file_put_contents($raiz . '/gestor/bibliotecas/dist-publicacao.php', '<?php require_once ' . var_export($biblioteca, true) . ';');
        $processo = null;
        try {
            $socket = stream_socket_server('tcp://127.0.0.1:0');
            self::assertIsResource($socket);
            $endereco = stream_socket_get_name($socket, false);
            fclose($socket);
            $log = $raiz . '/http.log';
            $processo = proc_open([
                PHP_BINARY, '-d', 'disable_functions=exec,shell_exec,system,passthru,popen,proc_open',
                '-S', $endereco, CONN2FLOW_ROOT . '/tests/Fixtures/project-update-dist-router.php',
            ], [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $raiz);
            self::assertIsResource($processo);
            fclose($pipes[0]);
            $pronto = false;
            for ($tentativa = 0; $tentativa < 100 && proc_get_status($processo)['running']; $tentativa++) {
                $conexao = @stream_socket_client('tcp://' . $endereco, $errno, $erro, 0.05);
                if ($conexao) {
                    fclose($conexao);
                    $pronto = true;
                    break;
                }
                usleep(20_000);
            }
            self::assertTrue($pronto, (string)file_get_contents($log));

            $dist = $raiz . '/public_html/dist';
            self::assertDirectoryDoesNotExist($dist);
            $url = 'http://' . $endereco . '/_api/project/update';
            $zip = $raiz . '/projeto.zip';
            $primeira = $this->enviarProjeto($url, $zip, ['assets/projeto.js' => 'primeira']);
            self::assertTrue($primeira['criado']);
            self::assertSame(2, $primeira['publicados']);
            self::assertSame(0, $primeira['falhas']);
            self::assertSame('core instalado', file_get_contents($dist . '/core.js'));
            self::assertSame('primeira', file_get_contents($dist . '/projeto.js'));

            $segunda = $this->enviarProjeto($url, $zip, [
                'assets/projeto.js' => 'segunda',
                'modulos/projeto/projeto.js' => 'novo',
                'modulos/projeto/projeto.min.js' => 'min',
            ]);
            self::assertFalse($segunda['criado']);
            self::assertSame(1, $segunda['publicados']);
            self::assertSame(1, $segunda['atualizados']);
            self::assertSame(1, $segunda['inalterados']);
            self::assertSame(0, $segunda['falhas']);
            self::assertSame('segunda', file_get_contents($dist . '/projeto.js'));
            self::assertSame('min', file_get_contents($dist . '/projeto/js.js'));
            $manifesto = json_decode(file_get_contents($dist . '/.manifest.json'), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame(sha1('segunda'), $manifesto['arquivos']['projeto.js']['sha1']);
            self::assertSame(sha1('min'), $manifesto['arquivos']['projeto/js.js']['sha1']);

            $distCore = $raiz . '/public_core/dist';
            self::assertDirectoryDoesNotExist($distCore);
            $http = curl_init('http://' . $endereco . '/fixture/core-finalize');
            curl_setopt_array($http, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_PROXY => '']);
            $corpo = curl_exec($http);
            self::assertSame(200, curl_getinfo($http, CURLINFO_RESPONSE_CODE), (string)$corpo . curl_error($http));
            self::assertTrue(json_decode($corpo, true, 512, JSON_THROW_ON_ERROR)['finished']);
            self::assertFileExists($distCore . '/.manifest.json');
            self::assertSame('core instalado', file_get_contents($distCore . '/core.js'));
        } finally {
            if (is_resource($processo)) {
                proc_terminate($processo);
                proc_close($processo);
            }
            $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($arquivos as $arquivo) {
                $arquivo->isDir() ? rmdir($arquivo->getPathname()) : unlink($arquivo->getPathname());
            }
            rmdir($raiz);
        }
    }

    private function enviarProjeto(string $url, string $caminho, array $arquivos): array
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($caminho, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach ($arquivos as $nome => $conteudo) self::assertTrue($zip->addFromString($nome, $conteudo));
        self::assertTrue($zip->close());

        $http = curl_init($url);
        curl_setopt_array($http, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer fixture-deploy', 'X-Project-ID: fixture'],
            CURLOPT_POSTFIELDS => ['project_zip' => new CURLFile($caminho), 'no_health' => '1'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_PROXY => '',
        ]);
        $corpo = curl_exec($http);
        self::assertSame(200, curl_getinfo($http, CURLINFO_RESPONSE_CODE), (string)$corpo . curl_error($http));
        $resposta = json_decode($corpo, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('success', $resposta['status']);
        return $resposta['data']['installation']['dist'];
    }
}
