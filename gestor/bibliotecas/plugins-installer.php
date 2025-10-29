<?php
/**
 * Biblioteca de Instalação e Atualização de Plugins
 *
 * Sistema completo para gerenciamento de plugins com suporte a:
 * - Download de repositórios GitHub (públicos e privados)
 * - Instalação local via arquivo ou diretório
 * - Validação de manifesto e dependências
 * - Migração de banco de dados e arquivos de dados
 * - Versionamento e atualização automática
 *
 * @package Conn2Flow
 * @subpackage Plugins
 * @version 1.0 (Fase 1)
 */

require_once __DIR__ . '/banco.php';
require_once __DIR__ . '/plugins-consts.php';
require_once __DIR__ . '/../modulos/admin-plugins/admin-plugins.php';

/**
 * Normaliza um slug de plugin para formato seguro.
 *
 * Remove caracteres especiais e converte para minúsculas,
 * mantendo apenas letras, números, hífens e underscores.
 *
 * @param string $slug Slug original do plugin.
 * @return string Slug normalizado (ex: "My-Plugin_123" → "my-plugin_123").
 */
function plugin_normalize_slug(string $slug): string { return strtolower(preg_replace('/[^a-zA-Z0-9_-]+/','-', $slug)); }

/**
 * Retorna o caminho raiz do diretório do gestor.
 *
 * @return string Caminho absoluto para o diretório raiz.
 */
function plugin_base_root(): string { return dirname(__DIR__) . '/'; }

/**
 * Retorna o caminho de staging temporário para um plugin.
 *
 * @param string $slug Slug do plugin.
 * @return string Caminho completo para temp/plugins/{slug}.
 */
function plugin_staging_path(string $slug): string { return plugin_base_root() . 'temp/plugins/' . $slug; }

/**
 * Retorna o caminho final de instalação de um plugin.
 *
 * @param string $slug Slug do plugin.
 * @return string Caminho completo para plugins/{slug}.
 */
function plugin_final_path(string $slug): string { return plugin_base_root() . 'plugins/' . $slug; }

/**
 * Retorna o diretório de destino para arquivos *Data.json do plugin.
 *
 * @param string $slug Slug do plugin.
 * @return string Caminho completo para db/data/plugins/{slug}.
 */
function plugin_datajson_dest_dir(string $slug): string { return plugin_base_root() . 'db/data/plugins/' . $slug; }

/**
 * Cria um diretório recursivamente se não existir.
 *
 * @param string $path Caminho do diretório a criar.
 * @return void
 */
function plugin_safe_mkdir(string $path): void { if(!is_dir($path)) mkdir($path, 0777, true); }

/**
 * Remove um diretório recursivamente incluindo todo seu conteúdo.
 *
 * @param string $dir Caminho do diretório a remover.
 * @return void
 */
function plugin_remove_dir(string $dir): void { if(!is_dir($dir)) return; $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $f){ $f->isDir()? rmdir($f->getPathname()):unlink($f->getPathname()); } rmdir($dir); }

/**
 * Calcula o checksum SHA-256 de um arquivo.
 *
 * @param string $file Caminho do arquivo.
 * @return string|null Hash SHA-256 ou null se o arquivo não existir.
 */
function plugin_compute_checksum(string $file): ?string { return file_exists($file)? hash_file('sha256',$file): null; }

/**
 * Detecta se o script está sendo executado via CLI ou web.
 *
 * Verifica o PHP SAPI para determinar o contexto de execução,
 * útil para adaptar comportamento de output e logging.
 *
 * @return bool True se CLI, false se web.
 */
function plugin_is_cli_context(): bool {
    return php_sapi_name() === 'cli';
}

/**
 * Converte nome de arquivo *Data.json para nome de tabela snake_case.
 *
 * Transforma nomes em CamelCase para snake_case seguindo convenções SQL.
 * Exemplos:
 * - ModulosData.json → modulos
 * - HostsConfiguracoesData.json → hosts_configuracoes
 *
 * @param string $file Nome do arquivo *Data.json.
 * @return string Nome da tabela em snake_case.
 */
function tabelaFromDataFile(string $file): string {
    // Remove sufixo "Data.json" para obter nome base
    $base = preg_replace('/Data\.json$/', '', basename($file));
    if ($base === '') return '';
    
    // Se já contém underscore, apenas converte para minúsculas
    if (strpos($base, '_') !== false) {
        return strtolower($base);
    }
    
    // Inserir underscore antes de cada letra maiúscula que não é inicial
    $snake = preg_replace('/(?<!^)([A-Z])/', '_$1', $base);
    return strtolower($snake);
}

/**
 * Lê e decodifica um arquivo JSON.
 *
 * Realiza validação do JSON e adiciona erros ao array de erros se houver falhas.
 *
 * @param string $path Caminho do arquivo JSON.
 * @param array &$errors Array de erros (passado por referência).
 * @return array|null Dados decodificados ou null em caso de erro.
 */
function plugin_read_json(string $path, array &$errors): ?array {
    if(!file_exists($path)){ $errors[] = "Arquivo não encontrado: $path"; return null; }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    if(json_last_error() !== JSON_ERROR_NONE){ $errors[] = "JSON inválido em $path: ".json_last_error_msg(); return null; }
    return $data;
}

/**
 * Valida campos obrigatórios e formato do manifest.json do plugin.
 *
 * Verifica:
 * - Campos obrigatórios: id, name, version
 * - Formato da versão: semantic versioning (x.y.z)
 * - Formato do ID: lowercase, números, hífen ou underscore
 *
 * @param array $manifest Dados do manifest.json decodificados.
 * @param array &$errors Array de erros (passado por referência).
 * @return bool True se válido, false caso contrário.
 */
function plugin_validate_manifest(array $manifest, array &$errors): bool {
    $required = ['id','name','version'];
    foreach($required as $r){ if(empty($manifest[$r])) $errors[] = "Missing required field: $r"; }
    if(!empty($manifest['version']) && !preg_match('/^\d+\.\d+\.\d+$/',$manifest['version'])) { $errors[] = 'Invalid version (use semantic x.y.z)'; }
    // ID prefix (guidance – does not block Phase 1)
    if(isset($manifest['id']) && !preg_match('/^[a-z0-9][a-z0-9_-]*$/',$manifest['id'])) { $errors[] = 'Invalid plugin ID (use lowercase, numbers, hyphen or underscore)'; }
    return empty($errors);
}

/**
 * Busca credenciais de token para acesso a repositórios privados.
 *
 * Aceita dois formatos:
 * 1. Token direto do GitHub (ghp_, github_pat_, gho_, ghu_, ghs_)
 * 2. Nome de referência para buscar em variável de ambiente PLUGIN_TOKEN_{REF}
 *
 * @param string|null $credRef Referência ou token direto.
 * @return string|null Token encontrado ou null.
 */
function plugin_credentials_lookup(?string $credRef): ?string {
    if(!$credRef) return null;

    // Verificar se é um token real (não uma referência)
    // Tokens GitHub começam com ghp_, github_pat_, etc.
    if(preg_match('/^(ghp_|github_pat_|gho_|ghu_|ghs_)/', $credRef)) {
        return $credRef; // Retornar o token diretamente
    }

    // Caso contrário, tratar como referência a variável de ambiente
    $envKey = 'PLUGIN_TOKEN_' . strtoupper($credRef);
    return $_ENV[$envKey] ?? null; // Fase 1: apenas via ENV
}

// === Download Helpers (GitHub) ===

/**
 * Realiza download HTTP de arquivo com suporte a cURL e fallback para streams.
 *
 * Faz o download de uma URL para um arquivo local, com:
 * - Preferência por cURL quando disponível
 * - Fallback para file_get_contents com stream context
 * - Suporte a headers customizados (autenticação, accept types)
 * - Timeout de 120 segundos
 * - Até 5 redirecionamentos
 * - Log detalhado de cada etapa
 *
 * @param string $url URL para download.
 * @param string $dest Caminho do arquivo de destino.
 * @param array $headers Headers HTTP a enviar (formato: ['Header-Name' => 'value']).
 * @param array &$log Array de log (passado por referência).
 * @return bool True se sucesso, false em caso de erro.
 */
