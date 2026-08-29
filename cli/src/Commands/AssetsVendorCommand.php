<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

/**
 * req-143 / BATCH-146 — baixa as bibliotecas de terceiros para `gestor/assets/vendor/`.
 *
 * Por que este comando existe. A biblioteca `assets-externos.php` já resolvia "local primeiro, CDN
 * como fallback", mas `assets/vendor/` nunca existiu — então o fallback era o único caminho e o
 * sistema seguia 100% dependente de CDN, com a aparência de já ter migrado. Este é o passo que
 * faltava para a promessa virar arquivo em disco.
 *
 * O que a migração resolve, medido no inventário do core:
 *
 *  - jQuery em QUATRO pontos, com TRÊS versões e QUATRO hosts (3.5.1 de `ajax.googleapis.com`,
 *    3.7.1 de jsdelivr, 3.7.1 de cdnjs, 3.6.0 de jsdelivr). Duas versões na mesma tela quebram
 *    plugins de um jeito difícil de diagnosticar, porque quem carrega por último vence.
 *  - O IP de cada visitante entregue a jsdelivr, Cloudflare, unpkg e Google. Para um projeto do
 *    Ministério Público isso é conformidade, não preferência.
 *  - O argumento histórico do CDN — cache compartilhado entre sites — acabou quando os navegadores
 *    passaram a particionar o cache HTTP por origem. Hoje se pagam DNS e TLS extras sem ganhar cache.
 *
 * O comando NÃO altera código: ele apenas popula o diretório. Quem decide de onde servir continua
 * sendo `assets_externos_url()`, que passa a encontrar o arquivo local.
 */
