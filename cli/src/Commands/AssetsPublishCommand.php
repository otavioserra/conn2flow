<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Conn2Flow\Cli\Support\SshRemoteTransport;
use Throwable;

/**
 * req-028 / BATCH-023 — publicação dos assets estáticos em `public_html/dist/`.
 *
 * O código PHP do gestor mora fora da pasta pública. Sem esta publicação, TODA imagem, fonte, CSS
 * e JS do painel atravessa o front-controller e o controlador `arquivo-estatico` — um processo
 * PHP-FPM por arquivo, com sessão, roteamento e leitura de banco antes de um `readfile()`. Depois
 * dela, o Nginx e o Apache entregam o arquivo direto do disco.
 *
 * Três decisões sustentam o comando:
 *
 * 1. **O caminho dentro de `dist/` é o caminho da URL.** `recursos_dist_mapear_fonte()` é a fonte
 *    única do contrato e é compartilhada com o runtime, então publicação e resolução não podem
 *    divergir.
 * 2. **Só extensões de asset web são publicadas.** A lista é de permissão, não de bloqueio: um
 *    arquivo novo de tipo inesperado não vaza para a pasta pública por descuido.
 * 3. **A publicação é idempotente e verificável.** O manifesto guarda o SHA-1 do conteúdo
 *    publicado; rodar de novo sem mudanças não reescreve nada, e o hash é o token de cache busting
 *    emitido nas tags HTML.
 */
final class AssetsPublishCommand implements CommandInterface
{
    /** Extensões entregues diretamente pelo servidor web. Lista de PERMISSÃO. */
    private const EXTENSOES = [
        'js', 'css',
        'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'ico', 'bmp', 'avif',
        'woff', 'woff2', 'ttf', 'otf', 'eot',
        'mp4', 'webm', 'ogg', 'mp3',
        'webmanifest',
    ];