function plugin_http_download(string $url, string $dest, array $headers, array &$log): bool {
    $log[] = '[http] GET ' . $url;
    
    // Remover arquivo antigo se existir
    if(file_exists($dest)) unlink($dest);
    $ok = false; $data = '';
    
    // Preferir cURL se disponível
    if(function_exists('curl_init')){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        
        // Preparar headers
        $hdrs = [];
        foreach($headers as $hK => $hV){ $hdrs[] = $hK.': '.$hV; }
        if($hdrs) curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
        curl_setopt($ch, CURLOPT_USERAGENT, 'conn2flow-plugin-installer/1.0');
        
        // Executar request
        $response = curl_exec($ch);
        $errno = curl_errno($ch); $err = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Validar resposta
        if($errno){ $log[] = '[erro] cURL erro='.$errno.' msg='.$err; return false; }
        if($status >= 400){ $log[]='[erro] HTTP status '.$status; return false; }
        if($response === false || $response === ''){ $log[]='[erro] resposta vazia'; return false; }
        $data = $response; $ok = true;
    } else {
        // Fallback stream
        $ctxHeaders = [];
        foreach($headers as $hK => $hV){ $ctxHeaders[] = $hK.': '.$hV; }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $ctxHeaders),
                'timeout' => 120,
            ]
        ]);
        
        $data = @file_get_contents($url, false, $ctx);
        if($data === false){ $log[]='[erro] download falhou (file_get_contents)'; return false; }
        $ok = true;
    }
    if($ok){
        if(file_put_contents($dest, $data) === false){ $log[]='[erro] falha gravar zip'; return false; }
        $log[] = '[ok] download concluído ('.strlen($data).' bytes)';
        // Corrigir permissões do arquivo temporário
        plugin_fix_temp_file_permissions($dest, $log);
        return true;
    }
    return false;
}

function plugin_github_zip_url(string $owner,string $repo,string $ref): string {
    // Usar API zipball (retorna zip) – aceita branch, tag ou commit.
    return "https://api.github.com/repos/$owner/$repo/zipball/" . rawurlencode($ref);
}

function plugin_download_github_public(string $owner,string $repo,string $ref,string $destZip, array &$log): bool {
    if(!$owner || !$repo){ $log[]='[erro] owner/repo ausentes'; return false; }

    // Para repositórios públicos, tentar descobrir assets primeiro (como fazem os privados)
    $download_url = null;
    if ($ref && $ref !== 'main' && $ref !== 'master') {
        // Se foi fornecida uma tag específica, tentar descobrir assets dessa tag
        try {
            $release_info = admin_plugins_descobrir_tag_especifica_plugin("https://github.com/{$owner}/{$repo}", $ref);
            $download_url = $release_info['download_url'];
            $log[] = '[info] Asset descoberto para tag específica: ' . $ref;
        } catch (Exception $e) {
            $log[] = '[aviso] Falha ao descobrir assets da tag ' . $ref . ': ' . $e->getMessage();
            $log[] = '[info] Usando zipball como fallback';
        }
    } else {
        // Para branch main/master, tentar descobrir a última release
        try {
            $release_info = admin_plugins_descobrir_ultima_tag_plugin("https://github.com/{$owner}/{$repo}");
            $download_url = $release_info['download_url'];
            $log[] = '[info] Asset descoberto da última release';
        } catch (Exception $e) {
            $log[] = '[aviso] Falha ao descobrir assets da última release: ' . $e->getMessage();
            $log[] = '[info] Usando zipball como fallback';
        }
    }

    // Se conseguiu descobrir um asset, usar ele
    if ($download_url) {
        $log[] = '[info] Baixando asset do release: ' . $download_url;
        return plugin_http_download($download_url, $destZip, [
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'Conn2Flow-Plugin-Manager/1.0'
        ], $log);
    }

    // Fallback: usar zipball (comportamento antigo)
    $log[] = '[info] Usando zipball (repositório completo) como fallback';
    $url = plugin_github_zip_url($owner,$repo,$ref ?: 'main');
    return plugin_http_download($url,$destZip,[
        'Accept' => 'application/vnd.github+json',
        'User-Agent' => 'Conn2Flow-Plugin-Manager/1.0'
    ],$log);
}

function plugin_download_github_private(string $owner,string $repo,string $ref,string $destZip,string $token, array &$log): bool {
    if(!$token){ $log[]='[erro] token vazio'; return false; }
    if(!$owner || !$repo){ $log[]='[erro] owner/repo ausentes'; return false; }
    $url = plugin_github_zip_url($owner,$repo,$ref ?: 'main');
    // Máscara simples para log
    $log[]='[info] utilizando token privado (***'.substr($token,-4).')';
    return plugin_http_download($url,$destZip,[
        'Accept' => 'application/vnd.github+json',
        'Authorization' => 'token '.$token
    ],$log);
}

function plugin_copy_local_path(string $sourcePath, string $destZip, array &$log): bool {
    if(is_dir($sourcePath)) {
        // Criar zip temporário do diretório (simplificado)
        $zip = new ZipArchive();
        if($zip->open($destZip, ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true){ $log[]="[erro] falha criar zip temporário"; return false; }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourcePath, FilesystemIterator::SKIP_DOTS));
        foreach($it as $file){ $fp = $file->getPathname(); $rel = substr($fp, strlen($sourcePath)+1); if(!$file->isDir()) $zip->addFile($fp, $rel); }
        $zip->close();
        $log[] = "[ok] pacote local zipado";
        // Corrigir permissões do arquivo temporário
        plugin_fix_temp_file_permissions($destZip, $log);
        return true;
    } elseif(is_file($sourcePath)) {
        // Copiar arquivo diretamente (caso seja um ZIP já pronto)
        if(copy($sourcePath, $destZip)) {
            $log[] = "[ok] arquivo local copiado";
            // Corrigir permissões do arquivo temporário
            plugin_fix_temp_file_permissions($destZip, $log);
            return true;
        } else {
            $log[] = "[erro] falha ao copiar arquivo local";
            return false;
        }
    } else {
        $log[] = "[erro] local_path não encontrado: $sourcePath";
        return false;
    }
}

function plugin_extract_zip(string $zipFile, string $destDir, array &$log): bool {
    if(!file_exists($zipFile)){ $log[] = "[erro] zip inexistente: $zipFile"; return false; }
    plugin_safe_mkdir($destDir);
    $zip = new ZipArchive();
    if($zip->open($zipFile)!==true){ $log[] = "[erro] abrir zip"; return false; }
    $zip->extractTo($destDir);
    $zip->close();
    // Normalizar diretórios com backslashes literais (artefato de zips criados em Windows)
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destDir, FilesystemIterator::SKIP_DOTS));
    foreach($rii as $f){
        $path = $f->getPathname();
        if(strpos($path,'\\')!==false){
            $normalized = str_replace('\\','/',$path);
            // Criar hierarquia alvo e mover arquivo
            $dirTarget = dirname($normalized);
            if(!is_dir($dirTarget)) mkdir($dirTarget,0777,true);
            if(is_file($path)){
                @rename($path,$normalized);
            }
        }
    }
    $log[] = "[ok] extração realizada em $destDir";
    return true;
}

function plugin_locate_manifest(string $staging, array &$log): ?string {
    // Manifest pode estar na raiz do pacote ou em subdir "plugin" (caso futuro)
    $candidates = [ $staging.'/manifest.json', $staging.'/plugin/manifest.json' ];
    foreach($candidates as $c){ if(file_exists($c)) return $c; }
    
    // Para zipballs do GitHub que criam subdiretórios, procurar recursivamente
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($staging, FilesystemIterator::SKIP_DOTS));
    foreach($rii as $file){
        if($file->isFile() && strcasecmp($file->getFilename(), 'manifest.json') === 0){
            $manifestPath = $file->getPathname();
            $log[] = '[info] manifest.json encontrado em: ' . str_replace($staging.'/', '', $manifestPath);
            return $manifestPath;
        }
    }
    
    $log[] = '[erro] manifest.json não encontrado';
    return null;
}

