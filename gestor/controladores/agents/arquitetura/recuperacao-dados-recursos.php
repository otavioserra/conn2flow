<?php
/**
 * Descompilador de Dados de Recursos (Pull System / Engenharia Reversa) - req-058 / BATCH-058
 * ------------------------------------------------------------------------------------------
 * Contraparte INVERSA do compilador atualizacao-dados-recursos.php. Enquanto o compilador varre
 * arquivos físicos (HTML/CSS/MD) + metadados e gera os <PascalCase>Data.json, este script faz o
 * caminho de volta: recebe um dump bruto (raw) de <PascalCase>Data.json (extraído do banco via
 * `_api/project/recover`) e decompõe cada registro de volta em:
 *   - arquivos físicos para os campos field_types do tipo "file:<ext>";
 *   - metadados saneados (JSON externo por idioma, ou inline em resources->idioma->tabela).
 *
 * Simetria com o compilador (CRÍTICA para round-trip estável): este descompilador é o reverso
 * EXATO de processarRegistroDinamico()/lerMetadadosDinamicos(). Em particular, os arquivos
 * file:<ext> vão para
 *   <base_dir>/<lang>/<resources_dir|tabela>/<id>/<id>.<ext>
 * usando uma subpasta por recurso, no mesmo padrão dos recursos fixos do core.
 *
 * Saneamento de cada registro (reverso das injeções do compilador):
 *   - decodifica campos field_types "json" (string JSON do banco -> array/objeto);
 *   - extrai campos field_types "file:<ext>" para arquivo e remove a chave do metadado;
 *   - remove colunas geradas no build/banco: versao, checksum, user_modified, project, a PK
 *     auto-increment (id_numerico) e a coluna de idioma (language/linguagem_codigo, derivada da
 *     pasta); remove status quando 'A' (default injetado) e module/modulo quando == módulo dono.
 *
 * Argumentos CLI:
 *   --source-dir=path   : (obrigatório) pasta com os <PascalCase>Data.json extraídos do ZIP da API.
 *   --project-path=path : (opcional) raiz de um projeto; sem isso, opera sobre o núcleo (gestor/).
 *
 * Estrutura: parse de args -> coleta de configs (tables_config.json/project_tables_config.json + módulos) -> para cada
 * *Data.json com sync_resources, descompila registros, grava arquivos físicos e metadados.
 */

declare(strict_types=1);

// ========================= UTILITÁRIOS DE BAIXO NÍVEL =========================

/** Remove BOM UTF-8 do início do conteúdo, quando presente. */
function rdr_strip_bom(?string $content): ?string {
    if ($content === null || $content === '') return $content;
    if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) return substr($content, 3);
    return $content;
}

/** Lê JSON (array associativo) removendo BOM; retorna null em ausência/erro. */
function rdr_json_read(string $path): ?array {
    if (!is_file($path)) return null;
    $c = rdr_strip_bom(file_get_contents($path));
    $d = json_decode((string)$c, true);
    return is_array($d) ? $d : null;
}

/**
 * Grava JSON formatado (UTF-8 sem BOM, legível para versionamento). Cria o diretório-pai.
 * Usa UNESCAPED_SLASHES para manter URLs e caminhos legíveis nos metadados de recurso.
 */
function rdr_json_write(string $path, $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    return file_put_contents($path, $json) !== false;
}

/** Grava um arquivo físico de recurso (UTF-8 sem BOM), criando o diretório-pai. */
function rdr_write_file(string $path, string $content): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return file_put_contents($path, rdr_strip_bom($content)) !== false;
}

/** Converte NomeTabelaPascalCaseData.json -> nome_da_tabela (snake_case). */
function rdr_data_file_to_table(string $file): string {
    $base = preg_replace('/Data\.json$/', '', basename($file));
    if ($base === '') return '';
    if (strpos($base, '_') !== false) return strtolower($base);
    $snake = preg_replace('/(?<!^)([A-Z])/', '_$1', $base);
    return strtolower($snake);
}

/** Log simples respeitando modo silencioso (testes definem $GLOBALS['RDR_SILENT']). */
function rdr_log(string $msg): void {
    if (PHP_SAPI === 'cli' && empty($GLOBALS['RDR_SILENT'])) echo $msg . PHP_EOL;
}

