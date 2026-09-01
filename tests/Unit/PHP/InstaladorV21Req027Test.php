<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'gestor-instalador' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'InstallerGuard.php';
require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'gestor-instalador' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Installer.php';

/**
 * REQ-027 / BATCH-022: instalador v2.1.0 com probe de rewrite no health check,
 * snippet Nginx copy-paste, `nginx-vhost.conf.sample` e runner headless robusto.
 */
final class InstaladorV21Req027Test extends TestCase
{
    /** @var string[] */
    private array $diretoriosTemporarios = [];

    protected function tearDown(): void
    {
        foreach ($this->diretoriosTemporarios as $diretorio) {
            $this->removerDiretorio($diretorio);
        }
        $this->diretoriosTemporarios = [];
    }

    private function criarDiretorioTemporario(string $prefixo): string
    {
        $caminho = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefixo . bin2hex(random_bytes(6));
        mkdir($caminho, 0777, true);
        $this->diretoriosTemporarios[] = $caminho;

        return $caminho;
    }

    private function removerDiretorio(string $diretorio): void
    {
        if (!is_dir($diretorio)) return;
        $itens = scandir($diretorio) ?: [];
        foreach ($itens as $item) {
            if ($item === '.' || $item === '..') continue;
            $alvo = $diretorio . DIRECTORY_SEPARATOR . $item;
            is_dir($alvo) ? $this->removerDiretorio($alvo) : @unlink($alvo);
        }
        @rmdir($diretorio);
    }

    /** Instância isolada: sem construtor não há escrita de log no repositório. */
    private function novoInstalador(array $data, string $baseDir, string $webServer = 'apache'): Installer
    {
        $reflexao = new ReflectionClass(Installer::class);
        $installer = $reflexao->newInstanceWithoutConstructor();

        foreach ([
            'data' => $data,
            'baseDir' => $baseDir,
            'tempDir' => $baseDir . DIRECTORY_SEPARATOR . 'temp',
            'logFile' => $baseDir . DIRECTORY_SEPARATOR . 'installer.log',
            'webServer' => $webServer,
            'avisos' => [],
        ] as $propriedade => $valor) {
            $campo = $reflexao->getProperty($propriedade);
            $campo->setAccessible(true);
            $campo->setValue($installer, $valor);
        }

        return $installer;
    }

    private function invocar(Installer $installer, string $metodo, array $argumentos = [])
    {
        $reflexao = new ReflectionMethod(Installer::class, $metodo);
        $reflexao->setAccessible(true);

        return $reflexao->invokeArgs($installer, $argumentos);
    }

    /** Copia o modelo real de `public-access/.htaccess` para o diretório de teste. */
    private function prepararPublicAccess(string $baseDir): void
    {
        $origem = CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'gestor-instalador'
            . DIRECTORY_SEPARATOR . 'public-access' . DIRECTORY_SEPARATOR . '.htaccess';
        $destino = $baseDir . DIRECTORY_SEPARATOR . 'public-access';
        mkdir($destino, 0777, true);
        copy($origem, $destino . DIRECTORY_SEPARATOR . '.htaccess');
    }

    public function testInstaladorAnunciaVersaoDoisPontoUm(): void
    {
        self::assertSame('2.1.0', InstallerGuard::VERSION);
        self::assertSame('api/rewrite-probe', InstallerGuard::API_REWRITE_PROBE);
        self::assertSame('nginx-vhost.conf.sample', InstallerGuard::NGINX_SAMPLE_FILE);
    }

    public function testRotaDaApiEResolvidaComOuSemRewriteAtivo(): void
    {
        // Com rewrite ativo a rota canônica chega como `_gestor-caminho`.
        self::assertSame(
            InstallerGuard::API_REWRITE_PROBE,
            InstallerGuard::resolveApiRoute(['_gestor-caminho' => 'api/rewrite-probe'])
        );
        // Instalação em subpasta: o prefixo não pode impedir o reconhecimento.
        self::assertSame(
            InstallerGuard::API_REWRITE_PROBE,
            InstallerGuard::resolveApiRoute(['_gestor-caminho' => '/instalador/api/rewrite-probe/'])
        );
        // Sem rewrite — o caso que a sonda diagnostica — vale o parâmetro determinístico.
        self::assertSame(
            InstallerGuard::API_REWRITE_PROBE,
            InstallerGuard::resolveApiRoute(['api' => 'rewrite-probe'])
        );
        self::assertSame(
            InstallerGuard::API_REWRITE_PROBE,
            InstallerGuard::resolveApiRoute([], ['PATH_INFO' => '/api/rewrite-probe'])
        );

        // Nada mais pode ser confundido com a rota da API.
        self::assertSame('', InstallerGuard::resolveApiRoute([]));
        self::assertSame('', InstallerGuard::resolveApiRoute(['_gestor-caminho' => 'signin/']));
        self::assertSame('', InstallerGuard::resolveApiRoute(['api' => 'outra-coisa']));
        self::assertSame('', InstallerGuard::resolveApiRoute(['_gestor-caminho' => 'api/rewrite-probe-falso']));
        self::assertSame('', InstallerGuard::resolveApiRoute(['_gestor-caminho' => InstallerGuard::REWRITE_PROBE]));
    }