function plugin_move_to_final(string $staging, string $final, array &$log): bool {
    $rootPlugins = dirname($final);
    if(!is_dir($rootPlugins)) mkdir($rootPlugins,0777,true);
    if(is_dir($final)){
        // Backup antes de remover
        try { plugin_backup_existing($final,$log); } catch(Throwable $e){ $log[]='[warn] backup falhou: '.$e->getMessage(); }
        plugin_remove_dir($final);
    }

    // Verificar se o diretório staging existe
    if (!is_dir($staging)) {
        $log[] = '[erro] diretório staging não existe: ' . $staging;
        return false;
    }

    // Encontrar o manifest.json para determinar a estrutura correta
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($staging, FilesystemIterator::SKIP_DOTS));
    $manifestPath = null;
    foreach($rii as $file){
        if($file->isFile() && strcasecmp($file->getFilename(), 'manifest.json') === 0){
            $manifestPath = $file->getPathname();
            break;
        }
    }

    if (!$manifestPath) {
        $log[] = '[erro] manifest.json não encontrado no staging';
        return false;
    }

    // O diretório que contém manifest.json é o diretório raiz do plugin
    $pluginRootDir = dirname($manifestPath);
    $sourcePath = $pluginRootDir;

    $log[] = '[info] diretório raiz do plugin identificado: ' . basename($pluginRootDir);

    // mover copiando
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourcePath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach($it as $item){
        $targetPath = $final . DIRECTORY_SEPARATOR . substr($item->getPathname(), strlen($sourcePath)+1);
        if($item->isDir()){
            if(!is_dir($targetPath)) mkdir($targetPath,0777,true);
        } else {
            copy($item->getPathname(), $targetPath);
        }
    }
    $log[] = "[ok] diretório final atualizado: $final";
    return true;
}function plugin_persist_metadata(string $slug, array $manifest, ?string $checksum, string $origemTipo, array $opcoes, array &$log): void {
    $dados = [
        'id' => $slug,
        'nome' => $manifest['name'] ?? $slug,
        'origem_tipo' => $origemTipo,
        'origem_referencia' => $opcoes['referencia'] ?? null,
        'origem_branch_tag' => $opcoes['ref'] ?? null,
        'origem_credencial_ref' => $opcoes['cred_ref'] ?? null,
        'versao_instalada' => $manifest['version'] ?? '0.1.0',
        'versao' => intval(str_replace('.', '', $manifest['version'] ?? '010')) ?? 10,
        'checksum_pacote' => $checksum,
        'manifest_json' => json_encode($manifest, JSON_UNESCAPED_UNICODE),
        'status_execucao' => PLG_STATUS_OK,
        'data_ultima_atualizacao' => date('Y-m-d H:i:s'),
    ];

    // Verificar se já existe registro para preservar data_instalacao e status
    $existe = plugin_db_fetch_one("SELECT data_instalacao, status FROM plugins WHERE id='".banco_escape_field($slug)."'");
    if ($existe) {
        // Preserva data_instalacao se existir e não estiver vazia
        if (!empty($existe['data_instalacao'])) {
            $dados['data_instalacao'] = $existe['data_instalacao'];
        } else {
            $dados['data_instalacao'] = date('Y-m-d H:i:s');
        }
        // Preserva status se existir e não estiver vazio
        if (!empty($existe['status'])) {
            $dados['status'] = $existe['status'];
        } else {
            $dados['status'] = 'A';
        }
    } else {
        // Nova instalação - define status como ativo e data atual
        $dados['status'] = 'A';
        $dados['data_instalacao'] = date('Y-m-d H:i:s');
    }

    // Manual upsert via banco_insert_update without special typing
    banco_insert_update([
        'tabela' => ['nome' => 'plugins','id' => 'id'],
        'dados' => $dados,
    ]);
    $log[] = '[ok] metadata persisted in plugins table';
}

function plugin_backup_existing(string $finalPath, array &$log): void {
    if(!is_dir($finalPath)) return; // nada a fazer
    $parent = dirname($finalPath);
    $slug = basename($finalPath);
    $backupDir = $parent . '/_backups';
    if(!is_dir($backupDir)) @mkdir($backupDir,0777,true);
    $zipName = $backupDir . '/' . $slug . '-' . date('Ymd-His') . '.zip';
    $zip = new ZipArchive();
    if($zip->open($zipName, ZipArchive::CREATE)!==true){ throw new RuntimeException('falha criar zip backup'); }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($finalPath, FilesystemIterator::SKIP_DOTS));
    foreach($it as $f){ if($f->isFile()){ $rel = substr($f->getPathname(), strlen($finalPath)+1); $zip->addFile($f->getPathname(), $rel); } }
    $zip->close();
    $log[]='[ok] backup criado em '.$zipName;
}

function plugin_sync_datajson(string $staging, string $slug, array &$log): void {
    // SUPORTE DINÂMICO: Detecta automaticamente todos os arquivos *Data.json
    $finalBase = plugin_final_path($slug); // já movido
    $dataDirCandidateRoots = [];
    // Preferir diretório final (já movido), fallback staging (legado Data.json)
    if(is_dir($finalBase.'/db/data')) $dataDirCandidateRoots[] = $finalBase.'/db/data';
    if(is_dir($staging.'/db/data')) $dataDirCandidateRoots[] = $staging.'/db/data';
    
    $allDataFiles = [];
    foreach($dataDirCandidateRoots as $root){
        $files = glob($root.'/*Data.json');
        foreach($files as $file){
            $baseName = basename($file);
            $tableName = tabelaFromDataFile($file);
            $allDataFiles[$tableName] = $file;
        }
        if(count($allDataFiles) > 0) break; // primeira raiz válida
    }

    if($allDataFiles){
        $log[]='[ok] Detectado modo multi-arquivos de dados ('.implode(', ', array_keys($allDataFiles)).')';
        plugin_sync_datajson_multi($allDataFiles,$slug,$log,$finalBase);
        return;
    }

    // Fallback legado: único Data.json
    $found = null;
    if(is_dir($staging)){
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($staging, FilesystemIterator::SKIP_DOTS));
        foreach($rii as $f){
            if($f->isFile() && strcasecmp($f->getFilename(),'Data.json')===0){
                $found = $f->getPathname();
                break;
            }
        }
    }
    if(!$found){
        $log[]='[warn] Nenhum Data.json ou multi-arquivos de dados encontrados';
        return;
    }
    $raw = file_get_contents($found);
    $json = json_decode($raw,true);
    if(json_last_error() !== JSON_ERROR_NONE){ $log[]='[erro] Data.json inválido: '.json_last_error_msg(); return; }
    $counts = [];
    if(is_array($json)){
        foreach($json as $k=>$v){ if(is_array($v)) $counts[$k] = count($v); }
    }
    $destDir = plugin_datajson_dest_dir($slug); plugin_safe_mkdir($destDir);
    @file_put_contents($destDir.'/Data.json', json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    $log[]='[ok] Data.json legado copiado';
    if(isset($json['resources'])) plugin_sync_resources_granular($json['resources'],$slug,$finalBase,$log);
    plugin_sync_modules_resources($finalBase,$slug,$log);
}

/**
 * Sincroniza dados a partir de arquivos *Data.json detectados dinamicamente.
 * Copia arquivos para diretório central gestor/db/data/plugins/<slug>/ e realiza upsert nas tabelas.
 * AGORA USA DELEGAÇÃO PARA SISTEMA ROBUSTO DE BANCO DE DADOS
 */
function plugin_sync_datajson_multi(array $filesMap, string $slug, array &$log, string $finalBase): void {
    $destDir = plugin_datajson_dest_dir($slug);
    plugin_safe_mkdir($destDir);
    $tot=['inserts'=>0,'updates'=>0,'skipped'=>0];
    $loadJson = function(string $file, array &$log): array {
        if(!is_file($file)) return [];
        $raw = file_get_contents($file);
        $d = json_decode($raw,true);
        if(json_last_error()!==JSON_ERROR_NONE){ $log[]='[erro] JSON inválido: '.$file.' -> '.json_last_error_msg(); return []; }
        return is_array($d)?$d:[];
    };

    // Copiar arquivos para diretório do plugin
    foreach($filesMap as $tableName=>$path){
        @copy($path, $destDir.'/'.basename($path));
    }

    // DELEGAÇÃO: Usar sistema robusto de banco de dados ao invés de upsert manual
    $dataFiles = array_values($filesMap);
    if (!plugin_delegate_database_operations($slug, $dataFiles, $log)) {
        $log[] = '[erro] Falha na delegação de operações de banco de dados';
        return;
    }

    // Estatísticas serão reportadas pelo sistema robusto
    $log[]='[ok] multi-data sincronizado via sistema robusto plugin='.$slug;
}

/**
 * Executa migrações específicas do plugin caso exista diretório db/migrations no pacote final.
 */
