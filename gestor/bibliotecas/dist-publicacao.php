<?php

require_once __DIR__ . '/recursos.php';

/**
 * Publica a árvore instalada do Gestor. O CLI também pode informar uma camada de projeto
 * separada em 'projeto', com a mesma precedência que ela teria na árvore instalada.
 */
function dist_publicar_completo(string $gestorRaiz, string $distPath, array $opcoes = []): array
{
    $res = ['criado' => false, 'verificados' => 0, 'publicados' => 0, 'atualizados' => 0,
        'inalterados' => 0, 'falhas' => 0, 'removidos' => 0, 'sem_fonte' => 0, 'bytes' => 0];
    $dist = rtrim($distPath, '/\\');
    $simular = !empty($opcoes['simular']);
    $relatar = $opcoes['relatar'] ?? static function (string $acao, string $caminho): void {};
    if ($dist === '' || is_link($dist) || !is_dir($gestorRaiz)
        || (file_exists($dist) && !is_dir($dist))) {
        $res['falhas']++;
        return $res;
    }

    $existe = is_dir($dist);
    $trava = null;
    if (!$simular) {
        if (!$existe && !@mkdir($dist, 0755, true) && !is_dir($dist)) {
            $res['falhas']++;
            return $res;
        }
        $res['criado'] = !$existe;
        $lock = $dist . '/.publish.lock';
        $trava = false;
        if (!is_link($lock) && (!file_exists($lock) || is_file($lock))) {
            $trava = @fopen($lock, 'c') ?: @fopen($lock, 'r');
        }
        if (!$trava || !flock($trava, LOCK_EX)) {
            if ($trava) fclose($trava);
            $res['falhas']++;
            return $res;
        }
    }

    try {
        $manifesto = $dist . '/' . RECURSOS_DIST_MANIFESTO;
        $htaccess = $dist . '/.htaccess';
        if (is_link($manifesto) || is_link($htaccess)) {
            $res['falhas']++;
            return $res;
        }
        $anterior = is_file($manifesto) ? json_decode((string)@file_get_contents($manifesto), true) : null;
        $minificar = (bool)($opcoes['preferir_minificado']
            ?? (!empty($opcoes['preservar_modo']) ? ($anterior['minificado'] ?? true) : true));
        $antigos = is_array($anterior['arquivos'] ?? null) ? $anterior['arquivos'] : [];
        $fontes = dist_publicacao_fontes($gestorRaiz, $opcoes['projeto'] ?? null, $minificar);
        $res['sem_fonte'] = count(array_diff_key($antigos, $fontes));
        $arquivos = array_filter($antigos, 'dist_publicacao_caminho_valido', ARRAY_FILTER_USE_KEY);

        if (!$simular && !is_file($htaccess)) {
            $config = dist_publicacao_htaccess();
            if (!dist_publicacao_gravar($htaccess, static fn($tmp) => @file_put_contents($tmp, $config) === strlen($config))) {
                $res['falhas']++;
                return $res;
            }
        }
        if (file_exists($manifesto) && !is_file($manifesto)) {
            $res['falhas']++;
            return $res;
        }

        foreach ($fontes as $url => [$raiz, $fonte]) {
            $res['verificados']++;
            $origem = $raiz . '/' . $fonte;
            $hash = @sha1_file($origem);
            $bytes = @filesize($origem);
            $destino = $dist . '/' . $url;
            $seguro = dist_publicacao_destino_seguro($dist, $url);
            $publicado = $seguro && is_file($destino);
            if (!$seguro || $hash === false || $bytes === false) {
                $res['falhas']++;
                $relatar('falha', $url);
                continue;
            }

            if ($publicado && @sha1_file($destino) === $hash) {
                $res['inalterados']++;
            } else {
                if (!$simular && !dist_publicacao_gravar($destino, static function ($tmp) use ($origem, $hash) {
                    return @copy($origem, $tmp) && @sha1_file($tmp) === $hash;
                })) {
                    $res['falhas']++;
                    $relatar('falha', $url);
                    continue;
                }
                $res[$publicado ? 'atualizados' : 'publicados']++;
                $relatar('publicar', $url);
            }
            $arquivos[$url] = ['sha1' => $hash, 'bytes' => $bytes, 'v' => substr($hash, 0, 16), 'fonte' => $fonte];
            $res['bytes'] += $bytes;
        }

        // A limpeza explícita alcança apenas arquivos gerenciados por um manifesto anterior.
        if (!empty($opcoes['limpar']) && !$res['falhas']) {
            foreach (array_keys(array_diff_key($arquivos, $fontes)) as $url) {
                $destino = $dist . '/' . $url;
                if (!dist_publicacao_destino_seguro($dist, $url)) {
                    $res['falhas']++;
                    $relatar('falha', $url);
                    continue;
                }
                if (is_file($destino)) {
                    if (@sha1_file($destino) !== ($arquivos[$url]['sha1'] ?? null)) continue;
                    if (!$simular && !@unlink($destino)) {
                        $res['falhas']++;
                        $relatar('falha', $url);
                        continue;
                    }
                    $res['removidos']++;
                    $relatar('remover', $url);
                }
                unset($arquivos[$url]);
            }
        }

        ksort($arquivos);
        if (!$simular && (($anterior['versao'] ?? null) !== 1
                || ($anterior['minificado'] ?? null) !== $minificar || $antigos !== $arquivos
                || !isset($anterior['gerado_em']))) {
            $json = json_encode(['versao' => 1, 'gerado_em' => date('c'), 'minificado' => $minificar,
                'arquivos' => (object)$arquivos], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false || !dist_publicacao_gravar($manifesto, static fn($tmp) => @file_put_contents($tmp, $json . "\n") === strlen($json) + 1)) {
                $res['falhas']++;
                $relatar('falha', RECURSOS_DIST_MANIFESTO);
            }
        }
    } catch (UnexpectedValueException $e) {
        $res['falhas']++;
    } finally {
        if ($trava) fclose($trava);
    }

    return $res;
}