    public function testEndpointDaApiExigeTokenDoInstalador(): void
    {
        $index = (string) file_get_contents(
            CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'gestor-instalador' . DIRECTORY_SEPARATOR . 'index.php'
        );

        $posicaoTrava = strpos($index, 'InstallerGuard::lockAcquire');
        $posicaoApi = strpos($index, 'InstallerGuard::resolveApiRoute');
        self::assertIsInt($posicaoTrava);
        self::assertIsInt($posicaoApi);
        // A API só é atendida depois do desbloqueio por chave e da trava de concorrência.
        self::assertGreaterThan($posicaoTrava, $posicaoApi);

        $trecho = substr($index, $posicaoApi, 1200);
        self::assertStringContainsString('hash_equals($installToken, $apiToken)', $trecho);
        self::assertStringContainsString('403', $trecho);
    }

    public function testSnippetNginxDespachaFastcgiDentroDaNamedLocation(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-snippet-');
        $installer = $this->novoInstalador(['url_raiz' => '/'], $base, 'nginx');

        $snippet = (string) $this->invocar($installer, 'getNginxSnippet', ['/']);

        self::assertStringContainsString('location @c2f_rewrite {', $snippet);
        self::assertStringContainsString('rewrite ^/(.*)$ /index.php?_gestor-caminho=$1&$args? break;', $snippet);
        // Com `break` o processamento fica na mesma location: sem estas linhas o Nginx
        // devolveria o arquivo bruto em vez de executar o front-controller.
        $namedLocation = substr($snippet, (int) strpos($snippet, 'location @c2f_rewrite {'));
        self::assertStringContainsString('include /etc/nginx/fastcgi_params;', $namedLocation);
        self::assertStringContainsString('fastcgi_param SCRIPT_NAME /index.php;', $namedLocation);
        self::assertStringContainsString('fastcgi_param SCRIPT_FILENAME $document_root/index.php;', $namedLocation);
        self::assertStringContainsString('fastcgi_pass unix:', $namedLocation);
    }

    public function testSnippetNginxRespeitaInstalacaoEmSubpasta(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-subpasta-');
        $installer = $this->novoInstalador(['url_raiz' => '/gestor/'], $base, 'nginx');

        $snippet = (string) $this->invocar($installer, 'getNginxSnippet', ['/gestor/']);

        self::assertStringContainsString('location /gestor/ {', $snippet);
        self::assertStringContainsString('rewrite ^/gestor/(.*)$ /gestor/index.php?_gestor-caminho=$1&$args? break;', $snippet);
        self::assertStringContainsString('fastcgi_param SCRIPT_FILENAME $document_root/gestor/index.php;', $snippet);
    }

    public function testVhostSampleEnvolveOSnippetEmUmServerCompleto(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-vhost-');
        $installer = $this->novoInstalador([
            'url_raiz' => '/',
            'domain' => 'cliente.example.com',
            'document_root' => '/home/cliente/web/cliente.example.com/public_html',
        ], $base, 'nginx');

        $vhost = (string) $this->invocar($installer, 'getNginxVhostSample', ['/']);

        self::assertStringContainsString('server {', $vhost);
        self::assertStringContainsString('server_name cliente.example.com;', $vhost);
        self::assertStringContainsString('root /home/cliente/web/cliente.example.com/public_html;', $vhost);
        self::assertStringContainsString('location @c2f_rewrite {', $vhost);
        self::assertStringContainsString('_gestor-caminho=$1', $vhost);
        // Arquivos ocultos (.env, .htaccess) nunca podem ser servidos pela web.
        self::assertStringContainsString('deny all;', $vhost);
        // Chaves balanceadas: o arquivo precisa passar em `nginx -t` sem edição estrutural.
        self::assertSame(substr_count($vhost, '{'), substr_count($vhost, '}'));
    }

    public function testVhostSampleEGravadoNoDiretorioDeInstalacaoQuandoDisponivel(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-sample-base-');
        $installPath = $this->criarDiretorioTemporario('c2f-sample-destino-');

        $installer = $this->novoInstalador([
            'url_raiz' => '/',
            'domain' => 'lab.conn2flow.local',
            'install_path' => $installPath,
        ], $base, 'nginx');

        $arquivo = (string) $this->invocar($installer, 'writeNginxVhostSample', ['/']);

        self::assertSame($installPath . DIRECTORY_SEPARATOR . InstallerGuard::NGINX_SAMPLE_FILE, $arquivo);
        self::assertFileExists($arquivo);
        self::assertStringContainsString('server_name lab.conn2flow.local;', (string) file_get_contents($arquivo));
        // Sem `install_path` utilizável o arquivo fica na pasta do instalador.
        $semDestino = $this->novoInstalador(['url_raiz' => '/'], $base, 'nginx');
        $arquivoBase = (string) $this->invocar($semDestino, 'writeNginxVhostSample', ['/']);
        self::assertSame($base . DIRECTORY_SEPARATOR . InstallerGuard::NGINX_SAMPLE_FILE, $arquivoBase);
    }