function plugin_run_migrations(string $slug, string $finalPath, array &$log, array $opts = []): void {
    if(!empty($opts['no_migrations'])) { $log[]='[info] migrações desativadas (--no-migrations)'; return; }
    if(!empty($opts['only_resources'])) { $log[]='[info] pulando migrações (only-resources)'; return; }
    $migDir = rtrim($finalPath,'/').'/db/migrations';
    if(!is_dir($migDir)) { $log[]='[info] plugin sem migrações'; return; }
    // Verifica se há arquivos php
    $has = glob($migDir.'/*.php') ?: [];
    if(!$has){ $log[]='[info] diretório de migrações vazio'; return; }
    try {
        // Carrega config principal para obter credenciais
        $gestorRoot = plugin_base_root(); // gestor/
        $phinxConfigFile = $gestorRoot.'phinx.php';
        if(!file_exists($phinxConfigFile)) { $log[]='[erro] phinx.php não encontrado para migrações'; return; }
        $baseConfig = require $phinxConfigFile; // array
        if(!is_array($baseConfig)){ $log[]='[erro] phinx config inválida'; return; }
        // Sobrescreve apenas paths.migrations
        $baseConfig['paths']['migrations'] = $migDir;
        // Autoload composer
        $autoload = $gestorRoot.'vendor/autoload.php'; if(file_exists($autoload)) require_once $autoload;
        if(!class_exists('\Phinx\Config\Config')) { $log[]='[erro] Phinx não disponível'; return; }
        $configObj = new \Phinx\Config\Config($baseConfig, $phinxConfigFile);
        $input  = new \Symfony\Component\Console\Input\ArrayInput([]);
        $buffer = new \Symfony\Component\Console\Output\BufferedOutput();
        $manager = new \Phinx\Migration\Manager($configObj,$input,$buffer);
        $env = $configObj->getDefaultEnvironment() ?: 'gestor';
        $manager->migrate($env);
        $out = trim($buffer->fetch());
        $log[]='[ok] migrações plugin executadas ('.count($has).' arquivos)';
        if($out!=='') $log[]='[phinx] '.str_replace("\n"," | ",$out);
    } catch(\Throwable $e){
        $log[]='[erro] migrações plugin falharam: '.$e->getMessage();
    }
}

function plugin_sync_resources_granular(array $resources, string $pluginId, ?string $baseDir, array &$log): void {
    // Estrutura esperada: resources[<lang>]['layouts'|'pages'|'components'|'variables'] => arrays
    $tot=['layouts'=>0,'pages'=>0,'components'=>0,'variables'=>0,'updates'=>0,'inserts'=>0,'skipped'=>0];
    foreach($resources as $lang=>$bundle){
        if(!is_array($bundle)) continue;
        // Layouts
        if(!empty($bundle['layouts']) && is_array($bundle['layouts'])){
            foreach($bundle['layouts'] as $layout){
                $id=$layout['id']??null; if(!$id){$tot['skipped']++; continue;}
                plugin_enrich_resource_checksums($baseDir,'layouts',$id,$layout,$log);
                plugin_upsert_layout($pluginId,$lang,$id,$layout,$tot);
            }
        }
        // Pages
        if(!empty($bundle['pages']) && is_array($bundle['pages'])){
            foreach($bundle['pages'] as $page){
                $id=$page['id']??null; if(!$id){$tot['skipped']++; continue;}
                plugin_enrich_resource_checksums($baseDir,'pages',$id,$page,$log);
                plugin_upsert_page($pluginId,$lang,$id,$page,$tot);
            }
        }
        // Components
        if(!empty($bundle['components']) && is_array($bundle['components'])){
            foreach($bundle['components'] as $comp){
                $id=$comp['id']??null; if(!$id){$tot['skipped']++; continue;}
                plugin_enrich_resource_checksums($baseDir,'components',$id,$comp,$log);
                plugin_upsert_component($pluginId,$lang,$id,$comp,$tot);
            }
        }
        // Variables
        if(!empty($bundle['variables']) && is_array($bundle['variables'])){
            foreach($bundle['variables'] as $var){
                $id=$var['id']??null; if(!$id){$tot['skipped']++; continue;}
                // variáveis não possuem html/css
                plugin_upsert_variable($pluginId,$lang,$id,$var,$tot);
            }
        }
    }
    $log[]='[ok] sync granular plugin='.$pluginId.' inserts='.$tot['inserts'].' updates='.$tot['updates'].' skipped='.$tot['skipped'];
}

function plugin_enrich_resource_checksums(?string $baseDir,string $tipo,string $id,array &$item,array &$log): void {
    if(!$baseDir || isset($item['checksum'])) return; // já fornecido
    $paths = plugin_guess_resource_files($baseDir,$tipo,$id);
    if(!$paths) return;
    $htmlHash = null; $cssHash=null;
    if(isset($paths['html']) && is_file($paths['html'])) $htmlHash = hash_file('sha256',$paths['html']);
    if(isset($paths['css']) && is_file($paths['css'])) $cssHash = hash_file('sha256',$paths['css']);
    if($htmlHash || $cssHash){
        $combined = hash('sha256', ($htmlHash??'').':'.($cssHash??''));
        $item['checksum'] = [ 'html'=>$htmlHash, 'css'=>$cssHash, 'combined'=>$combined ];
    }
}

function plugin_guess_resource_files(string $baseDir,string $tipo,string $id): ?array {
    $dirMap = [ 'layouts'=>'layouts', 'pages'=>'pages', 'components'=>'components' ];
    if(!isset($dirMap[$tipo])) return null;
    $root = rtrim($baseDir,'/').'/'.$dirMap[$tipo];
    if(!is_dir($root)) return null;
    $files = [];
    // Padrões possíveis: <id>.html / <id>.css ou <id>/index.html
    $html1 = $root.'/'.$id.'.html';
    $css1  = $root.'/'.$id.'.css';
    $html2 = $root.'/'.$id.'/index.html';
    $css2  = $root.'/'.$id.'/index.css';
    if(is_file($html1)) $files['html']=$html1; elseif(is_file($html2)) $files['html']=$html2;
    if(is_file($css1)) $files['css']=$css1; elseif(is_file($css2)) $files['css']=$css2;
    return $files?:null;
}

// === Sincronização de Recursos por Módulos ===
// Estrutura esperada dentro do pacote extraído:
//   modules/<modulo>/module-id.json OU modulos/<modulo>/module-id.json
// Cada arquivo module-id.json pode conter chave "resources" similar ao Data.json (por idioma)
// Exemplo mínimo:
// {
//   "id": "crm",
//   "resources": { "pt-br": { "pages": [ {"id": "dashboard", "name": "Dashboard"} ] } }
// }
// Regras:
// - Campo module/modulo é forçado com o nome da pasta se não presente no item
// - Reaproveita as funções plugin_upsert_* existentes (que já aceitam campo module/modulo dentro do array fonte)
// - Agregamos estatísticas globais por módulos para log
function plugin_sync_modules_resources(string $staging, string $pluginId, array &$log): void {
    $roots = [];
    foreach(['modules','modulos'] as $dir){
        $p = rtrim($staging,'/').'/'.$dir;
        if(is_dir($p)) $roots[] = $p;
    }
    if(!$roots){
        $log[]='[info] nenhum diretório de módulos encontrado';
        return;
    }
    $tot=['modules'=>0,'layouts'=>0,'pages'=>0,'components'=>0,'variables'=>0,'updates'=>0,'inserts'=>0,'skipped'=>0];
    foreach($roots as $root){
        $it = new DirectoryIterator($root);
        foreach($it as $entry){
            if($entry->isDot() || !$entry->isDir()) continue;
            $moduleName = $entry->getFilename();
            $modulePath = $entry->getPathname();
            // Candidatos a arquivo descriptor
            $candidates = [
                $modulePath.'/module-id.json',
                $modulePath.'/module.json',
                $modulePath.'/modulo-id.json',
                $modulePath.'/modulo.json'
            ];
            $descriptor = null;
            foreach($candidates as $c){ if(file_exists($c)){ $descriptor = $c; break; } }
            if(!$descriptor){
                $log[]='[warn] módulo "'.$moduleName.'" sem descriptor module-id.json';
                continue;
            }
            $raw = file_get_contents($descriptor);
            $json = json_decode($raw,true);
            if(json_last_error() !== JSON_ERROR_NONE){
                $log[]='[erro] JSON inválido em módulo '.$moduleName.': '.json_last_error_msg();
                continue;
            }
            if(empty($json['resources']) || !is_array($json['resources'])){
                $log[]='[info] módulo '.$moduleName.' sem resources';
                continue;
            }
            $tot['modules']++;
            foreach($json['resources'] as $lang=>$bundle){
                if(!is_array($bundle)) continue;
                // Layouts
                if(!empty($bundle['layouts']) && is_array($bundle['layouts'])){
                    foreach($bundle['layouts'] as $layout){
                        $id=$layout['id']??null; if(!$id){$tot['skipped']++; continue;}
                        if(empty($layout['module']) && empty($layout['modulo'])) $layout['module']=$moduleName;
                        plugin_upsert_layout($pluginId,$lang,$id,$layout,$tot); // layout não tem coluna modulo? se tiver é ignorado
                    }
                }
                // Pages
                if(!empty($bundle['pages']) && is_array($bundle['pages'])){
                    foreach($bundle['pages'] as $page){
                        $id=$page['id']??null; if(!$id){$tot['skipped']++; continue;}
                        if(empty($page['module']) && empty($page['modulo'])) $page['module']=$moduleName;
                        plugin_upsert_page($pluginId,$lang,$id,$page,$tot);
                    }
                }
                // Components
                if(!empty($bundle['components']) && is_array($bundle['components'])){
                    foreach($bundle['components'] as $comp){
                        $id=$comp['id']??null; if(!$id){$tot['skipped']++; continue;}
                        if(empty($comp['module']) && empty($comp['modulo'])) $comp['module']=$moduleName;
                        plugin_upsert_component($pluginId,$lang,$id,$comp,$tot);
                    }
                }
                // Variables
                if(!empty($bundle['variables']) && is_array($bundle['variables'])){
                    foreach($bundle['variables'] as $var){
                        $id=$var['id']??null; if(!$id){$tot['skipped']++; continue;}
                        if(empty($var['module']) && empty($var['modulo'])) $var['module']=$moduleName;
                        plugin_upsert_variable($pluginId,$lang,$id,$var,$tot);
                    }
                }
            }
        }
    }
    $log[]='[ok] sync módulos plugin='.$pluginId.' modules='.$tot['modules'].' inserts='.$tot['inserts'].' updates='.$tot['updates'].' skipped='.$tot['skipped'].' layouts='.$tot['layouts'].' pages='.$tot['pages'].' components='.$tot['components'].' variables='.$tot['variables'];
}