/** Indexa apenas arquivos que têm URL no contrato do controlador estático. */
function dist_publicacao_fontes(string $gestor, ?string $projeto, bool $minificar): array
{
    $extensoes = ['js', 'css', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'ico', 'bmp', 'avif',
        'woff', 'woff2', 'ttf', 'otf', 'eot', 'mp4', 'webm', 'ogg', 'mp3', 'webmanifest'];
    $naoPublicar = ['asset-versions.json', 'minify-manifest.json'];
    $camadas = [];
    foreach (array_unique(array_filter([$gestor, $projeto])) as $raiz) {
        $raiz = rtrim($raiz, '/\\');
        foreach (['assets', 'modulos'] as $pasta) {
            $base = $raiz . '/' . $pasta;
            if (is_link($base) || !is_dir($base)) continue;
            $diretorio = new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS);
            $filtro = new RecursiveCallbackFilterIterator($diretorio, static function ($item) {
                return !$item->isLink() && !str_starts_with($item->getFilename(), '.');
            });
            $itens = new RecursiveIteratorIterator($filtro);
            if ($pasta === 'modulos') $itens->setMaxDepth(1);
            foreach ($itens as $item) {
                $nome = $item->getFilename();
                if (!$item->isFile() || in_array($nome, $naoPublicar, true)
                    || !in_array(strtolower($item->getExtension()), $extensoes, true)) continue;

                $fonte = $pasta . '/' . str_replace(DIRECTORY_SEPARATOR, '/', substr($item->getPathname(), strlen($base) + 1));
                if (dist_publicacao_caminho_valido($fonte)) $camadas[$fonte] = $raiz;
            }
        }
    }

    $fontes = [];
    foreach ($camadas as $fonte => $raiz) {
        $derivado = str_ends_with($fonte, '.min.js');
        // Um minificado sem o respectivo arquivo de autoria é um asset independente.
        if ($derivado && isset($camadas[substr($fonte, 0, -7) . '.js'])) continue;
        $url = recursos_dist_mapear_fonte($fonte);
        if (!dist_publicacao_caminho_valido($url)) continue;

        if ($minificar && !$derivado && str_ends_with($fonte, '.js')) {
            $min = substr($fonte, 0, -3) . '.min.js';
            if (isset($camadas[$min]) && @filesize($camadas[$min] . '/' . $min) > 0) {
                $raiz = $camadas[$min];
                $fonte = $min;
            }
        }
        $fontes[$url] = [$raiz, $fonte];
    }
    ksort($fontes);
    return $fontes;
}

function dist_publicacao_caminho_valido(string $caminho): bool
{
    return $caminho !== '' && recursos_caminho_normalizar($caminho) === $caminho
        && !preg_match('~(?:^|/)\.|[:%\x00-\x1f\x7f]|[. ](?:/|$)|\.(?:php\d*|phtml|phar|cgi|fcgi|shtml)(?:[./]|$)~i', $caminho)
        && preg_match('//u', $caminho) === 1;
}

function dist_publicacao_destino_seguro(string $dist, string $url): bool
{
    $partes = explode('/', $url);
    $ultimo = array_pop($partes);
    foreach ($partes as $parte) {
        $dist .= '/' . $parte;
        if (is_link($dist) || (file_exists($dist) && !is_dir($dist))) return false;
    }
    $destino = $dist . '/' . $ultimo;
    return !is_link($destino) && (!file_exists($destino) || is_file($destino));
}

/** Escreve no mesmo diretório para que o rename seja atômico. */
function dist_publicacao_gravar(string $destino, callable $escrever): bool
{
    $dir = dirname($destino);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) return false;
    $tmp = $dir . '/.publish-' . bin2hex(random_bytes(12)) . '.tmp';
    if ($escrever($tmp) && @rename($tmp, $destino)) return true;
    @unlink($tmp);
    return false;
}

function dist_publicacao_htaccess(): string
{
    return <<<'APACHE'
# Conn2Flow - cache dos assets versionados por hash.
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresDefault "access plus 1 year"
</IfModule>
<IfModule mod_headers.c>
    Header set Cache-Control "public, max-age=31536000, immutable"
</IfModule>
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
Options -Indexes -ExecCGI
<FilesMatch "^\.">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

APACHE;
}