// ========================= COLETA DE CONFIGURAÇÕES =========================

/**
 * Normaliza um bloco "tabela" (global ou de módulo) para as configs de sincronização reversa.
 * Espelha normalizarConfigTabela() do compilador: a sub-chave "config" pode ser um OBJETO único
 * (1 tabela) ou ARRAY de objetos (N tabelas); o nome vem de config.tabela_nome ou do bloco.
 * Retorna SEMPRE uma lista de configs (apenas as que interessam à descompilação).
 *
 * @param array       $meta       Bloco "tabela" do JSON.
 * @param string      $scope      'global' ou 'module'.
 * @param string|null $modulo     id do módulo (scope=module).
 * @param string      $baseDir    pasta-base de recursos (resources do núcleo ou do módulo).
 * @param string      $sourceFile caminho do JSON raiz (para escrita inline de metadados).
 */
function rdr_normalizar_config(array $meta, string $scope, ?string $modulo, string $baseDir, string $sourceFile): array {
    $nomeBloco = (isset($meta['nome']) && is_string($meta['nome'])) ? $meta['nome'] : null;
    $configRaw = $meta['config'] ?? null;
    if (!is_array($configRaw)) return [];
    $configs = array_is_list($configRaw) ? $configRaw : [$configRaw];

    $out = [];
    foreach ($configs as $config) {
        if (!is_array($config)) continue;
        $nome = (isset($config['tabela_nome']) && is_string($config['tabela_nome']) && $config['tabela_nome'] !== '')
            ? $config['tabela_nome'] : $nomeBloco;
        if (!is_string($nome) || $nome === '') continue;
        $mesmoBloco = ($nome === $nomeBloco);

        $resourcesDir = (isset($config['resources_dir']) && is_string($config['resources_dir']) && $config['resources_dir'] !== '')
            ? $config['resources_dir'] : null;
        $metadataFile = (isset($config['metadata_file']) && is_string($config['metadata_file']) && $config['metadata_file'] !== '')
            ? $config['metadata_file'] : null;
        $fieldTypes = (isset($config['field_types']) && is_array($config['field_types'])) ? $config['field_types'] : [];
        $scopeOverride = (isset($config['scope']) && is_string($config['scope'])) ? $config['scope'] : null;
        $moduloOverride = (isset($config['modulo']) && is_string($config['modulo']) && $config['modulo'] !== '')
            ? $config['modulo'] : null;
        $effectiveScope = ($scopeOverride === 'module' && $moduloOverride !== null) ? 'module' : $scope;
        $effectiveModulo = ($effectiveScope === 'module') ? ($moduloOverride ?? $modulo) : null;
        $gestorRoot = $scope === 'module' ? dirname(dirname(dirname($baseDir))) : dirname($baseDir);
        $effectiveBaseDir = ($scopeOverride === 'module' && $moduloOverride !== null)
            ? $gestorRoot . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . $moduloOverride . DIRECTORY_SEPARATOR . 'resources'
            : $baseDir;

        $out[] = [
            'nome' => $nome,
            'id' => $config['id'] ?? ($mesmoBloco ? ($meta['id'] ?? 'id') : 'id'),
            'id_numerico' => $config['id_numerico'] ?? ($mesmoBloco ? ($meta['id_numerico'] ?? null) : null),
            'strategy' => ($config['strategy'] ?? 'pk') === 'natural_key' ? 'natural_key' : 'pk',
            'natural_key_columns' => array_values(array_filter((array)($config['natural_key_columns'] ?? []), 'is_string')),
            'sync_resources' => !empty($config['sync_resources']),
            'resources_dir' => $resourcesDir,
            'metadata_file' => $metadataFile,
            'field_types' => $fieldTypes,
            'scope' => $effectiveScope,
            'modulo' => $effectiveModulo,
            'base_dir' => $effectiveBaseDir,
            'source_file' => $sourceFile,
        ];
    }
    return $out;
}