function plugin_db_fetch_one(string $sql){
    $res = banco_query($sql); if(!$res) return null; $row = banco_fetch_assoc($res); return $row?:null; }

/**
 * Delega operações de banco de dados para o sistema robusto de atualizações.
 * Esta função substitui todas as operações manuais de upsert por delegação.
 */
function plugin_delegate_database_operations(string $pluginSlug, array $dataFiles, array &$log): bool {
    $log[] = '[info] Delegando operações de banco de dados para sistema robusto';

    // Caminho para o script de atualização de banco de dados
    $scriptPath = plugin_base_root() . 'controladores/plugins/atualizacao-plugin-banco-de-dados.php';

    if (!file_exists($scriptPath)) {
        $log[] = '[erro] Script de atualização de banco de dados não encontrado: ' . $scriptPath;
        return false;
    }

        // Preparar argumentos para o script
    $args = [
        '--plugin=' . escapeshellarg($pluginSlug),
        '--debug',
        '--log-diff'
    ];

    // Se há arquivos específicos, filtrar apenas eles
    if (!empty($dataFiles)) {
        $tableNames = array_map('tabelaFromDataFile', $dataFiles);
        $args[] = '--tables=' . escapeshellarg(implode(',', $tableNames));
    }

    if (plugin_is_cli_context()) {
        // Em contexto CLI, usar exec() como antes
        $cmd = 'php ' . escapeshellarg($scriptPath) . ' ' . implode(' ', $args);
        $log[] = '[info] Executando comando: ' . $cmd;

        // Executar o comando
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        // Processar saída
        foreach ($output as $line) {
            $log[] = '[db-system] ' . $line;
        }

        if ($returnCode === 0) {
            $log[] = '[ok] Operações de banco de dados delegadas com sucesso';
            return true;
        } else {
            $log[] = '[erro] Falha ao delegar operações de banco de dados (código: ' . $returnCode . ')';
            return false;
        }
    } else {
        // Em contexto web, usar include/require para evitar exec()
        $log[] = '[info] Contexto web - incluindo script diretamente: ' . $scriptPath;

        // Preparar variáveis globais que o script pode precisar
        global $argv, $GLOBALS;
        $originalArgv = $argv ?? [];
        $originalCliOpts = $GLOBALS['CLI_OPTS'] ?? null;
        
        // Simular argumentos da linha de comando
        $argv = array_merge(['php'], $args);
        
        // Parsear argumentos e definir $GLOBALS['CLI_OPTS'] para o script incluído
        $parsedArgs = [];
        foreach ($args as $arg) {
            if (preg_match('/^--([^=]+)=(.+)$/', $arg, $matches)) {
                $value = $matches[2];
                // Remover aspas simples ou duplas se estiverem presentes no início e fim
                if ((substr($value, 0, 1) === "'" && substr($value, -1) === "'") ||
                    (substr($value, 0, 1) === '"' && substr($value, -1) === '"')) {
                    $value = substr($value, 1, -1);
                }
                $parsedArgs[$matches[1]] = $value;
            } elseif (substr($arg, 0, 2) === '--') {
                $parsedArgs[substr($arg, 2)] = true;
            }
        }
        $GLOBALS['CLI_OPTS'] = $parsedArgs;
        // Também definir como variável global para compatibilidade
        global $CLI_OPTS;
        $CLI_OPTS = $parsedArgs;

        // Passar referência ao logger para unificar logs
        $GLOBALS['EXTERNAL_LOGGER'] = &$log;

        try {
            // Incluir o script diretamente (ele já chama main() no final)
            ob_start();
            $result = require $scriptPath;
            $output = ob_get_clean();

            // Adicionar saída aos logs se houver
            if (!empty(trim($output))) {
                $log[] = '[db-system] ' . trim($output);
            }

            // Verificar se o script retornou um código de saída
            if (is_int($result) && $result === 0) {
                $log[] = '[ok] Operações de banco de dados delegadas com sucesso';
                return true;
            } else {
                $log[] = '[erro] Falha ao delegar operações de banco de dados (código: ' . ($result ?? 'desconhecido') . ')';
                return false;
            }
        } catch (\Throwable $e) {
            $log[] = '[erro] Exceção ao incluir script de banco de dados: ' . $e->getMessage();
            return false;
        } finally {
            // Restaurar valores originais
            $argv = $originalArgv;
            if ($originalCliOpts !== null) {
                $GLOBALS['CLI_OPTS'] = $originalCliOpts;
                global $CLI_OPTS;
                $CLI_OPTS = $originalCliOpts;
            } else {
                unset($GLOBALS['CLI_OPTS']);
                global $CLI_OPTS;
                unset($CLI_OPTS);
            }
            // Limpar logger externo
            unset($GLOBALS['EXTERNAL_LOGGER']);
        }
    }
}

/**
 * Função genérica de upsert que delega para o sistema robusto de banco de dados.
 * Esta função substitui todas as operações manuais de upsert por delegação.
 */
function plugin_upsert_generic(string $tableName, string $pluginSlug, array $row, array &$tot, array &$log): void {
    // Para compatibilidade, manter contadores zerados já que a delegação será feita em lote
    $tot['inserts'] += 0; // Será atualizado pelo sistema robusto
    $tot['updates'] += 0; // Será atualizado pelo sistema robusto
    $tot['skipped'] += 0; // Será atualizado pelo sistema robusto

    $log[] = "[info] Operação de banco delegada para sistema robusto: tabela=$tableName plugin=$pluginSlug";
}