    public function testApacheGanhaHtaccessProvisorioSemRedirectHttps(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-htaccess-');
        $this->prepararPublicAccess($base);

        $installer = $this->novoInstalador(['url_raiz' => '/'], $base, 'apache');
        $resultado = $this->invocar($installer, 'ensureApacheRewrite');

        self::assertSame('criado', $resultado['acao']);
        $conteudo = (string) file_get_contents($base . DIRECTORY_SEPARATOR . '.htaccess');
        self::assertStringContainsString('RewriteEngine On', $conteudo);
        self::assertStringContainsString('_gestor-caminho=$1', $conteudo);
        // O health check pode rodar em HTTP puro: um 301 para HTTPS mascararia a sonda.
        self::assertStringNotContainsString('https://%{HTTP_HOST}', $conteudo);

        // Segunda chamada não sobrescreve o arquivo já presente.
        $segundo = $this->invocar($installer, 'ensureApacheRewrite');
        self::assertSame('existente', $segundo['acao']);
    }

    public function testRelatorioDoProbeEmNginxEntregaSnippetSampleEUrlDaSonda(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-probe-nginx-');
        $installPath = $this->criarDiretorioTemporario('c2f-probe-destino-');

        $installer = $this->novoInstalador([
            'url_raiz' => '/',
            'install_path' => $installPath,
            // Sem domínio a sonda de servidor não dispara requisição de rede no teste.
            'domain' => '',
        ], $base, 'nginx');

        $relatorio = $installer->rewriteProbeReport();

        self::assertSame('success', $relatorio['status']);
        self::assertSame('nginx', $relatorio['web_server']);
        self::assertSame(InstallerGuard::VERSION, $relatorio['versao']);
        self::assertSame('/' . InstallerGuard::REWRITE_PROBE, $relatorio['probe_url']);
        self::assertSame(InstallerGuard::REWRITE_PROBE_OK, $relatorio['probe_expected']);
        self::assertSame(InstallerGuard::NGINX_SAMPLE_FILE, $relatorio['sample_file']);
        self::assertStringContainsString('location @c2f_rewrite', $relatorio['snippet']);
        self::assertNull($relatorio['rewrite_ok']);
        self::assertFileExists($installPath . DIRECTORY_SEPARATOR . InstallerGuard::NGINX_SAMPLE_FILE);
    }

    public function testRelatorioDoProbeAceitaVereditoObservadoPeloNavegador(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-probe-cliente-');

        $ok = $this->novoInstalador(['url_raiz' => '/', 'domain' => ''], $base, 'nginx');
        $relatorioOk = $ok->rewriteProbeReport(['rewrite_ok' => true]);
        self::assertTrue($relatorioOk['rewrite_ok']);
        self::assertSame('cliente', $relatorioOk['origem']);

        $falha = $this->novoInstalador(['url_raiz' => '/', 'domain' => ''], $base, 'nginx');
        $relatorioFalha = $falha->rewriteProbeReport(['rewrite_ok' => false]);
        self::assertFalse($relatorioFalha['rewrite_ok']);
        self::assertSame('cliente', $relatorioFalha['origem']);
        // O snippet copy-paste precisa estar disponível justamente no caso de falha.
        self::assertStringContainsString('rewrite ^/(.*)$', $relatorioFalha['snippet']);
    }

    public function testRelatorioDoProbeEmApacheGarantAHtaccessENaoEmiteSnippet(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-probe-apache-');
        $this->prepararPublicAccess($base);

        $installer = $this->novoInstalador(['url_raiz' => '/', 'domain' => ''], $base, 'apache');
        $relatorio = $installer->rewriteProbeReport(['rewrite_ok' => true]);

        self::assertSame('apache', $relatorio['web_server']);
        self::assertSame('criado', $relatorio['htaccess']['acao']);
        self::assertSame('', $relatorio['snippet']);
        self::assertSame('', $relatorio['sample_file']);
        self::assertFileExists($base . DIRECTORY_SEPARATOR . '.htaccess');
    }

    public function testLimpezaFinalRemoveOVhostSampleDaPastaPublica(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-limpeza-sample-');
        $sample = $base . DIRECTORY_SEPARATOR . InstallerGuard::NGINX_SAMPLE_FILE;
        file_put_contents($sample, 'server {}');
        file_put_contents($base . DIRECTORY_SEPARATOR . 'installer.log', 'log');

        $installer = $this->novoInstalador([], $base, 'nginx');
        $this->invocar($installer, 'cleanupInstallerFiles');

        // O exemplo útil ao operador é o gravado em `install_path`; a cópia pública sai.
        self::assertFileDoesNotExist($sample);
    }
}
