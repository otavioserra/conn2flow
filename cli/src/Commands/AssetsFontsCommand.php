<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;
use Conn2Flow\Cli\Support\ProjectEnvironmentResolver;
use Throwable;

/**
 * req-143 / BATCH-148 — traz as fontes do Google Fonts para dentro do projeto.
 *
 * Por que isto não é preferência estética. Um `<link>` para `fonts.googleapis.com` faz o navegador
 * de CADA visitante abrir conexão com o Google e entregar o IP, o `Referer` (a página exata que a
 * pessoa está lendo) e o `User-Agent` — antes de qualquer consentimento e sem que o visitante tenha
 * escolhido interagir com o Google. Em 2022 o Landgericht München I condenou o operador de um site
 * exatamente por isso (Az. 3 O 17493/20). Para um projeto do Ministério Público, o argumento de
 * conformidade é mais forte do que em qualquer outro site do ecossistema.
 *
 * A licença não é obstáculo: as famílias do Google Fonts são publicadas sob SIL Open Font License ou
 * Apache 2.0, e ambas permitem hospedar os arquivos.
 *
 * O que o comando faz:
 *
 *  1. descobre as URLs do Google Fonts declaradas nos recursos do projeto (ou aceita `--url`);
 *  2. busca o CSS com User-Agent moderno — o Google devolve `woff2` só para quem sabe lê-lo;
 *  3. filtra os subsets (padrão `latin` + `latin-ext`), porque um site em português não precisa de
 *     cirílico, grego nem vietnamita: das 45 faces devolvidas para duas famílias, a maioria nunca
 *     seria pedida por um leitor brasileiro, e versioná-las é peso morto no repositório;
 *  4. baixa cada `woff2` e reescreve o `src: url(...)` para caminho relativo local;
 *  5. grava um único CSS em `contents/project/fonts/`.
 *
 * O `unicode-range` de cada face é PRESERVADO: é ele que faz o navegador baixar só o subset de que
 * precisa. Removê-lo transformaria a economia do passo 3 em desperdício de banda do visitante.
 */