function plugin_upsert_layout(string $plugin,string $lang,string $id,array $src,array &$tot): void {
    $nome = banco_escape_field($src['name']??($src['nome']??$id));
    $checksum = isset($src['checksum'])? (is_array($src['checksum'])?json_encode($src['checksum'],JSON_UNESCAPED_UNICODE):$src['checksum']) : null;
    $file_version = banco_escape_field($src['version']??null);
    $exists = plugin_db_fetch_one("SELECT id_layouts,checksum FROM layouts WHERE plugin='".banco_escape_field($plugin)."' AND id='".banco_escape_field($id)."' AND language='".banco_escape_field($lang)."'");
    if(!$exists){
        $orfao = plugin_db_fetch_one("SELECT id_layouts,checksum FROM layouts WHERE (plugin='' OR plugin IS NULL) AND id='".banco_escape_field($id)."' AND language='".banco_escape_field($lang)."'");
        if($orfao){
            banco_update_campo('plugin',$plugin);
            banco_update_campo('nome',$nome);
            if($file_version) banco_update_campo('file_version',$file_version);
            if($checksum) banco_update_campo('checksum',$checksum);
            // banco_update_campo('data_modificacao','NOW()',true);
            banco_update_executar('layouts',"WHERE id_layouts='".$orfao['id_layouts']."'");
            $tot['updates']++;
            $tot['layouts']++;
            return;
        }
    }
    if($exists){
        if($checksum && $exists['checksum']!==$checksum){
            banco_update_campo('nome',$nome);
            if($file_version) banco_update_campo('file_version',$file_version);
            if($checksum) banco_update_campo('checksum',$checksum);
            // Incrementar versão manualmente ao invés de usar banco_update_set_expr
            $novaVersao = (int)($exists['versao'] ?? 1) + 1;
            banco_update_campo('versao', $novaVersao, true);
            // banco_update_campo('data_modificacao','NOW()',true);
            banco_update_executar('layouts',"WHERE id_layouts='".$exists['id_layouts']."'");
            $tot['updates']++;
        } else { $tot['skipped']++; }
    } else {
        banco_insert_name_campo('plugin',$plugin);
        banco_insert_name_campo('id',$id);
        banco_insert_name_campo('language',$lang);
        banco_insert_name_campo('nome',$nome);
        if($file_version) banco_insert_name_campo('file_version',$file_version);
        if($checksum) banco_insert_name_campo('checksum',$checksum,false,true);
        banco_insert_name_campo('versao','1',true,true);
        banco_insert_name_campo('data_criacao','NOW()',true,true);
        // banco_insert_name_campo('data_modificacao','NOW()',true,true);
        banco_insert_name(banco_insert_name_campos(),'layouts');
        $tot['inserts']++;
    }
    $tot['layouts']++;
}

function plugin_upsert_page(string $plugin,string $lang,string $id,array $src,array &$tot): void {
    $nome = banco_escape_field($src['name']??($src['nome']??$id));
    $checksum = isset($src['checksum'])? (is_array($src['checksum'])?json_encode($src['checksum'],JSON_UNESCAPED_UNICODE):$src['checksum']) : null;
    $caminho = banco_escape_field($src['path']??($src['caminho']??($id.'/')));
    $moduloRaw = $src['module']??($src['modulo']??''); if(is_string($moduloRaw)) $moduloRaw=strtolower(trim($moduloRaw));
    $modulo = banco_escape_field($moduloRaw);
    $file_version = banco_escape_field($src['version']??null);
    // Match exato
    $exists = plugin_db_fetch_one("SELECT id_paginas,checksum,modulo FROM paginas WHERE plugin='".banco_escape_field($plugin)."' AND id='".banco_escape_field($id)."' AND language='".banco_escape_field($lang)."' AND modulo='".$modulo."' LIMIT 1");
    // Fallback: ignorar modulo (capturar registro antigo sem modulo)
    if(!$exists){
        $fallback = plugin_db_fetch_one("SELECT id_paginas,checksum,modulo FROM paginas WHERE plugin='".banco_escape_field($plugin)."' AND id='".banco_escape_field($id)."' AND language='".banco_escape_field($lang)."' LIMIT 1");
        if($fallback){
            if($fallback['modulo']!==$modulo){
                banco_update_campo('modulo',$modulo);
                // banco_update_campo('data_modificacao','NOW()',true);
                banco_update_executar('paginas',"WHERE id_paginas='".$fallback['id_paginas']."'");
            }
            $exists=$fallback;
        }
    }
    if($exists){
        if($checksum && $exists['checksum']!==$checksum){
            banco_update_campo('nome',$nome);
            banco_update_campo('caminho',$caminho);
            if($file_version) banco_update_campo('file_version',$file_version);
            banco_update_campo('checksum',$checksum,false,true);
            // Incrementar versão manualmente ao invés de usar banco_update_set_expr
            $novaVersao = (int)($exists['versao'] ?? 1) + 1;
            banco_update_campo('versao', $novaVersao, true);
            // Remover data_modificacao se a coluna não existir
            // banco_update_campo('data_modificacao','NOW()',true);
            banco_update_executar('paginas',"WHERE id_paginas='".$exists['id_paginas']."'");
            $tot['updates']++;
        } else { $tot['skipped']++; }
    } else {
        banco_insert_name_campo('plugin',$plugin);
        banco_insert_name_campo('id',$id);
        banco_insert_name_campo('language',$lang);
        if($modulo!=='') banco_insert_name_campo('modulo',$modulo);
        banco_insert_name_campo('nome',$nome);
        banco_insert_name_campo('caminho',$caminho);
        if($file_version) banco_insert_name_campo('file_version',$file_version);
        if($checksum) banco_insert_name_campo('checksum',$checksum,false,true);
        banco_insert_name_campo('versao','1',true,true);
        banco_insert_name_campo('data_criacao','NOW()',true,true);
        // Remover data_modificacao se a coluna não existir
        // banco_insert_name_campo('data_modificacao','NOW()',true,true);
        banco_insert_name(banco_insert_name_campos(),'paginas');
        $tot['inserts']++;
    }
    $tot['pages']++;
}

function plugin_upsert_component(string $plugin,string $lang,string $id,array $src,array &$tot): void {
    $nome = banco_escape_field($src['name']??($src['nome']??$id));
    $checksum = isset($src['checksum'])? (is_array($src['checksum'])?json_encode($src['checksum'],JSON_UNESCAPED_UNICODE):$src['checksum']) : null;
    $moduloRaw = $src['module']??($src['modulo']??''); if(is_string($moduloRaw)) $moduloRaw=strtolower(trim($moduloRaw));
    $modulo = banco_escape_field($moduloRaw);
    $file_version = banco_escape_field($src['version']??null);
    $exists = plugin_db_fetch_one("SELECT id_componentes,checksum,modulo FROM componentes WHERE plugin='".banco_escape_field($plugin)."' AND id='".banco_escape_field($id)."' AND language='".banco_escape_field($lang)."' LIMIT 1");
    if(!$exists){
        $fallback = plugin_db_fetch_one("SELECT id_componentes,checksum,modulo FROM componentes WHERE plugin='".banco_escape_field($plugin)."' AND id='".banco_escape_field($id)."' LIMIT 1");
        if($fallback){
            if($fallback['modulo']!==$modulo){
                banco_update_campo('modulo',$modulo);
                // banco_update_campo('data_modificacao','NOW()',true);
                banco_update_executar('componentes',"WHERE id_componentes='".$fallback['id_componentes']."'");
            }
            $exists=$fallback;
        }
    }
    if($exists){
        if($checksum && $exists['checksum']!==$checksum){
            banco_update_campo('nome',$nome);
            if($file_version) banco_update_campo('file_version',$file_version);
            banco_update_campo('checksum',$checksum,false,true);
            // Incrementar versão manualmente ao invés de usar banco_update_set_expr
            $novaVersao = (int)($exists['versao'] ?? 1) + 1;
            banco_update_campo('versao', $novaVersao, true);
            // Remover data_modificacao se a coluna não existir
            // banco_update_campo('data_modificacao','NOW()',true);
            banco_update_executar('componentes',"WHERE id_componentes='".$exists['id_componentes']."'");
            $tot['updates']++;
        } else { $tot['skipped']++; }
    } else {
        banco_insert_name_campo('plugin',$plugin);
        banco_insert_name_campo('id',$id);
        banco_insert_name_campo('language',$lang);
        if($modulo!=='') banco_insert_name_campo('modulo',$modulo);
        banco_insert_name_campo('nome',$nome);
        if($file_version) banco_insert_name_campo('file_version',$file_version);
        if($checksum) banco_insert_name_campo('checksum',$checksum,false,true);
        banco_insert_name_campo('versao','1',true,true);
        banco_insert_name_campo('data_criacao','NOW()',true,true);
        // Remover data_modificacao se a coluna não existir
        // banco_insert_name_campo('data_modificacao','NOW()',true,true);
        banco_insert_name(banco_insert_name_campos(),'componentes');
        $tot['inserts']++;
    }
    $tot['components']++;
}

