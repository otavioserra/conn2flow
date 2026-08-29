<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Commands;

use Conn2Flow\Cli\Contracts\CommandInterface;
use Conn2Flow\Cli\Contracts\InputInterface;
use Conn2Flow\Cli\Contracts\OutputInterface;

/**
 * req-145 / BATCH-148 — minificação do JavaScript próprio, no BUILD.
 *
 * A sugestão original era minificar no roteamento (`controladores/arquivo-estatico`). Não é ali, e
 * a razão é concreta: aquele controlador ganhou no BATCH-100 `Content-Length`, `Accept-Ranges`,
 * `ETag` e `304`, e os quatro dependem de o corpo entregue ser exatamente o arquivo em disco.
 * Transformar o corpo na hora da resposta significaria anunciar um tamanho que não se conhece,
 * validar com um `ETag` derivado de outro conteúdo, pagar CPU por visitante e arriscar quebra por
 * ASI — quatro problemas de correção em troca de um de tamanho.
 *
 * O arquivo minificado é um DERIVADO do JS de autoria, exatamente como `css_precompiled` é derivado
 * do `css` (CR-002): gerado por um passo de build, nunca editado à mão, sempre recalculável. Quem
 * escolhe servi-lo é a RESOLUÇÃO do arquivo estático, e por isso o envio continua sendo um envio de
 * arquivo — `Range`, `ETag` e `304` seguem corretos, calculados sobre o arquivo realmente servido.
 *
 * Usa o `terser` (já presente em devDependencies). O `--mangle` renomeia apenas variáveis locais.
 *
 * LIMITE HONESTO: `node --check` valida a SINTAXE de cada saída, não a semântica. Código que dependa
 * do nome de funções em runtime (`fn.name`, `eval` de identificadores locais) pode minificar sem
 * erro e falhar no navegador. Por isso a checagem de sintaxe é obrigatória e a saída é descartada
 * quando ela falha — mas homologação continua sendo do operador.
 */
final class AssetsMinifyCommand implements CommandInterface
{
    /** Diretórios que não são autoria do projeto e já vêm minificados de fábrica. */
    private const IGNORAR = [
        '/vendor/',
        '/datatables/',
        '/jQuery-Mask-Plugin',
        '/jQuery-File-Upload',
        '/node_modules/',
    ];

    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getName(): string
    {
        return 'assets:minify';
    }

    public function getDescription(): string
    {
        return 'Minify the core own JavaScript into .min.js derivatives (build step, never at request time).';
    }

    public function getAliases(): array
    {
        return ['assets:minificar', 'minify:assets'];
    }