/**
 * Coleta as configs de tabela sync_resources do núcleo/projeto: tables_config.json global,
 * project_tables_config.json do projeto e os blocos "tabela.config" de cada módulo.
 * Registros posteriores sobrescrevem anteriores por nome de tabela.
 * Retorna um mapa nome_tabela => config.
 */
function rdr_coletar_configs(string $gestorDir): array {
    $configs = [];

    // 1) Globais/projeto: <gestorDir>/resources/tables_config.json e project_tables_config.json.
    $resourcesDir = $gestorDir . DIRECTORY_SEPARATOR . 'resources';
    foreach (['tables_config.json', 'project_tables_config.json'] as $configFileName) {
        $globalFile = $resourcesDir . DIRECTORY_SEPARATOR . $configFileName;
        $g = rdr_json_read($globalFile);
        if (is_array($g) && isset($g['tabelas']) && is_array($g['tabelas'])) {
            foreach ($g['tabelas'] as $meta) {
                if (!is_array($meta)) continue;
                foreach (rdr_normalizar_config($meta, 'global', null, $resourcesDir, $globalFile) as $cfg) {
                    if ($cfg['sync_resources']) $configs[$cfg['nome']] = $cfg;
                }
            }
        }
    }

    // 2) Módulos: <gestorDir>/modulos/<mod>/<mod>.json (bloco "tabela.config")
    $modulosDir = $gestorDir . DIRECTORY_SEPARATOR . 'modulos';
    if (is_dir($modulosDir)) {
        foreach (glob($modulosDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $modPath) {
            $modId = basename($modPath);
            $data = rdr_json_read($modPath . DIRECTORY_SEPARATOR . $modId . '.json');
            if (!is_array($data)) continue;
            $tabela = $data['tabela'] ?? null;
            if (!is_array($tabela) || empty($tabela['config'])) continue;
            $baseDir = $modPath . DIRECTORY_SEPARATOR . 'resources';
            $sourceFile = $modPath . DIRECTORY_SEPARATOR . $modId . '.json';
            foreach (rdr_normalizar_config($tabela, 'module', $modId, $baseDir, $sourceFile) as $cfg) {
                if ($cfg['sync_resources']) $configs[$cfg['nome']] = $cfg;
            }
        }
    }

    return $configs;
}

// ========================= RESOLUÇÃO DE CAMINHOS =========================

/** Idioma do registro (language ou linguagem_codigo legado; fallback pt-br para tabelas sem idioma). */
function rdr_resolve_lang(array $rec): ?string {
    foreach (['language', 'linguagem_codigo'] as $k) {
        if (isset($rec[$k]) && $rec[$k] !== '') return (string)$rec[$k];
    }
    return 'pt-br';
}

/**
 * Pasta dos arquivos físicos (file:<ext>) de uma tabela/idioma: espelha processarRegistroDinamico().
 *   <base_dir>/<lang>/<resources_dir|tabela>
 */
function rdr_files_dir(array $cfg, string $lang): string {
    $resDir = $cfg['resources_dir'] ?? $cfg['nome'];
    return $cfg['base_dir'] . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $resDir;
}

/**
 * Caminho do metadado externo de uma tabela/idioma: espelha lerMetadadosDinamicos().
 * Retorna null quando a tabela usa metadados inline (sem metadata_file).
 */
function rdr_metadata_path(array $cfg, string $lang): ?string {
    $metaFile = $cfg['metadata_file'] ?? null;
    if (!$metaFile) return null;
    $baseDir = $cfg['base_dir'];
    $resDirExplicit = $cfg['resources_dir'] ?? null;
    if ($cfg['scope'] === 'module') {
        $resDir = $resDirExplicit ?? $cfg['nome'];
        return $baseDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $resDir . DIRECTORY_SEPARATOR . $metaFile;
    }
    // global
    return $resDirExplicit
        ? $baseDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $resDirExplicit . DIRECTORY_SEPARATOR . $metaFile
        : $baseDir . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $metaFile;
}

// ========================= DESCOMPILAÇÃO DE REGISTRO =========================

/**
 * Remove de um metadado as colunas geradas no build/banco, reverso das injeções do compilador.
 * Mantém o registro enxuto para versionamento limpo no Git.
 */
function rdr_sanear(array $meta, array $cfg, string $lang): array {
    // Colunas de controle de build/banco.
    unset($meta['versao'], $meta['checksum'], $meta['user_modified'], $meta['project']);
    // PK auto-increment declarada no contrato.
    if (!empty($cfg['id_numerico']) && is_string($cfg['id_numerico'])) {
        unset($meta[$cfg['id_numerico']]);
    }
    // Idioma é dimensão de roteamento (pasta/idioma) — não persiste no metadado.
    unset($meta['language'], $meta['linguagem_codigo']);
    // status='A' é default injetado pelo compilador.
    if (isset($meta['status']) && $meta['status'] === 'A') {
        unset($meta['status']);
    }
    // module/modulo é o módulo dono quando está na chave natural — injetado pelo compilador.
    if ($cfg['scope'] === 'module' && !empty($cfg['modulo'])) {
        foreach (['module', 'modulo'] as $mc) {
            if (in_array($mc, $cfg['natural_key_columns'], true)
                && isset($meta[$mc]) && (string)$meta[$mc] === (string)$cfg['modulo']) {
                unset($meta[$mc]);
            }
        }
    }
    return $meta;
}

/**
 * Descompila um único registro bruto: decodifica campos "json", extrai campos "file:<ext>" para
 * arquivos físicos (acumulados, não gravados aqui) e retorna o metadado saneado.
 *
 * Campos "file:<ext>" ausentes, nulos ou vazios (registro que usa o template padrão sem
 * customização) NÃO geram arquivo em branco; a omissão é registrada via log RDR_DEBUG_FILE_EMPTY.
 *
 * @return array{meta: array, files: array<string,string>} 'files' mapeia caminho absoluto -> conteúdo.
 */
function rdr_descompilar_registro(array $rec, array $cfg, string $lang): array {
    $fieldTypes = is_array($cfg['field_types'] ?? null) ? $cfg['field_types'] : [];
    $id = isset($rec['id']) ? (string)$rec['id'] : '';
    $filesDir = rdr_files_dir($cfg, $lang);

    $meta = $rec;
    $files = [];

    foreach ($fieldTypes as $campo => $tipo) {
        if (!is_string($tipo)) continue;

        // Campo JSON serializado no banco -> array/objeto estruturado.
        if ($tipo === 'json') {
            if (isset($meta[$campo]) && is_string($meta[$campo]) && $meta[$campo] !== '') {
                $decoded = json_decode($meta[$campo], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $meta[$campo] = $decoded;
                }
            }
            continue;
        }

        // Campo file:<ext> -> arquivo físico; a chave sai do metadado.
        if (strncmp($tipo, 'file:', 5) === 0) {
            $ext = substr($tipo, 5);
            if ($ext !== '' && $id !== '') {
                $bruto = array_key_exists($campo, $rec) ? $rec[$campo] : null;
                $conteudo = $bruto === null ? '' : (string)rdr_strip_bom((string)$bruto);
                if ($conteudo === '') {
                    // Campo físico ausente/nulo/vazio: típico de registro que usa o template padrão
                    // do sistema sem customização (o html/css do registro fica nulo no banco). Não
                    // gerar arquivo em branco; registrar o motivo para tornar "arquivos=0" explicável.
                    rdr_log("RDR_DEBUG_FILE_EMPTY tabela={$cfg['nome']} id=$id campo=$campo");
                } else {
                    $fpath = $filesDir . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . $id . '.' . $ext;
                    $files[$fpath] = $conteudo;
                }
            }
            unset($meta[$campo]);
        }
    }

    $meta = rdr_sanear($meta, $cfg, $lang);
    return ['meta' => $meta, 'files' => $files];
}

// ========================= ESCRITA DE METADADOS =========================

/**
 * Escreve a lista de metadados de uma tabela/idioma. Caso metadata_file esteja configurado, grava
 * o JSON externo; caso contrário, atualiza inline o JSON raiz (project_tables_config.json/<mod>.json) na
 * chave resources -> idioma -> tabela.
 */
function rdr_escrever_metadados(array $cfg, string $lang, array $lista): bool {
    $externo = rdr_metadata_path($cfg, $lang);
    if ($externo !== null) {
        return rdr_json_write($externo, array_values($lista));
    }
    // Inline: atualizar o JSON raiz preservando o restante do arquivo.
    $sourceFile = $cfg['source_file'];
    $root = rdr_json_read($sourceFile);
    if (!is_array($root)) $root = [];
    if (!isset($root['resources']) || !is_array($root['resources'])) $root['resources'] = [];
    if (!isset($root['resources'][$lang]) || !is_array($root['resources'][$lang])) $root['resources'][$lang] = [];
    $root['resources'][$lang][$cfg['nome']] = array_values($lista);
    return rdr_json_write($sourceFile, $root);
}

// ========================= SINCRONIZACAO DE CONTENTS =========================

/** Caminho relativo normalizado para logs de contents. */
function rdr_contents_rel(string $baseDir, string $path): string {
    return ltrim(str_replace('\\', '/', substr($path, strlen(rtrim($baseDir, '/\\')))), '/');
}

/**
 * Copia contents/ do pacote de recuperacao respeitando MD5 e timestamps.
 *
 * @return array{copiados:int,pulados:int,conflitos:array<int,string>}
 */
function rdr_sincronizar_contents(string $sourceDir, string $gestorDir): array {
    $remoteContents = rtrim($sourceDir, '/\\') . DIRECTORY_SEPARATOR . 'contents';
    $localContents = rtrim($gestorDir, '/\\') . DIRECTORY_SEPARATOR . 'contents';
    $stats = ['copiados' => 0, 'pulados' => 0, 'conflitos' => []];
    if (!is_dir($remoteContents)) {
        return $stats;
    }
    if (!is_dir($localContents)) {
        @mkdir($localContents, 0775, true);
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($remoteContents, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        if (!$item->isFile()) continue;
        $remotePath = $item->getPathname();
        $rel = rdr_contents_rel($remoteContents, $remotePath);
        if ($rel === '') continue;
        $localPath = $localContents . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $remoteMtime = $item->getMTime();

        if (is_file($localPath)) {
            if (md5_file($remotePath) === md5_file($localPath)) {
                $stats['pulados']++;
                continue;
            }
            $localMtime = filemtime($localPath) ?: 0;
            if ($remoteMtime <= $localMtime) {
                $stats['pulados']++;
                $stats['conflitos'][] = $rel;
                rdr_log("RDR_CONFLITO arquivo=$rel (local mais recente, remoto diferente; pulado)");
                continue;
            }
        }

        $dir = dirname($localPath);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        if (@copy($remotePath, $localPath)) {
            @touch($localPath, $remoteMtime);
            $stats['copiados']++;
        }
    }

    if ($stats['conflitos']) {
        rdr_log('RDR_CONFLITOS total=' . count($stats['conflitos']));
        foreach ($stats['conflitos'] as $rel) {
            rdr_log('RDR_CONFLITOS arquivo=' . $rel);
        }
    }

    return $stats;
}

// ========================= ORQUESTRAÇÃO =========================

/**
 * Processa todos os <PascalCase>Data.json de um diretório de origem, descompilando as tabelas que
 * possuem configuração sync_resources. Grava arquivos físicos e metadados (externos/inline).
 *
 * @return array Estatísticas por tabela e listas de tabelas ignoradas.
 */
function rdr_processar(string $sourceDir, string $gestorDir): array {
    $configs = rdr_coletar_configs($gestorDir);
    $stats = ['tabelas' => [], 'ignoradas' => [], 'arquivos' => 0, 'registros' => 0, 'contents' => ['copiados' => 0, 'pulados' => 0, 'conflitos' => []]];

    $sourceDir = rtrim($sourceDir, '/\\');
    $stats['contents'] = rdr_sincronizar_contents($sourceDir, $gestorDir);
    $dataFiles = glob($sourceDir . DIRECTORY_SEPARATOR . '*Data.json') ?: [];
    if (!$dataFiles) {
        rdr_log('RDR_SEM_DATA_JSON em ' . $sourceDir);
        return $stats;
    }

    foreach ($dataFiles as $dataFile) {
        $tabela = rdr_data_file_to_table($dataFile);
        if (!isset($configs[$tabela])) {
            $stats['ignoradas'][] = $tabela;
            rdr_log("RDR_SKIP tabela=$tabela (sem config sync_resources)");
            continue;
        }
        $cfg = $configs[$tabela];
        $registros = rdr_json_read($dataFile);
        if (!is_array($registros)) {
            rdr_log("RDR_SKIP tabela=$tabela (Data.json inválido)");
            continue;
        }

        // Agrupa metadados por idioma e grava arquivos físicos de imediato.
        $porLang = [];
        $arquivos = 0;
        foreach ($registros as $rec) {
            if (!is_array($rec)) continue;
            $lang = rdr_resolve_lang($rec);
            if ($lang === null) {
                rdr_log("RDR_REGISTRO_SEM_LANG tabela=$tabela id=" . ($rec['id'] ?? '?'));
                continue;
            }
            $res = rdr_descompilar_registro($rec, $cfg, $lang);
            foreach ($res['files'] as $fpath => $conteudo) {
                if (rdr_write_file($fpath, $conteudo)) $arquivos++;
            }
            $porLang[$lang][] = $res['meta'];
        }

        // Escreve os metadados por idioma.
        foreach ($porLang as $lang => $lista) {
            rdr_escrever_metadados($cfg, $lang, $lista);
        }

        $total = array_sum(array_map('count', $porLang));
        $stats['tabelas'][$tabela] = ['registros' => $total, 'arquivos' => $arquivos, 'idiomas' => array_keys($porLang)];
        $stats['arquivos'] += $arquivos;
        $stats['registros'] += $total;
        rdr_log("RDR_OK tabela=$tabela registros=$total arquivos=$arquivos langs=" . implode(',', array_keys($porLang)));
    }

    return $stats;
}

/** Resolve a raiz do gestor/projeto a partir das opções (--project-path) ou do núcleo. */
function rdr_resolver_gestor_dir(array $opts): string {
    if (!empty($opts['project-path']) && is_string($opts['project-path'])) {
        $p = realpath($opts['project-path']);
        if ($p !== false) return rtrim($p, '/\\');
        return rtrim($opts['project-path'], '/\\');
    }
    $systemRoot = realpath(__DIR__ . '/../../../../');
    return $systemRoot . DIRECTORY_SEPARATOR . 'gestor';
}

/** Parser simples de argumentos CLI no padrão --chave=valor / --flag. */
function rdr_parse_args(array $argv): array {
    $out = [];
    foreach ($argv as $a) {
        if (preg_match('/^--([^=]+)=(.*)$/', $a, $m)) { $out[$m[1]] = $m[2]; }
        elseif (substr($a, 0, 2) === '--') { $out[substr($a, 2)] = true; }
    }
    return $out;
}

/** Ponto de entrada principal: valida --source-dir e dispara o processamento. */
function rdr_main(array $opts): int {
    $sourceDir = $opts['source-dir'] ?? null;
    if (!is_string($sourceDir) || $sourceDir === '') {
        fwrite(STDERR, "Uso: php recuperacao-dados-recursos.php --source-dir=<pasta> [--project-path=<projeto>]\n");
        return 1;
    }
    if (!is_dir($sourceDir)) {
        fwrite(STDERR, "Diretório de origem não encontrado: $sourceDir\n");
        return 1;
    }
    $gestorDir = rdr_resolver_gestor_dir($opts);
    rdr_log("RDR_START source=$sourceDir gestor=$gestorDir");
    $stats = rdr_processar($sourceDir, $gestorDir);
    rdr_log("RDR_DONE tabelas=" . count($stats['tabelas']) . " registros={$stats['registros']} arquivos={$stats['arquivos']} contents_copiados={$stats['contents']['copiados']} contents_conflitos=" . count($stats['contents']['conflitos']));
    return 0;
}

// Guard de autorun: permite incluir este arquivo em testes sem disparar a execução.
if (!defined('SDD_NO_AUTORUN')) {
    exit(rdr_main(rdr_parse_args($argv ?? [])));
}