final class AssetsVendorCommand implements CommandInterface
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'assets:vendor';
    }

    public function getDescription(): string
    {
        return 'Download registered third-party libraries into gestor/assets/vendor/ (local-first serving).';
    }

    public function getAliases(): array
    {
        return ['vendor:assets', 'assets:download'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f assets:vendor [--lib=NAME] [--forcar] [--listar]\n\n" .
               "Downloads every file declared in assets_externos_registro() into\n" .
               "gestor/assets/vendor/<lib>/<version>/. Existing files are kept unless --forcar.\n\n" .
               "Options:\n" .
               "  --lib=NAME  Only this library (default: all registered).\n" .
               "  --forcar    Re-download files that already exist.\n" .
               "  --listar    Show what would be downloaded, without writing anything.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $bibliotecaPath = $this->rootPath . '/gestor/bibliotecas/assets-externos.php';
        if (!is_file($bibliotecaPath)) {
            $output->error("Biblioteca não encontrada: {$bibliotecaPath}");
            return 1;
        }

        require_once $bibliotecaPath;

        $registro = assets_externos_registro();
        $apenas = (string)($input->getOption('lib') ?? '');
        $forcar = $input->hasOption('forcar');
        $listar = $input->hasOption('listar');

        if ($apenas !== '') {
            if (!isset($registro[$apenas])) {
                $output->error("Biblioteca '{$apenas}' não está registrada. Registradas: "
                    . implode(', ', array_keys($registro)));
                return 1;
            }
            $registro = [$apenas => $registro[$apenas]];
        }

        $vendorBase = $this->rootPath . '/gestor/assets/vendor';
        $output->title('Conn2Flow — Assets de terceiros (local-first)');
        $output->write("  destino: {$vendorBase}");
        $output->write('');

        $baixados = 0;
        $mantidos = 0;
        $falhas = [];

        foreach ($registro as $nome => $lib) {
            $versao = (string)($lib['versao'] ?? '');
            $arquivos = array_merge((array)($lib['css'] ?? []), (array)($lib['js'] ?? []));

            $output->section("{$nome}@{$versao} (" . count($arquivos) . ' arquivos)');

            foreach ($arquivos as $arquivo) {
                $url = str_replace(['{v}', '{f}'], [$versao, $arquivo], (string)($lib['cdn'] ?? ''));
                $destino = $vendorBase . '/' . $nome . '/' . $versao . '/' . $arquivo;

                if (!$forcar && is_file($destino) && filesize($destino) > 0) {
                    $mantidos++;
                    continue;
                }

                if ($listar) {
                    $output->write("  [listar] {$arquivo}  <-  {$url}");
                    continue;
                }

                $conteudo = $this->baixar($url);

                // Um arquivo vazio, ou uma página de erro do CDN gravada no lugar da biblioteca, é
                // pior que a ausência: o `is_file()` do resolvedor passaria a servi-lo como se fosse
                // válido, e a tela quebraria sem nenhuma pista de rede.
                if ($conteudo === null || $conteudo === '') {
                    $falhas[] = "{$nome}/{$arquivo}";
                    $output->write("  FALHA   {$arquivo}");
                    continue;
                }

                $dir = dirname($destino);
                if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                    $falhas[] = "{$nome}/{$arquivo} (mkdir)";
                    continue;
                }

                file_put_contents($destino, $conteudo);
                $baixados++;
                $output->write(sprintf('  OK      %-46s %8d B', $arquivo, strlen($conteudo)));
            }
        }

        $output->write('');
        $output->write("baixados: {$baixados} | ja existiam: {$mantidos} | falhas: " . count($falhas));

        if ($falhas !== []) {
            $output->warning('Sem esses arquivos as bibliotecas continuam sendo servidas do CDN '
                . '(o fallback segue valendo): ' . implode(', ', $falhas));
            return 1;
        }

        if (!$listar && $baixados > 0) {
            $output->success('Assets locais prontos. `assets_externos_url()` passa a servir do disco.');
        }

        return 0;
    }

    /**
     * Busca o conteúdo de uma URL, com verificação de certificado SEMPRE ligada.
     *
     * A cadeia existe por um motivo concreto de ambiente: o PHP CLI do Windows costuma vir sem
     * `curl.cainfo`/`openssl.cafile`, e toda requisição HTTPS falha com "unable to get local issuer
     * certificate" — foi exatamente o que aconteceu aqui, com as 28 baixas falhando de uma vez.
     *
     * A saída FÁCIL seria `CURLOPT_SSL_VERIFYPEER => false`, e ela está descartada de propósito:
     * este comando grava arquivos que vão ser servidos como biblioteca em toda tela do gestor.
     * Baixar isso por um canal não verificado é pior do que continuar no CDN.
     *
     * Então: PHP cURL primeiro; se ele não tiver âncora de confiança, o binário `curl` do sistema,
     * que valida contra o repositório de certificados do SO; e `file_get_contents` por último.
     *
     * Só aceita HTTP 200: um 404 do CDN devolve corpo HTML, e gravá-lo com nome de biblioteca seria
     * a falha mais difícil de diagnosticar que este comando poderia produzir.
     */
    private function baixar(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_USERAGENT => 'conn2flow-cli/assets-vendor',
            ]);
            $corpo = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erro = curl_error($ch);
            curl_close($ch);

            if ($status === 200 && is_string($corpo)) {
                return $corpo;
            }

            // Falha de âncora de confiança não é falha da URL: vale tentar quem tem o repositório
            // de certificados do sistema.
            if (stripos($erro, 'certificate') === false && $status !== 0) {
                return null;
            }
        }

        $corpo = $this->baixarComCurlDoSistema($url);
        if ($corpo !== null) {
            return $corpo;
        }

        $contexto = stream_context_create(['http' => ['timeout' => 60, 'ignore_errors' => true]]);
        $corpo = @file_get_contents($url, false, $contexto);

        if (!is_string($corpo)) {
            return null;
        }

        foreach ($http_response_header ?? [] as $cabecalho) {
            if (stripos($cabecalho, 'HTTP/') === 0) {
                return (strpos($cabecalho, ' 200') !== false) ? $corpo : null;
            }
        }

        return null;
    }

    /**
     * Último recurso: o binário `curl` do sistema, que traz sua própria âncora de confiança.
     *
     * `--fail` faz o próprio curl recusar respostas >= 400, de modo que uma página de erro nunca
     * chega a virar arquivo. Nenhum `-k`/`--insecure` aqui, pelo mesmo motivo de sempre.
     */
    private function baixarComCurlDoSistema(string $url): ?string
    {
        if (!function_exists('exec')) {
            return null;
        }

        $destino = tempnam(sys_get_temp_dir(), 'c2f-vendor-');
        if ($destino === false) {
            return null;
        }

        $comando = 'curl -sS --fail --location --max-time 60 '
            . escapeshellarg($url) . ' -o ' . escapeshellarg($destino) . ' 2>&1';

        $saida = [];
        $status = 1;
        @exec($comando, $saida, $status);

        $corpo = ($status === 0 && is_file($destino)) ? (string)file_get_contents($destino) : null;
        @unlink($destino);

        return ($corpo === '') ? null : $corpo;
    }
}
