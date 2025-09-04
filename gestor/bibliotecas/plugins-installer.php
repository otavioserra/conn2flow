<?php
// Helpers para instalação/atualização de plugins (Fase 1)

require_once __DIR__ . '/banco.php';

function plugin_normalize_slug(string $slug): string { return strtolower(preg_replace('/[^a-zA-Z0-9_-]+/','-', $slug)); }
function plugin_base_root(): string { return dirname(__DIR__) . '/'; }
function plugin_staging_path(string $slug): string { return plugin_base_root() . 'temp/plugins/' . $slug; }
function plugin_final_path(string $slug): string { return plugin_base_root() . 'plugins/' . $slug; }
function plugin_datajson_dest_dir(string $slug): string { return plugin_base_root() . 'db/data/plugins/' . $slug; }
function plugin_safe_mkdir(string $path): void { if(!is_dir($path)) mkdir($path, 0777, true); }
function plugin_remove_dir(string $dir): void { if(!is_dir($dir)) return; $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $f){ $f->isDir()? rmdir($f->getPathname()):unlink($f->getPathname()); } rmdir($dir); }
function plugin_compute_checksum(string $file): ?string { return file_exists($file)? hash_file('sha256',$file): null; }

function plugin_read_json(string $path, array &$errors): ?array {
    if(!file_exists($path)){ $errors[] = "Arquivo não encontrado: $path"; return null; }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    if(json_last_error() !== JSON_ERROR_NONE){ $errors[] = "JSON inválido em $path: ".json_last_error_msg(); return null; }
    return $data;
}

function plugin_validate_manifest(array $manifest, array &$errors): bool {
    $required = ['id','nome','versao'];
    foreach($required as $r){ if(empty($manifest[$r])) $errors[] = "Campo obrigatório ausente: $r"; }
    if(!empty($manifest['versao']) && !preg_match('/^\d+\.\d+\.\d+$/',$manifest['versao'])) { $errors[] = 'Versão inválida (use semântica x.y.z)'; }
    // Prefixo de IDs (orientação – não bloqueia Fase 1)
    if(isset($manifest['id']) && !preg_match('/^[a-z0-9][a-z0-9_-]*$/',$manifest['id'])) { $errors[] = 'ID do plugin inválido (use minúsculas, números, hífen ou underscore)'; }
    return empty($errors);
}

function plugin_credentials_lookup(?string $credRef): ?string {
    if(!$credRef) return null;
    $envKey = 'PLUGIN_TOKEN_' . strtoupper($credRef);
    return $_ENV[$envKey] ?? null; // Fase 1: apenas via ENV
}

// === Download Helpers (GitHub) ===

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
        $hdrs = [];
        foreach($headers as $hK => $hV){ $hdrs[] = $hK.': '.$hV; }
        if($hdrs) curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
        curl_setopt($ch, CURLOPT_USERAGENT, 'conn2flow-plugin-installer/1.0');
        $response = curl_exec($ch);
        $errno = curl_errno($ch); $err = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
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
    $url = plugin_github_zip_url($owner,$repo,$ref ?: 'main');
    return plugin_http_download($url,$destZip,[ 'Accept' => 'application/vnd.github+json' ],$log);
}

function plugin_download_github_private(string $owner,string $repo,string $ref,string $destZip,string $token, array &$log): bool {
    if(!$token){ $log[]='[erro] token vazio'; return false; }
    if(!$owner || !$repo){ $log[]='[erro] owner/repo ausentes'; return false; }
    $url = plugin_github_zip_url($owner,$repo,$ref ?: 'main');
    // Máscara simples para log
    $log[]='[info] utilizando token privado (***'.substr($token,-4).')';
    return plugin_http_download($url,$destZip,[
        'Accept' => 'application/vnd.github+json',
        'Authorization' => 'Bearer '.$token
    ],$log);
}