function plugin_upsert_variable(string $plugin,string $lang,string $id,array $src,array &$tot): void {
    $valor = banco_escape_field($src['value']??($src['valor']??''));
    $tipo = banco_escape_field($src['type']??($src['tipo']??''));
    $grupoRaw = $src['group']??($src['grupo']??''); if(is_string($grupoRaw)) $grupoRaw=trim($grupoRaw);
    $grupo = banco_escape_field($grupoRaw);
    $moduloRaw = $src['module']??($src['modulo']??''); if(is_string($moduloRaw)) $moduloRaw=strtolower(trim($moduloRaw));
    $modulo = banco_escape_field($moduloRaw);
    $exists = plugin_db_fetch_one("SELECT id_variaveis,modulo,grupo FROM variaveis WHERE plugin='".banco_escape_field($plugin)."' AND id='".banco_escape_field($id)."' AND linguagem_codigo='".banco_escape_field($lang)."' AND modulo='".$modulo."' AND (grupo".($grupo!==''?"='".$grupo."'":" IS NULL").") LIMIT 1");
    if(!$exists){
        $fallback = plugin_db_fetch_one("SELECT id_variaveis,modulo,grupo FROM variaveis WHERE plugin='".banco_escape_field($plugin)."' AND id='".banco_escape_field($id)."' AND linguagem_codigo='".banco_escape_field($lang)."' LIMIT 1");
        if($fallback){
            if($modulo!=='' && ($fallback['modulo']??'')!==$modulo) banco_update_campo('modulo',$modulo);
            if($grupo!=='' && ($fallback['grupo']??'')!==$grupo) banco_update_campo('grupo',$grupo);
            // banco_update_campo('data_modificacao','NOW()',true);
            banco_update_executar('variaveis',"WHERE id_variaveis='".$fallback['id_variaveis']."'");
            $exists=$fallback;
        }
    }
    if($exists){
        banco_update_campo('valor',$valor);
        if($tipo!=='') banco_update_campo('tipo',$tipo);
        if($grupo!=='') banco_update_campo('grupo',$grupo);
        banco_update_executar('variaveis',"WHERE id_variaveis='".$exists['id_variaveis']."'");
        $tot['updates']++;
    } else {
        banco_insert_name_campo('plugin',$plugin);
        banco_insert_name_campo('id',$id);
        banco_insert_name_campo('linguagem_codigo',$lang);
        if($modulo!=='') banco_insert_name_campo('modulo',$modulo);
        banco_insert_name_campo('valor',$valor);
        if($tipo!=='') banco_insert_name_campo('tipo',$tipo);
        if($grupo!=='') banco_insert_name_campo('grupo',$grupo);
        banco_insert_name(banco_insert_name_campos(),'variaveis');
        $tot['inserts']++;
    }
    $tot['variables']++;
}

function plugin_upsert_module(string $plugin,string $id,array $src,array &$tot): void {
    $nome = banco_escape_field($src['name']??($src['nome']??$id));
    $modulo_grupo_id = banco_escape_field($src['modulo_grupo_id']??'');
    $titulo = $src['titulo'];
    $icone = banco_escape_field($src['icone']??'');
    $icone2 = $src['icone2'];
    $nao_menu_principal = isset($src['nao_menu_principal']) ? (int)$src['nao_menu_principal'] : null;
    $host = $src['host'];
    $status = banco_escape_field($src['status']??'A');
    $versao = (int)($src['versao']??1);
    $exists = plugin_db_fetch_one("SELECT id_modulos FROM modulos WHERE plugin='".banco_escape_field($plugin)."' AND id='".banco_escape_field($id)."'");
    if($exists){
        // Update
        banco_update_campo('nome',$nome);
        if($modulo_grupo_id!=='') banco_update_campo('modulo_grupo_id',$modulo_grupo_id);
        if($titulo !== null) banco_update_campo('titulo',$titulo);
        if($icone!=='') banco_update_campo('icone',$icone);
        if($icone2 !== null) banco_update_campo('icone2',$icone2);
        if($nao_menu_principal!==null) banco_update_campo('nao_menu_principal',$nao_menu_principal,true);
        if($host !== null) banco_update_campo('host',$host,true);
        if($status!=='') banco_update_campo('status',$status);
        banco_update_campo('versao',$versao,true);
        // banco_update_campo('data_modificacao','NOW()',true);
        banco_update_executar('modulos',"WHERE id_modulos='".$exists['id_modulos']."'");
        $tot['updates']++;
    } else {
        // Insert
        banco_insert_name_campo('plugin',$plugin);
        banco_insert_name_campo('id',$id);
        banco_insert_name_campo('nome',$nome);
        if($modulo_grupo_id!=='') banco_insert_name_campo('modulo_grupo_id',$modulo_grupo_id);
        if($titulo !== null) banco_insert_name_campo('titulo',$titulo);
        if($icone!=='') banco_insert_name_campo('icone',$icone);
        if($icone2 !== null) banco_insert_name_campo('icone2',$icone2);
        if($nao_menu_principal!==null) banco_insert_name_campo('nao_menu_principal',$nao_menu_principal,true);
        if($host !== null) banco_insert_name_campo('host',$host,true);
        banco_insert_name_campo('status',$status);
        banco_insert_name_campo('versao',$versao,true);
        banco_insert_name_campo('data_criacao','NOW()',true,true);
        // banco_insert_name_campo('data_modificacao','NOW()',true,true);
        banco_insert_name(banco_insert_name_campos(),'modulos');
        $tot['inserts']++;
    }
    $tot['modules']++;
}

function plugin_checksum_changed(string $slug, ?string $newChecksum): bool {
    if(!$newChecksum) return true;
    $res = banco_select([
        'tabela' => 'plugins',
        'campos' => ['checksum_pacote'],
        'extra' => "WHERE id='".banco_escape_field($slug)."'"
    ]);
    if(!$res) return true;
    $old = $res[0]['checksum_pacote'] ?? null;
    return $old !== $newChecksum;
}

function plugin_mark_status(string $slug, string $status): void {
    banco_update_campo('status_execucao', $status);
    banco_update_campo('data_ultima_atualizacao', 'NOW()', true);
    banco_update_executar('plugins', "WHERE id='".banco_escape_field($slug)."'");
}

function plugin_log_block(array $logLines, string $slug): void {
    $dir = plugin_base_root() . 'logs/plugins';
    if(!is_dir($dir)) mkdir($dir, 0777, true);
    $file = $dir.'/installer.log';
    foreach($logLines as $l){ file_put_contents($file, '['.date('c')."] [PLUGIN:$slug] $l\n", FILE_APPEND); }
}

/**
 * Limpa pasta DB e corrige permissões após instalação
 */
function plugin_cleanup_after_install(string $finalPath, array &$log): void {
    $dbDir = $finalPath . '/db';
    
    // Remover pasta DB se existir
    if(is_dir($dbDir)) {
        plugin_remove_dir($dbDir);
        $log[] = '[ok] pasta db/ removida do plugin instalado';
    }
    
    // Corrigir permissões apenas em contexto CLI
    if(plugin_is_cli_context()) {
        $parentDir = dirname($finalPath);
        if(is_dir($parentDir)) {
            $stat = stat($parentDir);
            if($stat) {
                $owner = posix_getpwuid($stat['uid'])['name'] ?? 'www-data';
                $group = posix_getgrgid($stat['gid'])['name'] ?? 'www-data';
                
                // Executar chown recursivo
                $cmd = "chown -R $owner:$group " . escapeshellarg($finalPath);
                exec($cmd, $output, $returnCode);
                
                if($returnCode === 0) {
                    $log[] = "[ok] permissões corrigidas para $owner:$group";
                } else {
                    $log[] = "[aviso] falha ao corrigir permissões (código $returnCode)";
                }
            }
        }
    } else {
        $log[] = '[info] contexto web - pulando correção de permissões (gerenciado automaticamente pelo servidor)';
    }
}

function plugin_fix_temp_file_permissions(string $filePath, array &$log): void {
    if(!file_exists($filePath)) return;
    
    // Corrigir permissões apenas em contexto CLI
    if(plugin_is_cli_context()) {
        // Executar chown para www-data:www-data
        $cmd = "chown www-data:www-data " . escapeshellarg($filePath);
        exec($cmd, $output, $returnCode);
        
        if($returnCode === 0) {
            $log[] = "[ok] permissões corrigidas para www-data:www-data: $filePath";
        } else {
            $log[] = "[aviso] falha ao corrigir permissões (código $returnCode): $filePath";
        }
    } else {
        $log[] = "[info] contexto web - pulando correção de permissões para arquivo temporário (gerenciado automaticamente pelo servidor): $filePath";
    }
}