final class AssetsFontsCommand implements CommandInterface
{
    /** O Google decide o formato pelo User-Agent: sem um moderno, devolve `ttf` em vez de `woff2`. */
    private const UA_MODERNO = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
        . '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    private const SUBSETS_PADRAO = 'latin,latin-ext';

    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'assets:fonts';
    }

    public function getDescription(): string
    {
        return 'Self-host Google Fonts declared by a project (downloads woff2 and rewrites the CSS).';
    }

    public function getAliases(): array
    {
        return ['fonts:vendor', 'assets:fontes'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f assets:fonts --project=ID [--url=CSS_URL] [--subsets=latin,latin-ext] [--listar]\n\n" .
               "Downloads the Google Fonts families a project declares and rewrites them as a local\n" .
               "stylesheet under contents/project/fonts/, so no visitor request reaches Google.\n\n" .
               "Options:\n" .
               "  --project=ID  Project whose resources are scanned (and where files are written).\n" .
               "  --url=URL     Use this Google Fonts CSS URL instead of scanning the resources.\n" .
               "  --subsets=..  Comma-separated subsets to keep (default: " . self::SUBSETS_PADRAO . ").\n" .
               "  --todos       Keep every subset, including cyrillic/greek/vietnamese.\n" .
               "  --listar      Report what would be downloaded, without writing anything.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectId = (string)($input->getOption('project') ?? '');
        if ($projectId === '') {
            $output->error('Informe --project=ID. Exemplo: c2f assets:fonts --project=transformamp-local');
            return 1;
        }

        try {
            $resolver = new ProjectEnvironmentResolver($this->rootPath);
            $projeto = $resolver->resolve($projectId);
        } catch (Throwable $e) {
            $output->error($e->getMessage());
            return 1;
        }

        $config = $projeto['config'];
        $ehLocal = ($config['local'] ?? false) === true;

        // Mesma trava do `css:rebuild`: este comando ESCREVE no repositório do projeto, e projeto de
        // teste e projeto de produção compartilham a mesma fonte.
        $output->write("  projeto: {$projectId} | local: " . ($ehLocal ? 'true' : 'false')
            . ' | url: ' . $projeto['accessUrl']);

        if (!$ehLocal && !$input->hasOption('confirmar-remoto')) {
            $output->error("Projeto '{$projectId}' não está marcado como local em environment.json. "
                . 'Rode com --confirmar-remoto apenas com autorização explícita do operador.');
            return 1;
        }

        // A FONTE, não o espelho: `resolve()` prefere `path_tests`, mas o que o pipeline lê e o
        // deploy publica é `path`. Escrever no espelho faria o trabalho sumir no próximo sync.
        $fonte = $this->caminhoFonte($config, (string)$projeto['gestorPath']);
        if ($fonte === null) {
            $output->error('Não foi possível resolver o caminho de FONTE do projeto (campo `path`).');
            return 1;
        }

        $urls = [];
        $urlManual = (string)($input->getOption('url') ?? '');
        if ($urlManual !== '') {
            $urls[] = $urlManual;
        } else {
            $urls = $this->descobrirUrls($fonte);
        }

        if ($urls === []) {
            $output->warning('Nenhuma URL do Google Fonts encontrada nos recursos do projeto.');
            return 0;
        }

        $subsets = $input->hasOption('todos')
            ? []
            : array_map('trim', explode(',', (string)($input->getOption('subsets') ?? self::SUBSETS_PADRAO)));

        $destinoFisico = $fonte . DIRECTORY_SEPARATOR . 'contents' . DIRECTORY_SEPARATOR
            . 'project' . DIRECTORY_SEPARATOR . 'fonts';

        $output->title('Conn2Flow — Google Fonts self-hosted');
        $output->write('  destino: ' . $destinoFisico);
        $output->write('  subsets: ' . ($subsets === [] ? 'todos' : implode(', ', $subsets)));
        $output->write('  URLs declaradas: ' . count($urls));
        $output->write('');

        $cssFinal = [];
        $baixados = 0;
        $descartados = 0;
        $falhas = [];

        foreach ($urls as $url) {
            $output->section(substr($url, 0, 110));

            $css = $this->buscar($url, self::UA_MODERNO);
            if ($css === null) {
                $falhas[] = $url;
                $output->write('  FALHA ao buscar o CSS');
                continue;
            }

            $faces = $this->extrairFaces($css);
            $output->write('  faces devolvidas: ' . count($faces));

            foreach ($faces as $face) {
                if ($subsets !== [] && !in_array($face['subset'], $subsets, true)) {
                    $descartados++;
                    continue;
                }

                $arquivo = $this->nomeArquivo($face);
                $destino = $destinoFisico . DIRECTORY_SEPARATOR . $arquivo;

                if (!$input->hasOption('listar')) {
                    if (!is_file($destino) || filesize($destino) === 0) {
                        $binario = $this->buscar($face['url'], self::UA_MODERNO);
                        if ($binario === null || $binario === '') {
                            $falhas[] = $arquivo;
                            continue;
                        }

                        if (!is_dir($destinoFisico) && !mkdir($destinoFisico, 0775, true) && !is_dir($destinoFisico)) {
                            $falhas[] = $arquivo . ' (mkdir)';
                            continue;
                        }

                        file_put_contents($destino, $binario);
                        $baixados++;
                    }
                }

                $cssFinal[] = $this->reescreverFace($face, $arquivo);
            }
        }

        $output->write('');
        $output->write("faces mantidas: " . count($cssFinal) . " | descartadas por subset: {$descartados}"
            . " | arquivos baixados: {$baixados} | falhas: " . count($falhas));

        if ($falhas !== []) {
            $output->warning('Falhas: ' . implode(', ', array_slice($falhas, 0, 8)));
            return 1;
        }

        if ($input->hasOption('listar')) {
            $output->write('');
            $output->write('(--listar: nada foi gravado)');
            return 0;
        }

        if ($cssFinal === []) {
            $output->warning('Nenhuma face restou depois do filtro de subset.');
            return 1;
        }

        $cabecalho = "/*\n"
            . " * Fontes hospedadas pelo próprio projeto (req-143).\n"
            . " *\n"
            . " * Gerado por `c2f assets:fonts` — DERIVADO, não edite à mão: a próxima execução sobrescreve.\n"
            . " * Substitui o `<link>` para fonts.googleapis.com, que fazia o navegador de cada visitante\n"
            . " * entregar IP e Referer ao Google antes de qualquer consentimento.\n"
            . " *\n"
            . " * O `unicode-range` de cada face é preservado: é ele que faz o navegador baixar apenas o\n"
            . " * subset de que precisa.\n"
            . " */\n\n";

        $arquivoCss = $destinoFisico . DIRECTORY_SEPARATOR . 'fonts.css';
        file_put_contents($arquivoCss, $cabecalho . implode("\n", $cssFinal) . "\n");

        $output->write('  CSS gravado: ' . $arquivoCss);
        $output->success('Fontes locais prontas. Troque o <link> do Google pelos arquivos em project/fonts/.');

        return 0;
    }

    /**
     * Caminho de FONTE do projeto (onde o desenvolvedor edita), não o espelho de testes.
     *
     * @param array<string, mixed> $config
     */
    private function caminhoFonte(array $config, string $fallback): ?string
    {
        $path = $config['path'] ?? null;
        if (!is_string($path) || $path === '') {
            return $fallback !== '' ? $fallback : null;
        }

        // `/c/Users/...` (formato Git Bash) para `C:\Users\...`.
        if (preg_match('#^/([a-zA-Z])/(.*)$#', $path, $m)) {
            $path = strtoupper($m[1]) . ':' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $m[2]);
        }

        $path = rtrim($path, "/\\");

        if (!is_file($path . DIRECTORY_SEPARATOR . 'config.php')
            && is_file($path . DIRECTORY_SEPARATOR . 'gestor' . DIRECTORY_SEPARATOR . 'config.php')) {
            $path .= DIRECTORY_SEPARATOR . 'gestor';
        }

        return is_dir($path) ? $path : ($fallback !== '' ? $fallback : null);
    }

    /**
     * Varre os recursos do projeto atrás das URLs do Google Fonts já declaradas.
     *
     * Descobrir em vez de exigir a lista mantém o comando alinhado ao que os layouts realmente
     * pedem: acrescentar uma família ao layout e rodar o comando basta.
     *
     * @return list<string>
     */
    private function descobrirUrls(string $gestorPath): array
    {
        $encontradas = [];
        $raiz = $gestorPath . DIRECTORY_SEPARATOR . 'resources';

        if (!is_dir($raiz)) {
            return [];
        }

        $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz));
        foreach ($iterador as $arquivo) {
            if (!$arquivo->isFile()) {
                continue;
            }
            if (!in_array(strtolower($arquivo->getExtension()), ['html', 'css'], true)) {
                continue;
            }

            $conteudo = (string)file_get_contents($arquivo->getPathname());
            if (preg_match_all('#https://fonts\.googleapis\.com/css2\?[^"\')\s]+#', $conteudo, $m)) {
                foreach ($m[0] as $url) {
                    $encontradas[html_entity_decode($url)] = true;
                }
            }
        }

        return array_keys($encontradas);
    }

    /**
     * Quebra o CSS do Google em faces, guardando o subset que o comentário anterior nomeia.
     *
     * @return list<array{subset: string, familia: string, estilo: string, peso: string, url: string, bloco: string}>
     */
    private function extrairFaces(string $css): array
    {
        $faces = [];

        // O Google emite `/* latin */` imediatamente antes de cada `@font-face`. É a única marcação
        // de subset disponível — o `unicode-range` sozinho não diz o nome.
        $padrao = '#/\*\s*([a-z0-9-]+)\s*\*/\s*(@font-face\s*\{[^}]*\})#i';

        if (!preg_match_all($padrao, $css, $m, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($m as $achado) {
            $bloco = $achado[2];

            if (!preg_match('#src:\s*url\((https://fonts\.gstatic\.com[^)]+)\)#i', $bloco, $u)) {
                continue;
            }

            preg_match("#font-family:\s*'([^']+)'#i", $bloco, $f);
            preg_match('#font-style:\s*([a-z]+)#i', $bloco, $e);
            preg_match('#font-weight:\s*([0-9]+)#i', $bloco, $p);

            $faces[] = [
                'subset' => strtolower($achado[1]),
                'familia' => $f[1] ?? 'font',
                'estilo' => $e[1] ?? 'normal',
                'peso' => $p[1] ?? '400',
                'url' => $u[1],
                'bloco' => $bloco,
            ];
        }

        return $faces;
    }

    /**
     * Nome legível e estável para o arquivo local.
     *
     * O nome do Google é um hash opaco; um nome descritivo torna o diretório auditável — dá para
     * ver o que está versionado sem abrir cada arquivo.
     *
     * @param array{subset: string, familia: string, estilo: string, peso: string, url: string, bloco: string} $face
     */
    private function nomeArquivo(array $face): string
    {
        $familia = strtolower(preg_replace('#[^a-zA-Z0-9]+#', '-', $face['familia']) ?? 'font');
        $italico = ($face['estilo'] === 'italic') ? '-italic' : '';

        return trim($familia, '-') . '-' . $face['peso'] . $italico . '-' . $face['subset'] . '.woff2';
    }

    /**
     * Reescreve o `src` da face para o arquivo local, preservando todo o resto do bloco.
     *
     * @param array{subset: string, familia: string, estilo: string, peso: string, url: string, bloco: string} $face
     */
    private function reescreverFace(array $face, string $arquivo): string
    {
        $bloco = preg_replace(
            '#src:\s*url\(https://fonts\.gstatic\.com[^)]+\)#i',
            "src: url({$arquivo})",
            $face['bloco']
        );

        return "/* {$face['subset']} */\n" . $bloco;
    }

    /** Busca uma URL, com a mesma cadeia verificada de `assets:vendor`. */
    private function buscar(string $url, string $userAgent): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_USERAGENT => $userAgent,
            ]);
            $corpo = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erro = curl_error($ch);
            curl_close($ch);

            if ($status === 200 && is_string($corpo)) {
                return $corpo;
            }

            if (stripos($erro, 'certificate') === false && $status !== 0) {
                return null;
            }
        }

        // Sem âncora de confiança no PHP, o binário `curl` do sistema resolve — nunca `--insecure`.
        if (!function_exists('exec')) {
            return null;
        }

        $temp = tempnam(sys_get_temp_dir(), 'c2f-font-');
        if ($temp === false) {
            return null;
        }

        $comando = 'curl -sS --fail --location --max-time 60 -A ' . escapeshellarg($userAgent) . ' '
            . escapeshellarg($url) . ' -o ' . escapeshellarg($temp) . ' 2>&1';

        $saida = [];
        $status = 1;
        @exec($comando, $saida, $status);

        $corpo = ($status === 0 && is_file($temp)) ? (string)file_get_contents($temp) : null;
        @unlink($temp);

        return ($corpo === '') ? null : $corpo;
    }
}