    public function getHelp(): string
    {
        return "Usage: c2f assets:minify [--verificar] [--forcar] [--listar]\n\n" .
               "Generates <name>.min.js next to each own JavaScript file and records provenance in\n" .
               "gestor/assets/minify-manifest.json. The static controller prefers the minified file\n" .
               "when DEVELOPMENT_ENV is false.\n\n" .
               "Options:\n" .
               "  --verificar  Only report which derivatives are stale (exit 1 if any). Writes nothing.\n" .
               "  --forcar     Regenerate every derivative, even the ones already current.\n" .
               "  --listar     Show what would be processed, without writing anything.";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $terser = $this->rootPath . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR
            . '.bin' . DIRECTORY_SEPARATOR . 'terser';

        $verificar = $input->hasOption('verificar');

        if (!$verificar && !is_file($terser) && !is_file($terser . '.cmd')) {
            $output->warning('terser não encontrado em node_modules/.bin. Rode `npm install`. '
                . 'Sem ele o sistema continua servindo o JavaScript de autoria — apenas maior.');
            return 0;
        }

        $manifestoPath = $this->rootPath . '/gestor/assets/minify-manifest.json';
        $manifesto = is_file($manifestoPath)
            ? (array)json_decode((string)file_get_contents($manifestoPath), true)
            : [];

        $fontes = $this->descobrirFontes();

        $output->title('Conn2Flow — Minificação de JavaScript (build)');
        $output->write('  arquivos de autoria: ' . count($fontes));
        $output->write('');

        $gerados = 0;
        $atuais = 0;
        $stale = [];
        $falhas = [];
        $bytesAntes = 0;
        $bytesDepois = 0;
        $novoManifesto = [];

        foreach ($fontes as $relativo => $absoluto) {
            $conteudo = (string)file_get_contents($absoluto);
            $hash = sha1($conteudo);
            $destino = preg_replace('/\.js$/', '.min.js', $absoluto);

            $registrado = $manifesto[$relativo]['sha1'] ?? null;
            $coerente = is_file($destino) && $registrado === $hash;

            if ($verificar) {
                if (!$coerente) {
                    $stale[] = $relativo;
                }
                continue;
            }

            if ($coerente && !$input->hasOption('forcar')) {
                $atuais++;
                $novoManifesto[$relativo] = $manifesto[$relativo];
                $bytesAntes += strlen($conteudo);
                $bytesDepois += (int)filesize($destino);
                continue;
            }

            if ($input->hasOption('listar')) {
                $output->write('  [listar] ' . $relativo);
                continue;
            }

            $resultado = $this->minificar($absoluto, $destino);
            if ($resultado === null) {
                $falhas[] = $relativo;
                $output->write('  FALHA   ' . $relativo);
                continue;
            }

            $novoManifesto[$relativo] = ['sha1' => $hash, 'origem' => strlen($conteudo), 'minificado' => $resultado];
            $bytesAntes += strlen($conteudo);
            $bytesDepois += $resultado;
            $gerados++;

            $output->write(sprintf(
                '  OK      %-56s %7.1f KB -> %7.1f KB  (-%d%%)',
                $relativo,
                strlen($conteudo) / 1024,
                $resultado / 1024,
                strlen($conteudo) > 0 ? (int)round(100 - ($resultado / strlen($conteudo) * 100)) : 0
            ));
        }

        if ($verificar) {
            $output->write('derivados desatualizados: ' . count($stale));
            foreach (array_slice($stale, 0, 20) as $arquivo) {
                $output->write('  stale  ' . $arquivo);
            }

            return $stale === [] ? 0 : 1;
        }

        if ($input->hasOption('listar')) {
            return 0;
        }

        file_put_contents(
            $manifestoPath,
            json_encode($novoManifesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        $output->write('');
        $output->write(sprintf(
            'gerados: %d | ja coerentes: %d | falhas: %d | %.1f KB -> %.1f KB (-%d%%)',
            $gerados,
            $atuais,
            count($falhas),
            $bytesAntes / 1024,
            $bytesDepois / 1024,
            $bytesAntes > 0 ? (int)round(100 - ($bytesDepois / $bytesAntes * 100)) : 0
        ));

        if ($falhas !== []) {
            $output->warning('Sem derivado, estes continuam sendo servidos de autoria (maiores, mas '
                . 'corretos): ' . implode(', ', array_slice($falhas, 0, 8)));
        }

        return 0;
    }

    /**
     * JavaScript de AUTORIA do sistema: exclui terceiros e derivados.
     *
     * @return array<string, string> relativo => absoluto
     */
    private function descobrirFontes(): array
    {
        $encontrados = [];

        foreach (['gestor/assets', 'gestor/modulos'] as $base) {
            $raiz = $this->rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $base);
            if (!is_dir($raiz)) {
                continue;
            }

            $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz));
            foreach ($iterador as $arquivo) {
                if (!$arquivo->isFile() || strtolower($arquivo->getExtension()) !== 'js') {
                    continue;
                }

                $caminho = str_replace('\\', '/', $arquivo->getPathname());

                // Um derivado nunca vira fonte de outro derivado.
                if (substr($caminho, -7) === '.min.js') {
                    continue;
                }

                foreach (self::IGNORAR as $trecho) {
                    if (strpos($caminho, $trecho) !== false) {
                        continue 2;
                    }
                }

                $relativo = ltrim(str_replace(str_replace('\\', '/', $this->rootPath), '', $caminho), '/');
                $encontrados[$relativo] = $arquivo->getPathname();
            }
        }

        ksort($encontrados);

        return $encontrados;
    }

    /**
     * Minifica um arquivo e devolve o tamanho do resultado, ou null quando não deu para confiar.
     *
     * A saída só é gravada depois de passar no `node --check`: um minificador que produza sintaxe
     * inválida quebraria a tela sem nenhum sinal no servidor, e o arquivo de autoria — maior, porém
     * correto — é sempre a alternativa preferível.
     */
    private function minificar(string $origem, string $destino): ?int
    {
        $temp = tempnam(sys_get_temp_dir(), 'c2f-min-') . '.js';

        $comando = 'npx --no-install terser ' . escapeshellarg($origem)
            . ' --compress --mangle -o ' . escapeshellarg($temp) . ' 2>&1';

        $saida = [];
        $status = 1;
        @exec($comando, $saida, $status);

        if ($status !== 0 || !is_file($temp) || filesize($temp) === 0) {
            @unlink($temp);
            return null;
        }

        $checagem = [];
        $statusCheck = 1;
        @exec('node --check ' . escapeshellarg($temp) . ' 2>&1', $checagem, $statusCheck);

        if ($statusCheck !== 0) {
            @unlink($temp);
            return null;
        }

        $conteudo = (string)file_get_contents($temp);
        @unlink($temp);

        file_put_contents($destino, $conteudo);

        return strlen($conteudo);
    }
}