function plugin_process(array $params): int {
    $log = [];
    $slug = plugin_normalize_slug($params['slug']);
    $origem = $params['origem_tipo'];
    $log[] = "Iniciando processamento slug=$slug origem=$origem";
    $staging = plugin_staging_path($slug);
    $zipTmp = sys_get_temp_dir() . "/plg_$slug.zip";
    if(is_dir($staging)) plugin_remove_dir($staging);
    plugin_safe_mkdir($staging);
    $downloadOk = false;
    switch($origem){
        case 'upload':
            if(!isset($params['arquivo']) || !file_exists($params['arquivo'])){ $log[]='[erro] arquivo upload não encontrado. file_path: '.$params['arquivo']; plugin_log_block($log,$slug); return PLG_EXIT_PARAMS_OR_FILE; }
            copy($params['arquivo'], $zipTmp); $downloadOk = true; $log[]='[ok] arquivo upload copiado';
            // Corrigir permissões do arquivo temporário
            plugin_fix_temp_file_permissions($zipTmp, $log);
        break;
        case 'github_publico':
            // Se download_url foi fornecido diretamente (ex: de descoberta anterior), usar ele
            if(!empty($params['download_url'])){
                $log[]='[info] usando URL de download fornecida diretamente';
                $downloadOk = plugin_http_download($params['download_url'], $zipTmp, [ 'Accept' => 'application/octet-stream' ], $log);
                // Tentar baixar SHA256 se disponível
                if(!empty($params['sha256_url'])){
                    $sha256Tmp = sys_get_temp_dir() . "/plg_$slug.zip.sha256";
                    $log[]='[info] baixando arquivo SHA256';
                    plugin_http_download($params['sha256_url'], $sha256Tmp, [ 'Accept' => 'application/octet-stream' ], $log);
                }
            } else {
                $downloadOk = plugin_download_github_public($params['owner'],$params['repo'],$params['ref'] ?? 'main',$zipTmp,$log);
            }
        break;
        case 'github_privado':
            $token = plugin_credentials_lookup($params['cred_ref'] ?? null);
            if(!$token){ $log[]='[erro] credencial não resolvida'; plugin_log_block($log,$slug); return PLG_EXIT_PARAMS_OR_FILE; }
            
            // Se download_url foi fornecido diretamente (ex: de descoberta anterior), usar ele
            if(!empty($params['download_url'])){
                $log[]='[info] utilizando token privado (***'.substr($token,-4).')';
                $log[]='[info] usando URL de download fornecida diretamente';
                $downloadOk = plugin_http_download($params['download_url'], $zipTmp, [
                    'Accept' => 'application/octet-stream',
                    'Authorization' => 'token '.$token
                ], $log);
                // Tentar baixar SHA256 se disponível
                if(!empty($params['sha256_url'])){
                    $sha256Tmp = sys_get_temp_dir() . "/plg_$slug.zip.sha256";
                    $log[]='[info] baixando arquivo SHA256';
                    plugin_http_download($params['sha256_url'], $sha256Tmp, [
                        'Accept' => 'application/octet-stream',
                        'Authorization' => 'token '.$token
                    ], $log);
                }
            } else {
                $downloadOk = plugin_download_github_private($params['owner'],$params['repo'],$params['ref'] ?? 'main',$zipTmp,$token,$log);
            }
        break;
        case 'local_path':
            $downloadOk = plugin_copy_local_path($params['local_path'],$zipTmp,$log);
        break;
        default:
            $log[]='[erro] origem_tipo inválido'; plugin_log_block($log,$slug); return PLG_EXIT_VALIDATE;
    }
    if(!$downloadOk){
        $log[]='[erro] falha no download/obtenção do pacote';
        plugin_log_block($log,$slug); return PLG_EXIT_DOWNLOAD; // código distinto para erro download
    }
    if(!file_exists($zipTmp) || filesize($zipTmp) < 128){ // 128 bytes sanity
        $log[]='[erro] zip não disponível ou muito pequeno'; plugin_log_block($log,$slug); return PLG_EXIT_ZIP_INVALID; }
    
    // Verificar checksum se arquivo SHA256 foi baixado
    $sha256Tmp = sys_get_temp_dir() . "/plg_$slug.zip.sha256";
    if(file_exists($sha256Tmp) && filesize($sha256Tmp) > 0){
        $log[]='[info] verificando checksum SHA256';
        $checksum = plugin_compute_checksum($zipTmp);
        $expectedChecksum = trim(file_get_contents($sha256Tmp));
        if(!hash_equals($expectedChecksum, $checksum)){
            $log[]='[erro] checksum SHA256 não confere';
            plugin_log_block($log,$slug); return PLG_EXIT_CHECKSUM;
        }
        $log[]='[ok] checksum SHA256 verificado';
        unlink($sha256Tmp); // Remover arquivo temporário
    }
    
    $checksum = plugin_compute_checksum($zipTmp);
    if(!plugin_checksum_changed($slug,$checksum) && empty($params['reprocessar'])){
        $log[] = '[info] checksum inalterado – nenhum processamento adicional';
        plugin_log_block($log,$slug); return PLG_EXIT_OK;
    }
    if(!plugin_extract_zip($zipTmp, $staging, $log)) { plugin_log_block($log,$slug); return PLG_EXIT_PARAMS_OR_FILE; }
    $manifestPath = plugin_locate_manifest($staging,$log);
    if(!$manifestPath){ plugin_log_block($log,$slug); return PLG_EXIT_VALIDATE; }
    $errors=[]; $manifest = plugin_read_json($manifestPath,$errors);
    if(!$manifest || !plugin_validate_manifest($manifest,$errors)){
        foreach($errors as $e){ $log[]='[erro] '.$e; }
        plugin_log_block($log,$slug); return PLG_EXIT_VALIDATE;
    }
    // mover para final
    $finalPath = plugin_final_path($slug);
    $dryRun = !empty($params['dry_run']);
    $noResources = !empty($params['no_resources']);
    $onlyMigrations = !empty($params['only_migrations']);
    $onlyResources = !empty($params['only_resources']);
    // Movimentação de arquivos (pular se apenas migrações? ainda precisamos mover para ter migrations). Em dry-run não mover.
    if($dryRun){
        $log[]='[info] DRY-RUN: pulando move para diretório final';
    } else {
        if(!plugin_move_to_final($staging, $finalPath, $log)){ plugin_log_block($log,$slug); return PLG_EXIT_MOVE; }
    }
    // Migrações (se habilitadas)
    if(!$onlyResources){
        plugin_run_migrations($slug,$dryRun?$staging:$finalPath,$log,[
            'no_migrations'=>!empty($params['no_migrations']),
            'only_resources'=>$onlyResources
        ]);
    }
    // Sincronização de dados
    if(!$onlyMigrations && !$noResources){
        if($dryRun){
            $log[]='[info] DRY-RUN: analisando Data.json(s) sem aplicar no banco';
            // Apenas varrer para estatísticas
            $tmpLog=[]; $prevCount = count($tmpLog);
            // Reaproveita função mas intercepta upserts? Simples: não chamar plugin_sync_datajson, apenas detectar presença
            $hasMulti=false; 
            $files = glob($staging.'/db/data/*Data.json');
            if(count($files) > 0) $hasMulti=true;
            if($hasMulti){ $log[]='[info] multi-arquivos detectados (dry-run)'; }
            elseif(is_file($staging.'/db/data/Data.json')){ $log[]='[info] Data.json legado detectado (dry-run)'; }
        } else {
            plugin_sync_datajson($staging, $slug, $log);
        }
    } else {
        if($onlyMigrations) $log[]='[info] modo only-migrations: recursos não sincronizados';
        if($noResources) $log[]='[info] --no-resources: sincronização de recursos desativada';
    }
    
    // Limpeza da pasta DB e correção de permissões (após processamento)
    if(!$dryRun){
        plugin_cleanup_after_install($finalPath, $log);
    }
    
    // persistir
    plugin_persist_metadata($slug, $manifest, $checksum, $origem, [
        'referencia' => $params['referencia'] ?? ($params['owner'] ?? '').'/'.($params['repo'] ?? ''),
        'ref' => $params['ref'] ?? null,
        'cred_ref' => $params['cred_ref'] ?? null,
    ], $log);
    plugin_remove_dir($staging);
    $log[]='[ok] finalizado';
    plugin_log_block($log,$slug);
    return PLG_EXIT_OK;
}