    /**
     * Arquivos de `gestor/assets/` que são insumo do pipeline, não asset de página.
     * Publicá-los exporia o inventário interno do build na pasta pública.
     */
    private const NAO_PUBLICAR = [
        'asset-versions.json',
        'minify-manifest.json',
    ];

    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'assets:publish';
    }

    public function getDescription(): string
    {
        return 'Publish processed static assets into public_html/dist/ so the web server serves them without PHP.';
    }

    public function getAliases(): array
    {
        return ['assets:publicar', 'dist:publish'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f assets:publish [--project=ID] [--public=PATH] [--dev] [--clean] [--dry-run]\n\n" .
               "Copies the core and module assets to <public>/dist/, keeping the published path\n" .
               "identical to the public URL, and writes <public>/dist/.manifest.json with the SHA-1\n" .
               "of every published file (used as the cache-busting token in the HTML tags).\n\n" .
               "Options:\n" .
               "  --project=ID   Resolve the DocumentRoot from environment.json (devProjects). With\n" .
               "                 deploy_mode=\"ssh\", publishes locally and rsyncs into ssh_public_path.\n" .
               "  --public=PATH  DocumentRoot to publish into. Defaults to PUBLIC_PATH in gestor/.env.\n" .
               "  --dev          Publish the authored JavaScript instead of the .min.js derivative.\n" .
               "  --clean        Remove files under dist/ that are no longer in the manifest.\n" .
               "  --dry-run      Report what would be published, writing nothing.\n" .
               "  --opcional     Used by pipelines: a missing DocumentRoot is reported, not an error.\n" .
               "  --confirmar-remoto  Required to push dist/ to a VM over SSH.\n" .
               "  --simular-remoto    Print the rsync command line without executing it.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->title('Conn2Flow — Publicação de assets estáticos (dist/)');

        $this->carregarContratoDeMapeamento();

        $remoto = $this->resolverAlvoDeProjeto($input, $output);
        if ($remoto === false) {
            return $input->hasOption('opcional') ? 0 : 1;
        }

        // Publicação remota: o `dist/` é montado numa área de staging local e enviado por rsync,
        // para que a etapa continue idempotente e verificável antes de tocar na VM.
        $publicPath = $remoto !== null
            ? $this->prepararStaging($remoto['staging'], $output)
            : $this->resolverPublicPath($input, $output);

        if ($publicPath === null) {
            return $input->hasOption('opcional') ? 0 : 1;
        }

        $distPath = $publicPath . 'dist' . DIRECTORY_SEPARATOR;
        $dryRun = $input->hasOption('dry-run');
        $preferirMinificado = !$input->hasOption('dev');

        $output->writeln('  DocumentRoot: ' . $publicPath);
        $output->writeln('  Destino:      ' . $distPath);
        if ($dryRun) {
            $output->writeln('  Modo:         simulação (nada será escrito)');
        }
        $output->writeln('');

        $fontes = $this->descobrirFontes();
        if ($fontes === []) {
            $output->warning('Nenhum asset publicável encontrado em gestor/assets ou gestor/modulos.');
            return 0;
        }

        if (!$dryRun && !$this->garantirDiretorio($distPath)) {
            $output->error("Não foi possível criar o diretório de publicação: {$distPath}");
            return 1;
        }

        $manifestoPath = $distPath . '.manifest.json';
        $manifestoAnterior = [];
        if (is_file($manifestoPath)) {
            $decodificado = json_decode((string)file_get_contents($manifestoPath), true);
            if (is_array($decodificado) && isset($decodificado['arquivos']) && is_array($decodificado['arquivos'])) {
                $manifestoAnterior = $decodificado['arquivos'];
            }
        }

        $arquivos = [];
        $publicados = 0;
        $inalterados = 0;
        $falhas = [];
        $bytes = 0;

        foreach ($fontes as $urlRelativa => $origem) {
            $fisico = $preferirMinificado ? $this->preferirMinificado($origem) : $origem;

            $conteudo = @file_get_contents($fisico);
            if ($conteudo === false) {
                $falhas[] = $urlRelativa;
                continue;
            }

            $hash = sha1($conteudo);
            $destino = $distPath . str_replace('/', DIRECTORY_SEPARATOR, $urlRelativa);

            $registro = [
                'sha1' => $hash,
                'bytes' => strlen($conteudo),
                // Token curto de cache busting: muda somente quando o conteúdo publicado muda.
                'v' => substr($hash, 0, 16),
                'fonte' => $this->relativoAoGestor($fisico),
            ];

            $atual = is_file($destino) && ($manifestoAnterior[$urlRelativa]['sha1'] ?? null) === $hash;

            if ($atual) {
                $inalterados++;
                $arquivos[$urlRelativa] = $registro;
                $bytes += strlen($conteudo);
                continue;
            }

            if ($dryRun) {
                $output->writeln('  [dry-run] ' . $urlRelativa);
                $arquivos[$urlRelativa] = $registro;
                $publicados++;
                $bytes += strlen($conteudo);
                continue;
            }

            if (!$this->garantirDiretorio(dirname($destino) . DIRECTORY_SEPARATOR)
                || file_put_contents($destino, $conteudo) === false) {
                $falhas[] = $urlRelativa;
                $output->writeln('  FALHA     ' . $urlRelativa);
                continue;
            }

            $arquivos[$urlRelativa] = $registro;
            $publicados++;
            $bytes += strlen($conteudo);
        }

        ksort($arquivos);

        if (!$dryRun) {
            $this->gravarHtaccess($distPath);
        }

        $removidos = 0;
        if ($input->hasOption('clean')) {
            $removidos = $this->limpar($distPath, $arquivos, $dryRun, $output);
        }

        if (!$dryRun) {
            $manifesto = [
                'versao' => 1,
                'gerado_em' => date('c'),
                'minificado' => $preferirMinificado,
                'arquivos' => $arquivos,
            ];

            if (file_put_contents(
                $manifestoPath,
                json_encode($manifesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
            ) === false) {
                $output->error("Não foi possível gravar o manifesto em {$manifestoPath}");
                return 1;
            }
        }

        $output->writeln('');
        $output->writeln(sprintf(
            'publicados: %d | ja atuais: %d | removidos: %d | falhas: %d | total: %.1f KB',
            $publicados,
            $inalterados,
            $removidos,
            count($falhas),
            $bytes / 1024
        ));

        if ($falhas !== []) {
            // Sem o arquivo em dist/, a URL antiga continua resolvendo pelo controlador estático:
            // o site não quebra, apenas perde a entrega direta desses arquivos.
            $output->warning('Não publicados (seguem sendo servidos pelo arquivo-estatico): '
                . implode(', ', array_slice($falhas, 0, 8)));
        }

        if (!$dryRun) {
            $output->success('Assets publicados em ' . $distPath);
        }

        if ($remoto !== null) {
            return $this->enviarParaVm($remoto, $distPath, $input, $output);
        }

        return 0;
    }

    private function prepararStaging(string $staging, OutputInterface $output): ?string
    {
        $normalizado = rtrim(str_replace('\\', '/', $staging), '/') . '/';

        if (!is_dir($normalizado) && !mkdir($normalizado, 0775, true) && !is_dir($normalizado)) {
            $output->error("Não foi possível criar a área de staging: {$normalizado}");
            return null;
        }

        return $normalizado;
    }

    /**
     * req-050 — resolve o destino quando o alvo é um projeto do `environment.json`.
     *
     * Sem isto, o pipeline `project:update-all` chamava esta etapa sem alvo, ela lia o
     * `PUBLIC_PATH` do `.env` do CORE e publicava o `dist/` do projeto no DocumentRoot de OUTRO
     * site — ou, na ausência do `.env`, simplesmente pulava a etapa.
     *
     * @return array{staging: string, transport: SshRemoteTransport}|null|false
     *         null = alvo local resolvido normalmente; false = erro já reportado.
     */
    private function resolverAlvoDeProjeto(InputInterface $input, OutputInterface $output): array|null|false
    {
        $projectId = (string)($input->getOption('project') ?? '');
        if ($projectId === '') {
            return null;
        }

        try {
            $resolvido = (new ProjectEnvironmentResolver($this->rootPath))->resolve($projectId);
        } catch (Throwable $e) {
            $output->error($e->getMessage());
            return false;
        }

        $ssh = is_array($resolvido['ssh'] ?? null) ? $resolvido['ssh'] : null;
        if ($ssh === null) {
            return null;
        }

        $config = is_array($resolvido['config'] ?? null) ? $resolvido['config'] : [];

        try {
            $transport = new SshRemoteTransport($ssh, $config);
        } catch (Throwable $e) {
            $output->error($e->getMessage());
            return false;
        }

        if ($transport->publicPath() === null) {
            $output->writeln("  Projeto '{$projectId}' usa deploy_mode \"ssh\" mas não declara "
                . 'ssh_public_path: publicação ignorada, os assets seguem sendo servidos pelo '
                . 'arquivo-estatico.');
            return false;
        }

        // Alcançar outra máquina nunca é implícito, mesmo com local=true.
        if (!$input->hasOption('confirmar-remoto') && !$input->hasOption('simular-remoto')) {
            $output->error(
                "Projeto '{$projectId}' publica em {$transport->describe()}. Enviar assets para a VM "
                . 'exige --confirmar-remoto (ou --simular-remoto para apenas ver o comando).'
            );
            return false;
        }

        $staging = $this->rootPath . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR
            . 'assets-publish' . DIRECTORY_SEPARATOR . $projectId . DIRECTORY_SEPARATOR;

        return ['staging' => $staging, 'transport' => $transport];
    }

    /**
     * @param array{staging: string, transport: SshRemoteTransport} $remoto
     */
    private function enviarParaVm(array $remoto, string $distPath, InputInterface $input, OutputInterface $output): int
    {
        $transport = $remoto['transport'];
        $destino = $transport->publicPath() . '/dist';

        $mkdir = $transport->buildEnsureDirectoryCommand($destino);
        $rsync = $transport->buildRsyncCommand($distPath, $destino, $input->hasOption('clean'));

        $output->writeln('');
        $output->writeln('  destino remoto: ' . $transport->target() . ':' . $destino);
        $output->writeln('  preparar:       ' . $mkdir);
        $output->writeln('  enviar:         ' . $rsync);

        if ($input->hasOption('simular-remoto') || $input->hasOption('dry-run')) {
            $output->info('Simulação: os comandos acima não foram executados.');
            return 0;
        }

        foreach ([$mkdir, $rsync] as $comando) {
            $codigo = $this->runShellCommand($comando, $output);
            if ($codigo !== 0) {
                $output->error("Publicação remota falhou (código {$codigo}). Os assets locais "
                    . 'foram gerados em ' . $distPath . ' e podem ser reenviados.');
                return 1;
            }
        }

        $output->success('Assets publicados em ' . $transport->target() . ':' . $destino);
        return 0;
    }

    private function runShellCommand(string $comando, OutputInterface $output): int
    {
        $process = proc_open($comando, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $this->rootPath);

        if (!is_resource($process)) {
            $output->error('Não foi possível iniciar o processo de transporte.');
            return 1;
        }

        fclose($pipes[0]);

        while ($linha = fgets($pipes[1])) {
            $output->write($linha);
        }
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $codigo = proc_close($process);

        if ($codigo !== 0 && is_string($stderr) && trim($stderr) !== '') {
            $output->error(trim($stderr));
        }

        return $codigo;
    }

    /** Carrega o contrato de mapeamento compartilhado com o runtime do gestor. */
    private function carregarContratoDeMapeamento(): void
    {
        if (function_exists('recursos_dist_mapear_fonte')) {
            return;
        }

        require_once $this->rootPath . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR
            . 'bibliotecas' . DIRECTORY_SEPARATOR . 'recursos.php';
    }

    /**
     * DocumentRoot de publicação: opção explícita, `PUBLIC_PATH` do `.env` ou nada.
     * Nunca adivinhamos o diretório: escrever na pasta errada de um servidor compartilhado é pior
     * do que não publicar.
     */
    private function resolverPublicPath(InputInterface $input, OutputInterface $output): ?string
    {
        $declarado = (string)($input->getOption('public') ?? '');

        if ($declarado === '') {
            $declarado = (string)($this->lerEnv('PUBLIC_PATH') ?? '');
        }

        if ($declarado === '') {
            // Chamado de dentro de um pipeline, a ausência de DocumentRoot não é erro: a instalação
            // simplesmente não usa `dist/` e todo asset segue pelo controlador `arquivo-estatico`.
            if ($input->hasOption('opcional')) {
                $output->writeln('  DocumentRoot não declarado (PUBLIC_PATH ausente): publicação '
                    . 'ignorada, os assets seguem sendo servidos pelo arquivo-estatico.');
                return null;
            }

            $output->error('DocumentRoot não informado. Use --public=/caminho/public_html '
                . 'ou declare PUBLIC_PATH no gestor/.env.');
            return null;
        }

        $normalizado = rtrim(str_replace('\\', '/', $declarado), '/');
        if ($normalizado === '' || !is_dir($normalizado)) {
            $output->error("DocumentRoot inexistente: {$declarado}");
            return null;
        }

        return $normalizado . '/';
    }

    /** Lê uma chave do `gestor/.env` sem carregar o bootstrap completo do gestor. */
    private function lerEnv(string $chave): ?string
    {
        $envPath = $this->rootPath . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envPath)) {
            return null;
        }

        $linhas = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($linhas as $linha) {
            if (strpos(trim($linha), '#') === 0 || strpos($linha, '=') === false) {
                continue;
            }
            [$nome, $valor] = explode('=', $linha, 2);
            if (trim($nome) !== $chave) {
                continue;
            }

            return trim(trim(trim($valor), '"'), "'");
        }

        return null;
    }

    /**
     * Assets publicáveis, indexados pelo caminho que terão na URL.
     *
     * @return array<string, string> caminho na URL => caminho físico de origem
     */
    private function descobrirFontes(): array
    {
        $gestor = $this->rootPath . DIRECTORY_SEPARATOR . 'gestor';
        $fontes = [];

        foreach (['assets', 'modulos'] as $raiz) {
            $base = $gestor . DIRECTORY_SEPARATOR . $raiz;
            if (!is_dir($base)) {
                continue;
            }

            $iterador = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterador as $item) {
                if (!$item->isFile() || $item->isLink()) {
                    continue;
                }

                $nome = $item->getFilename();
                if (in_array($nome, self::NAO_PUBLICAR, true)) {
                    continue;
                }

                $extensao = strtolower($item->getExtension());
                if (!in_array($extensao, self::EXTENSOES, true)) {
                    continue;
                }

                // `X.min.js` com `X.js` ao lado é DERIVADO, não asset independente: ele já será
                // publicado como `X.js` por `preferirMinificado()`. Publicá-lo também sob nome
                // próprio duplicaria o arquivo na pasta pública sem nenhuma URL que o peça.
                // Terceiros que só distribuem o minificado (datatables, jQuery Mask) não têm par
                // e continuam entrando normalmente.
                if (substr($nome, -7) === '.min.js'
                    && is_file(substr($item->getPathname(), 0, -7) . '.js')) {
                    continue;
                }

                $relativo = $raiz . '/' . str_replace('\\', '/', substr($item->getPathname(), strlen($base) + 1));
                $url = recursos_dist_mapear_fonte($relativo);
                if ($url === '') {
                    continue;
                }

                $fontes[$url] = $item->getPathname();
            }
        }

        ksort($fontes);

        return $fontes;
    }

    /**
     * Escolhe o derivado `.min.js` quando ele existe — a mesma decisão que
     * `arquivo_estatico_preferir_minificado()` toma em runtime, antecipada para o build.
     */
    private function preferirMinificado(string $arquivo): string
    {
        if (substr($arquivo, -3) !== '.js' || substr($arquivo, -7) === '.min.js') {
            return $arquivo;
        }

        $minificado = substr($arquivo, 0, -3) . '.min.js';

        // Arquivo vazio significa minificação interrompida no meio: o de autoria é maior, porém correto.
        return (is_file($minificado) && filesize($minificado) > 0) ? $minificado : $arquivo;
    }

    /**
     * Grava o `.htaccess` de cache dentro de `dist/` — paridade com o `location /dist/` do Nginx.
     *
     * Fica DENTRO do diretório de propósito: escopar por diretório no `.htaccess` da raiz exigiria
     * a diretiva `<If>`, que só existe no Apache 2.4 e derruba o site inteiro com 500 no 2.2. Aqui
     * o escopo vem da própria localização do arquivo, e os `IfModule` tornam o bloco inerte onde os
     * módulos não estão carregados. No Nginx o arquivo é ignorado e o template já nega ocultos.
     *
     * O cache é longo com segurança porque toda URL emitida carrega `?v=<hash do conteúdo>`.
     */
    private function gravarHtaccess(string $distPath): void
    {
        $conteudo = "# Conn2Flow req-028 - gerado por `c2f assets:publish`. Nao editar a mao.\n"
            . "# Cache longo dos assets publicados; a URL carrega ?v=<hash>, entao nunca serve\n"
            . "# conteudo antigo. No Nginx este arquivo e inerte (ver location /dist/ no template).\n"
            . "<IfModule mod_expires.c>\n"
            . "\tExpiresActive On\n"
            . "\tExpiresDefault \"access plus 1 year\"\n"
            . "</IfModule>\n"
            . "<IfModule mod_headers.c>\n"
            . "\tHeader set Cache-Control \"public, max-age=31536000, immutable\"\n"
            . "</IfModule>\n"
            . "# O diretorio guarda apenas assets: nenhum deles deve ser executado pelo servidor.\n"
            . "<IfModule mod_php.c>\n"
            . "\tphp_flag engine off\n"
            . "</IfModule>\n"
            . "Options -Indexes -ExecCGI\n"
            . "\n"
            . "# O manifesto (.manifest.json) e este proprio arquivo sao internos do build.\n"
            . "<FilesMatch \"^\\.\">\n"
            . "\t<IfModule mod_authz_core.c>\n"
            . "\t\tRequire all denied\n"
            . "\t</IfModule>\n"
            . "\t<IfModule !mod_authz_core.c>\n"
            . "\t\tOrder allow,deny\n"
            . "\t\tDeny from all\n"
            . "\t</IfModule>\n"
            . "</FilesMatch>\n";

        @file_put_contents($distPath . '.htaccess', $conteudo);
    }

    private function relativoAoGestor(string $absoluto): string
    {
        $gestor = $this->rootPath . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR;
        $absoluto = str_replace('\\', '/', $absoluto);
        $gestor = str_replace('\\', '/', $gestor);

        return strpos($absoluto, $gestor) === 0 ? substr($absoluto, strlen($gestor)) : $absoluto;
    }

    private function garantirDiretorio(string $diretorio): bool
    {
        return is_dir($diretorio) || @mkdir($diretorio, 0755, true) || is_dir($diretorio);
    }

    /**
     * Remove de `dist/` o que não está mais no manifesto novo.
     *
     * @param array<string, array<string, mixed>> $arquivos
     */
    private function limpar(string $distPath, array $arquivos, bool $dryRun, OutputInterface $output): int
    {
        if (!is_dir($distPath)) {
            return 0;
        }

        $removidos = 0;
        $iterador = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($distPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterador as $item) {
            $relativo = str_replace('\\', '/', substr($item->getPathname(), strlen($distPath)));

            // Diretórios ficam para o fim (CHILD_FIRST) e só somem quando esvaziam; eles não
            // entram na contagem, que reporta arquivos removidos.
            if ($item->isDir()) {
                if (!$dryRun) {
                    @rmdir($item->getPathname());
                }
                continue;
            }

            // O manifesto indexa o diretório e o .htaccess define sua política de cache: os dois
            // são gerados por este comando, não entram no manifesto e nunca são removidos por ele.
            if ($relativo === '.manifest.json' || $relativo === '.htaccess' || isset($arquivos[$relativo])) {
                continue;
            }

            if ($dryRun) {
                $output->writeln('  [dry-run] remover ' . $relativo);
                $removidos++;
                continue;
            }

            if (@unlink($item->getPathname())) {
                $removidos++;
            }
        }

        return $removidos;
    }
}
