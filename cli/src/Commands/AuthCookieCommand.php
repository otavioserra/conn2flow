<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

final class AuthCookieCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'auth:cookie';
    }

    public function getDescription(): string
    {
        return 'Generate authentication cookies (JWT + session) for automated access to authenticated routes.';
    }

    public function getAliases(): array
    {
        return ['auth:generate'];
    }

    public function getHelp(): string
    {
        return <<<HELP
Usage: c2f auth:cookie [--user=admin] [--project=ID] [--out=temp/agent-cookies.txt]

Generates a Netscape cookie jar file for use with curl -b or Playwright.
Also prints the Cookie header string to stdout.

Options:
  --user       User identifier (name or ID). Default: 'admin' (ID 1)
  --project    Project ID for URL resolution (optional)
  --out        Output cookie jar path. Default: temp/agent-cookies.txt
HELP;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Generate Authentication Cookies');

        $userIdent = $input->getOption('user', 'admin');
        $outPath = $input->getOption('out', 'temp/agent-cookies.txt');
        $projectId = $input->getOption('project');

        // Resolve absolute output path
        if (!str_starts_with($outPath, '/') && !str_starts_with($outPath, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:/', $outPath)) {
            $outPath = $this->rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $outPath);
        }

        // Ensure output directory exists
        $outDir = dirname($outPath);
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        // Bootstrap Gestor environment
        $gestorPath = $this->rootPath . DIRECTORY_SEPARATOR . 'gestor';
        $configFile = $gestorPath . DIRECTORY_SEPARATOR . 'config.php';

        if (!is_file($configFile)) {
            $output->error("Gestor config.php not found at: {$configFile}");
            return 1;
        }

        // Set required server variables if not present
        $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
        $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Conn2Flow-CLI/1.0';

        // Set up the index path required by config.php
        global $_INDEX;
        $_INDEX = $_INDEX ?? [];
        $_INDEX['sistemas-dir'] = $gestorPath . '/';

        $output->info('Bootstrapping Gestor environment...');

        try {
            require_once $configFile;
        } catch (\Throwable $e) {
            $output->error('Failed to load config.php: ' . $e->getMessage());
            return 1;
        }

        global $_CONFIG, $_GESTOR;

        // Load required libraries
        $libs = ['banco', 'gestor', 'usuario', 'seguranca', 'ip'];
        foreach ($libs as $lib) {
            $libFile = $gestorPath . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . $lib . '.php';
            if (is_file($libFile)) {
                require_once $libFile;
            }
        }

        // Connect to database
        try {
            if (function_exists('banco_conectar')) {
                banco_conectar();
            }
        } catch (\Throwable $e) {
            $output->error('Database connection failed: ' . $e->getMessage());
            return 1;
        }

        // Find user
        $output->info("Looking up user: {$userIdent}");
        $whereClause = is_numeric($userIdent)
            ? "WHERE id_usuarios='{$userIdent}'"
            : "WHERE nome='{$userIdent}' OR id_usuarios='1'";

        try {
            $usuario = banco_select([
                'unico' => true,
                'tabela' => 'usuarios',
                'campos' => ['id_usuarios', 'nome'],
                'extra' => $whereClause,
            ]);
        } catch (\Throwable $e) {
            $output->error('Failed to query user: ' . $e->getMessage());
            return 1;
        }

        if (!$usuario) {
            $output->error("User '{$userIdent}' not found in database.");
            return 1;
        }

        $userId = $usuario['id_usuarios'];
        $userName = $usuario['nome'];
        $output->success("User found: {$userName} (ID: {$userId})");

        // Generate JWT token
        $output->info('Generating JWT token...');

        $expiration = time() + ($_CONFIG['cookie-lifetime'] ?? 1296000);
        $opensslPath = $_GESTOR['openssl-path'] ?? ($gestorPath . DIRECTORY_SEPARATOR . 'openssl' . DIRECTORY_SEPARATOR);
        $keyPublicPath = $opensslPath . 'publica.key';

        if (!is_file($keyPublicPath)) {
            $output->error("Public key not found at: {$keyPublicPath}");
            return 1;
        }

        $chavePublica = file_get_contents($keyPublicPath);
        $tokenPubId = md5(uniqid((string)rand(), true));
        $hashAlgo = $_CONFIG['usuario-hash-algo'] ?? 'sha256';
        $hashPassword = $_CONFIG['usuario-hash-password'] ?? '';
        $pubIDValidation = hash_hmac($hashAlgo, $tokenPubId, $hashPassword);

        $tokenJWT = usuario_gerar_jwt([
            'host' => $_SERVER['SERVER_NAME'],
            'expiration' => $expiration,
            'chavePublica' => $chavePublica,
            'pubID' => $tokenPubId,
        ]);

        if (!$tokenJWT) {
            $output->error('Failed to generate JWT token. Check OpenSSL keys.');
            return 1;
        }

        // Delete old CLI-generated tokens for this user
        banco_delete('usuarios_tokens', "WHERE user_agent='Conn2Flow-CLI/1.0' AND id_usuarios='{$userId}'");

        // Insert token record in database
        $ip = function_exists('ip_get') ? ip_get() : '127.0.0.1';
        $campos = [];
        $campos[] = ['id_usuarios', $userId, null];
        $campos[] = ['pubID', $tokenPubId, null];
        $campos[] = ['pubIDValidation', $pubIDValidation, null];
        $campos[] = ['expiration', $expiration, null];
        $campos[] = ['ip', $ip, null];
        $campos[] = ['user_agent', 'Conn2Flow-CLI/1.0', null];
        $campos[] = ['data_criacao', 'NOW()', true];
        banco_insert_name($campos, 'usuarios_tokens');

        // Generate session ID
        $sessionId = function_exists('seguranca_token_aleatorio')
            ? seguranca_token_aleatorio(32)
            : bin2hex(random_bytes(16));

        // Build cookie names
        $cookieAuthName = $_CONFIG['cookie-authname'] ?? '_C2FCID';
        $sessionAuthName = $_CONFIG['session-authname'] ?? '_C2FSID';
        $serverName = $_SERVER['SERVER_NAME'];

        // Resolve URL for project
        $accessUrl = 'http://' . $serverName . '/';
        if ($projectId) {
            $envJsonPath = $this->rootPath . DIRECTORY_SEPARATOR . 'dev-environment' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'environment.json';
            if (is_file($envJsonPath)) {
                $envJson = json_decode(file_get_contents($envJsonPath), true);
                if (isset($envJson['devProjects'][$projectId]['url'])) {
                    $accessUrl = $envJson['devProjects'][$projectId]['url'];
                }
            }
        }

        // Write Netscape cookie jar
        $domain = $serverName;
        $expStr = (string)$expiration;
        $cookieJar = "# Netscape HTTP Cookie File\n";
        $cookieJar .= "# Generated by c2f auth:cookie on " . date('Y-m-d H:i:s') . "\n";
        $cookieJar .= "# User: {$userName} (ID: {$userId})\n";
        $cookieJar .= "{$domain}\tFALSE\t/\tFALSE\t{$expStr}\t{$cookieAuthName}\t{$tokenJWT}\n";
        $cookieJar .= "{$domain}\tFALSE\t/\tFALSE\t{$expStr}\t{$sessionAuthName}\t{$sessionId}\n";

        file_put_contents($outPath, $cookieJar);

        // Output results
        $output->section('Cookie Jar');
        $output->success("Written to: {$outPath}");

        $output->section('HTTP Header');
        $cookieHeader = "Cookie: {$cookieAuthName}={$tokenJWT}; {$sessionAuthName}={$sessionId}";
        $output->writeln($cookieHeader);

        $output->section('Usage Examples');
        $output->writeln("  curl -b {$outPath} {$accessUrl}");
        $output->writeln("  curl -H \"{$cookieHeader}\" {$accessUrl}");

        $output->writeln('');
        $output->info('Token expires: ' . date('Y-m-d H:i:s', $expiration));

        return 0;
    }
}
