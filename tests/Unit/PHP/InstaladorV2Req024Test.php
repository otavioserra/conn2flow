<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'gestor-instalador' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'InstallerGuard.php';
require_once CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'gestor-instalador' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Installer.php';

/**
 * REQ-024 / BATCH-019: instalador v2.0.0 com suporte dual Nginx/Apache,
 * chave de segurança pré-instalação, trava de concorrência e URL_RAIZ determinístico.
 */
final class InstaladorV2Req024Test extends TestCase
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

    public function testInstaladorDeclaraVersaoDoisPontoZero(): void
    {
        $indexPath = CONN2FLOW_ROOT . DIRECTORY_SEPARATOR . 'gestor-instalador' . DIRECTORY_SEPARATOR . 'index.php';
        $conteudo = (string) file_get_contents($indexPath);

        self::assertMatchesRegularExpression("/\\\$_GESTOR_INSTALADOR\\['versao'\\]\s*=\s*'2\.0\.0'/", $conteudo);
    }

    public function testChaveDeSegurancaEGeradaComTrintaEDoisHexadecimais(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-key-');

        $chave = InstallerGuard::ensureKey($base);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $chave);
        self::assertFileExists(InstallerGuard::keyPath($base));

        // Uma segunda chamada não pode rotacionar a chave já entregue ao operador.
        self::assertSame($chave, InstallerGuard::ensureKey($base));

        self::assertTrue(InstallerGuard::validateKey($base, $chave));
        self::assertFalse(InstallerGuard::validateKey($base, strrev($chave)));
        self::assertFalse(InstallerGuard::validateKey($base, ''));

        InstallerGuard::removeKey($base);
        self::assertFileDoesNotExist(InstallerGuard::keyPath($base));
        self::assertFalse(InstallerGuard::validateKey($base, $chave));
    }

    public function testTravaRecusaSessaoDivergenteEAceitaTomadaAposTimeout(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-lock-');
        $lock = InstallerGuard::lockPath($base);

        self::assertTrue(InstallerGuard::lockAcquire($lock, 'sessao-legitima'));
        self::assertTrue(InstallerGuard::lockOwner($lock, 'sessao-legitima'));

        // Sessão concorrente é recusada enquanto a trava estiver ativa.
        self::assertFalse(InstallerGuard::lockAcquire($lock, 'sessao-intrusa'));
        self::assertFalse(InstallerGuard::lockOwner($lock, 'sessao-intrusa'));

        // A sessão dona renova a própria trava sem perdê-la.
        self::assertTrue(InstallerGuard::lockAcquire($lock, 'sessao-legitima'));
        self::assertTrue(InstallerGuard::lockTouch($lock, 'sessao-legitima'));
        self::assertFalse(InstallerGuard::lockTouch($lock, 'sessao-intrusa'));

        // Trava abandonada há mais de 30 minutos pode ser retomada.
        $expirado = time() - (InstallerGuard::LOCK_TIMEOUT + 60);
        file_put_contents($lock, json_encode([
            'token_hash' => hash('sha256', 'sessao-legitima'),
            'created_at' => $expirado,
            'updated_at' => $expirado,
        ]));
        self::assertTrue(InstallerGuard::lockAcquire($lock, 'sessao-nova'));
        self::assertTrue(InstallerGuard::lockOwner($lock, 'sessao-nova'));

        self::assertFalse(InstallerGuard::lockRelease($lock, 'sessao-intrusa'));
        self::assertTrue(InstallerGuard::lockRelease($lock, 'sessao-nova'));
        self::assertFileDoesNotExist($lock);
    }

    public function testDeteccaoENormalizacaoDoServidorWeb(): void
    {
        $original = $_SERVER['SERVER_SOFTWARE'] ?? null;

        try {
            $_SERVER['SERVER_SOFTWARE'] = 'nginx/1.24.0';
            self::assertSame('nginx', InstallerGuard::detectWebServer());

            $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.58 (Ubuntu)';
            self::assertSame('apache', InstallerGuard::detectWebServer());

            $_SERVER['SERVER_SOFTWARE'] = 'LiteSpeed';
            self::assertSame('apache', InstallerGuard::detectWebServer());

            unset($_SERVER['SERVER_SOFTWARE']);
            self::assertSame('', InstallerGuard::detectWebServer());
        } finally {
            if ($original === null) {
                unset($_SERVER['SERVER_SOFTWARE']);
            } else {
                $_SERVER['SERVER_SOFTWARE'] = $original;
            }
        }

        self::assertSame('nginx', InstallerGuard::normalizeWebServer('NGINX'));
        self::assertSame('apache', InstallerGuard::normalizeWebServer(' Apache '));
        self::assertSame('nginx', InstallerGuard::normalizeWebServer('', 'nginx'));
        self::assertSame('apache', InstallerGuard::normalizeWebServer('iis', 'lighttpd'));
    }

    public function testUrlRaizDoPayloadTemPrecedenciaSobreQualquerHeuristica(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-raiz-');

        $installer = $this->novoInstalador([
            'url_raiz' => '/',
            'document_root' => 'C:/caminho/que/nao/existe',
        ], $base);
        self::assertSame('/', $this->invocar($installer, 'detectUrlRaiz'));

        // Valor sem barras é normalizado para o formato canônico.
        $installer = $this->novoInstalador(['url_raiz' => 'gestor'], $base);
        self::assertSame('/gestor/', $this->invocar($installer, 'detectUrlRaiz'));
    }

    public function testUrlRaizUsaDocumentRootDoPayloadParaSubpasta(): void
    {
        $docRoot = $this->criarDiretorioTemporario('c2f-docroot-');
        $subpasta = $docRoot . DIRECTORY_SEPARATOR . 'instalador';
        mkdir($subpasta, 0777, true);

        $naRaiz = $this->novoInstalador(['document_root' => $docRoot], $docRoot);
        self::assertSame('/', $this->invocar($naRaiz, 'detectUrlRaiz'));

        $emSubpasta = $this->novoInstalador(['document_root' => $docRoot], $subpasta);
        self::assertSame('/instalador/', $this->invocar($emSubpasta, 'detectUrlRaiz'));
    }

    public function testUrlRaizEmCliNaoVazaCaminhoFisicoDoRunner(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-cli-');
        $documentRootOriginal = $_SERVER['DOCUMENT_ROOT'] ?? null;
        $scriptNameOriginal = $_SERVER['SCRIPT_NAME'] ?? null;

        try {
            unset($_SERVER['DOCUMENT_ROOT']);
            // Cenário do bug: o runner headless mora dentro da árvore do gestor.
            $_SERVER['SCRIPT_NAME'] = '/home/admin/web/site.local/conn2flow-gestor/modulos/host-manager/includes/orchestrator-headless-runner.php';

            $installer = $this->novoInstalador([], $base);
            self::assertSame('/', $this->invocar($installer, 'detectUrlRaiz'));
        } finally {
            if ($documentRootOriginal === null) {
                unset($_SERVER['DOCUMENT_ROOT']);
            } else {
                $_SERVER['DOCUMENT_ROOT'] = $documentRootOriginal;
            }
            if ($scriptNameOriginal === null) {
                unset($_SERVER['SCRIPT_NAME']);
            } else {
                $_SERVER['SCRIPT_NAME'] = $scriptNameOriginal;
            }
        }
    }

    public function testSnippetNginxInjetaGestorCaminhoSemDuplicarQueryString(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-nginx-');
        $installer = $this->novoInstalador(['url_raiz' => '/'], $base, 'nginx');

        $snippet = (string) $this->invocar($installer, 'getNginxSnippet', ['/']);

        self::assertStringContainsString('location @c2f_rewrite', $snippet);
        self::assertStringContainsString('_gestor-caminho=$1', $snippet);
        // A interrogação final evita que o Nginx reanexe os argumentos originais.
        self::assertStringContainsString('&$args? last;', $snippet);
        // Assets inexistentes em disco precisam alcançar o controlador arquivo-estatico.
        self::assertStringContainsString('try_files $uri @c2f_rewrite;', $snippet);
    }

    public function testChaveDeSegurancaCliAceitaSkipEBloqueiaChaveInvalida(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-guard-');
        $chave = InstallerGuard::ensureKey($base);

        $comSkip = $this->novoInstalador(['skip_security_key' => '1'], $base);
        $this->invocar($comSkip, 'assertSecurityKey');

        $comChave = $this->novoInstalador(['install_key' => $chave], $base);
        $this->invocar($comChave, 'assertSecurityKey');

        $semChave = $this->novoInstalador(['install_key' => 'chave-errada'], $base);
        $this->expectException(Exception::class);
        $this->invocar($semChave, 'assertSecurityKey');
    }

    public function testLimpezaFinalRemoveChaveTravaELogDoInstalador(): void
    {
        $base = $this->criarDiretorioTemporario('c2f-cleanup-');
        mkdir($base . DIRECTORY_SEPARATOR . 'src', 0777, true);
        mkdir($base . DIRECTORY_SEPARATOR . 'views', 0777, true);
        file_put_contents($base . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Installer.php', 'x');
        file_put_contents(InstallerGuard::keyPath($base), 'chave');
        file_put_contents(InstallerGuard::lockPath($base), '{}');
        file_put_contents($base . DIRECTORY_SEPARATOR . 'installer.log', 'log');
        file_put_contents($base . DIRECTORY_SEPARATOR . 'index.php', 'front-controller');

        $installer = $this->novoInstalador([], $base);
        $this->invocar($installer, 'cleanupInstallerFiles');

        self::assertFileDoesNotExist(InstallerGuard::keyPath($base));
        self::assertFileDoesNotExist(InstallerGuard::lockPath($base));
        self::assertDirectoryDoesNotExist($base . DIRECTORY_SEPARATOR . 'src');
        self::assertDirectoryDoesNotExist($base . DIRECTORY_SEPARATOR . 'views');
        // O front-controller gerado permanece: ele é o index.php final do site.
        self::assertFileExists($base . DIRECTORY_SEPARATOR . 'index.php');
    }
}