function plugin_copy_local_path(string $sourcePath, string $destZip, array &$log): bool {
    if(!is_dir($sourcePath)) { $log[] = "[erro] local_path não encontrado: $sourcePath"; return false; }
    // Criar zip temporário do diretório (simplificado)
    $zip = new ZipArchive();
    if($zip->open($destZip, ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true){ $log[]="[erro] falha criar zip temporário"; return false; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourcePath, FilesystemIterator::SKIP_DOTS));
    foreach($it as $file){ $fp = $file->getPathname(); $rel = substr($fp, strlen($sourcePath)+1); if(!$file->isDir()) $zip->addFile($fp, $rel); }
    $zip->close();
    $log[] = "[ok] pacote local zipado";
    return true;
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
    // mover copiando
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($staging, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach($it as $item){
        $targetPath = $final . DIRECTORY_SEPARATOR . substr($item->getPathname(), strlen($staging)+1);
        if($item->isDir()){
            if(!is_dir($targetPath)) mkdir($targetPath,0777,true);
        } else {
            copy($item->getPathname(), $targetPath);
        }
    }
    $log[] = "[ok] diretório final atualizado: $final";
    return true;
}

function plugin_persist_metadata(string $slug, array $manifest, ?string $checksum, string $origemTipo, array $opcoes, array &$log): void {
    $dados = [
        'id' => $slug,
        'nome' => $manifest['nome'] ?? $slug,
        'origem_tipo' => $origemTipo,
        'origem_referencia' => $opcoes['referencia'] ?? null,
        'origem_branch_tag' => $opcoes['ref'] ?? null,
        'origem_credencial_ref' => $opcoes['cred_ref'] ?? null,
        'versao_instalada' => $manifest['versao'] ?? null,
        'checksum_pacote' => $checksum,
        'manifest_json' => json_encode($manifest, JSON_UNESCAPED_UNICODE),
    'status_execucao' => PLG_STATUS_OK,
        'data_instalacao' => date('Y-m-d H:i:s'),
        'data_ultima_atualizacao' => date('Y-m-d H:i:s'),
    ];
    // Upsert manual via banco_insert_update sem tipagem especial
    banco_insert_update([
        'tabela' => ['nome' => 'plugins','id' => 'id'],
        'dados' => $dados,
    ]);
    $log[] = '[ok] metadados persistidos na tabela plugins';
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
    // NOVO SUPORTE: Multi-arquivos (LayoutsData.json, PaginasData.json, ComponentesData.json, VariaveisData.json)
    $finalBase = plugin_final_path($slug); // já movido
    $dataDirCandidateRoots = [];
    // Preferir diretório final (já movido), fallback staging (legado Data.json)
    if(is_dir($finalBase.'/db/data')) $dataDirCandidateRoots[] = $finalBase.'/db/data';
    if(is_dir($staging.'/db/data')) $dataDirCandidateRoots[] = $staging.'/db/data';
    // Detecta se existem arquivos individuais
    $multiFiles = ['layouts'=>'LayoutsData.json','paginas'=>'PaginasData.json','componentes'=>'ComponentesData.json','variaveis'=>'VariaveisData.json'];
    $multiFound = [];
    foreach($dataDirCandidateRoots as $root){
        foreach($multiFiles as $k=>$f){
            $p = $root.'/'.$f;
            if(is_file($p)) $multiFound[$k] = $p;
        }
        if(count($multiFound) > 0) break; // primeira raiz válida
    }

    if($multiFound){
        $log[]='[ok] Detectado modo multi-arquivos de dados ('.implode(', ', array_keys($multiFound)).')';
        plugin_sync_datajson_multi($multiFound,$slug,$log,$finalBase);
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
 * Sincroniza dados a partir de arquivos separados *Data.json.
 * Copia arquivos para diretório central gestor/db/data/plugins/<slug>/ e realiza upsert nas tabelas.
 */
function plugin_sync_datajson_multi(array $filesMap, string $slug, array &$log, string $finalBase): void {
    $destDir = plugin_datajson_dest_dir($slug);
    plugin_safe_mkdir($destDir);
    $tot=['inserts'=>0,'updates'=>0,'skipped'=>0,'layouts'=>0,'pages'=>0,'components'=>0,'variables'=>0];
    $loadJson = function(string $file, array &$log): array {
        if(!is_file($file)) return [];
        $raw = file_get_contents($file);
        $d = json_decode($raw,true);
        if(json_last_error()!==JSON_ERROR_NONE){ $log[]='[erro] JSON inválido: '.$file.' -> '.json_last_error_msg(); return []; }
        return is_array($d)?$d:[];
    };
    // Copiar e processar cada tipo
    foreach($filesMap as $tipo=>$path){
        @copy($path, $destDir.'/'.basename($path));
        $rows = $loadJson($path,$log);
        foreach($rows as $row){
            // Normaliza nomes de campos esperados pelos upserts
            if($tipo==='layouts'){
                $lang = $row['language'] ?? $row['linguagem_codigo'] ?? null; $id = $row['id'] ?? null; if(!$lang||!$id) continue;
                plugin_upsert_layout($slug,$lang,$id,$row,$tot);
            } elseif($tipo==='paginas') {
                $lang = $row['language'] ?? null; $id = $row['id'] ?? null; if(!$lang||!$id) continue;
                // Ajusta chave 'caminho' para 'path' no array fonte se necessário (upsert usa caminho/ c.
                if(isset($row['caminho']) && !isset($row['path'])) $row['path']=$row['caminho'];
                plugin_upsert_page($slug,$lang,$id,$row,$tot);
            } elseif($tipo==='componentes') {
                $lang = $row['language'] ?? null; $id = $row['id'] ?? null; if(!$lang||!$id) continue;
                plugin_upsert_component($slug,$lang,$id,$row,$tot);
            } elseif($tipo==='variaveis') {
                $lang = $row['linguagem_codigo'] ?? $row['language'] ?? null; $id = $row['id'] ?? null; if(!$lang||!$id) continue;
                plugin_upsert_variable($slug,$lang,$id,$row,$tot);
            }
        }
    }
    plugin_sync_modules_resources($finalBase,$slug,$log); // inclui módulos se existirem
    $log[]='[ok] multi-data sincronizado plugin='.$slug.' layouts='.$tot['layouts'].' pages='.$tot['pages'].' components='.$tot['components'].' variables='.$tot['variables'].' inserts='.$tot['inserts'].' updates='.$tot['updates'].' skipped='.$tot['skipped'];
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
            banco_update_campo('data_modificacao','NOW()',true);
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
            // Incrementa versao
            banco_update_set_expr('versao','versao+1');
            banco_update_campo('data_modificacao','NOW()',true);
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
        banco_insert_name_campo('data_modificacao','NOW()',true,true);
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
                banco_update_campo('data_modificacao','NOW()',true);
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
            banco_update_set_expr('versao','versao+1');
            banco_update_campo('data_modificacao','NOW()',true);
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
        banco_insert_name_campo('data_modificacao','NOW()',true,true);
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
                banco_update_campo('data_modificacao','NOW()',true);
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
            banco_update_set_expr('versao','versao+1');
            banco_update_campo('data_modificacao','NOW()',true);
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
        banco_insert_name_campo('data_modificacao','NOW()',true,true);
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
            banco_update_campo('data_modificacao','NOW()',true);
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
            if(!isset($params['arquivo']) || !file_exists($params['arquivo'])){ $log[]='[erro] arquivo upload não encontrado'; plugin_log_block($log,$slug); return PLG_EXIT_PARAMS_OR_FILE; }
            copy($params['arquivo'], $zipTmp); $downloadOk = true; $log[]='[ok] arquivo upload copiado';
        break;
        case 'github_publico':
            $downloadOk = plugin_download_github_public($params['owner'],$params['repo'],$params['ref'] ?? 'main',$zipTmp,$log);
        break;
        case 'github_privado':
            $token = plugin_credentials_lookup($params['cred_ref'] ?? null);
            if(!$token){ $log[]='[erro] credencial não resolvida'; plugin_log_block($log,$slug); return PLG_EXIT_PARAMS_OR_FILE; }
            $downloadOk = plugin_download_github_private($params['owner'],$params['repo'],$params['ref'] ?? 'main',$zipTmp,$token,$log);
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
            $hasMulti=false; foreach(['LayoutsData.json','PaginasData.json','ComponentesData.json','VariaveisData.json'] as $f){ if(is_file($staging.'/db/data/'.$f)) $hasMulti=true; }
            if($hasMulti){ $log[]='[info] multi-arquivos detectados (dry-run)'; }
            elseif(is_file($staging.'/db/data/Data.json')){ $log[]='[info] Data.json legado detectado (dry-run)'; }
        } else {
            plugin_sync_datajson($staging, $slug, $log);
        }
    } else {
        if($onlyMigrations) $log[]='[info] modo only-migrations: recursos não sincronizados';
        if($noResources) $log[]='[info] --no-resources: sincronização de recursos desativada';
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

