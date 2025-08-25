<?php
/**
 * Sistema de Atualizações do Conn2Flow (Fase 1)
 * Atualiza arquivos do gestor + merge .env + atualizações de banco de dados.
 * Segue especificação em: ai-workspace/prompts/atualizacoes/atualizacoes-sistema.md
 */
declare(strict_types=1);

// ----------------------------
// Constantes / Códigos Saída
// ----------------------------
const EXIT_OK           = 0;
const EXIT_GENERIC      = 1;
const EXIT_DOWNLOAD     = 2;
const EXIT_EXTRACTION   = 3;
const EXIT_ENV_MERGE    = 4;
const EXIT_DB_ERROR     = 5;
const EXIT_ROLLBACK     = 6; // Reservado Fase 2
const EXIT_INTEGRITY    = 7; // Falha verificação integridade (checksum ZIP)

// ----------------------------
// Exceções customizadas
// ----------------------------
class DownloadException extends RuntimeException {}
class ExtractionException extends RuntimeException {}
class EnvMergeException extends RuntimeException {}
class DatabaseUpdateException extends RuntimeException {}
class IntegrityException extends RuntimeException {}

// ----------------------------
// Contexto Global (imutável após init)
// ----------------------------
$BASE_PATH = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR; // gestor/
$LOGS_DIR  = $BASE_PATH . 'logs' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR;
$TEMP_DIR  = $BASE_PATH . 'temp' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR;
@is_dir($LOGS_DIR) || @mkdir($LOGS_DIR, 0775, true);
@is_dir($TEMP_DIR) || @mkdir($TEMP_DIR, 0775, true);
$DATE_SUFFIX = date('Ymd');
$LOG_FILE = $LOGS_DIR . 'atualizacoes-sistema-' . $DATE_SUFFIX . '.log';

// Estrutura do contexto mutável
$CONTEXT = [
    'opts' => [],
    'plan' => null,
    'start_time' => microtime(true),
    'staging_dir' => null,
    'zip_path' => null,
    'checksum' => null,
    'backups' => [],
    'env_merge' => [ 'added' => [], 'deprecated' => [] ],
    'conflicts' => [],
    'errors' => [],
    'mode' => 'full',
    'release_tag' => null,
    'debug' => false,
];

// ----------------------------
// Logging Helpers
// ----------------------------
function logAtualizacao(string $msg, string $level = 'INFO', bool $force = false): void {
    global $LOG_FILE, $CONTEXT;
    if (!$force && $level === 'DEBUG' && empty($CONTEXT['debug'])) return; // ignora debug se não ativado
    $ts = date('Y-m-d H:i:s');
    $line = "[$ts][$level] $msg" . PHP_EOL;
    file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
function logErroCtx(string $msg): void { logAtualizacao($msg, 'ERROR', true); }

// ----------------------------
// CLI Parsing & Ajuda
// ----------------------------
function parseArgs(array $argv): array {
    $out = [];
    foreach ($argv as $i => $a) {
        if ($i === 0) continue;
        if (substr($a,0,2) === '--') {
            $eq = strpos($a,'=');
            if ($eq !== false) {
                $k = substr($a,2,$eq-2); $v = substr($a,$eq+1); $out[$k] = $v;
            } else {
                $out[substr($a,2)] = true;
            }
        }
    }
    if (isset($out['version']) && !isset($out['tag'])) $out['tag'] = $out['version'];
    if (isset($out['env-dir']) && !isset($out['domain'])) $out['domain'] = $out['env-dir'];
    return $out;
}

function help(): void {
    echo "Conn2Flow - Sistema de Atualizações (Fase 1)\n";
    echo "Uso: php atualizacoes-sistema.php [--flags]\n\n";
    echo "Principais Flags:\n";
    echo "  --tag=GESTOR_TAG        Especifica release (gestor-vX.Y.Z)\n";
    echo "  --domain=DOMINIO        Ambiente (pasta autenticacoes/<dominio>)\n";
    echo "  --only-files            Apenas atualização de arquivos + merge .env\n";
    echo "  --only-db               Apenas banco de dados\n";
    echo "  --no-db                 Pula banco (igual full sem DB)\n";
    echo "  --download-only         Baixa, extrai e gera plano sem aplicar\n";
    echo "  --skip-download         Usa ZIP já existente em staging\n";
    echo "  --backup                Cria backup de arquivos alterados\n";
    echo "  --dry-run               Simula (gera plano, não aplica)\n";
    echo "  --no-verify             Desativa verificação SHA256 do arquivo gestor.zip\n";
    echo "  --force-all             Encaminha ao script de banco\n";
    echo "  --tables=lista          Limita tabelas (ex: paginas,variaveis)\n";
    echo "  --log-diff              Log detalhado de diffs banco\n";
    echo "  --debug                 Verbosidade maior (DEBUG logs)\n";
    echo "  --clean-temp            Remove staging ao final mesmo em dry-run\n";
    echo "  --help                  Exibe esta ajuda\n";
    echo "\nPastas ignoradas na atualização de arquivos (proteção dados usuário): logs/, backups/, temp/, contents/\n";
}

function validarOpts(array &$opts): void {
    if (!empty($opts['only-files']) && !empty($opts['only-db'])) {
        throw new InvalidArgumentException('Flags conflitantes: --only-files e --only-db');
    }
    if (empty($opts['domain'])) {
        $opts['domain'] = 'localhost'; // Fallback
    }
}

// ----------------------------
// Staging / Ambiente
// ----------------------------
function prepararStaging(): string {
    global $TEMP_DIR;
    $dir = $TEMP_DIR . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . DIRECTORY_SEPARATOR;
    if (!@mkdir($dir, 0775, true)) {
        throw new RuntimeException('Falha ao criar staging: ' . $dir);
    }
    return $dir;
}

// ----------------------------
// Release Discovery & Download
// ----------------------------
function descobrirUltimaTagGestor(): array {
    $url = 'https://api.github.com/repos/otavioserra/conn2flow/releases';
    $ch = curl_init();
    curl_setopt_array($ch,[
        CURLOPT_URL=>$url,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_SSL_VERIFYPEER=>false,
        CURLOPT_USERAGENT=>'Conn2Flow-Update/1.0',
        CURLOPT_TIMEOUT=>30,
        CURLOPT_HTTPHEADER=>['Accept: application/vnd.github+json']
    ]);
    $resp = curl_exec($ch); $code = curl_getinfo($ch,CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    if ($code!==200 || !$resp) throw new DownloadException('Falha releases GitHub HTTP '.$code.' '.$err);
    $data = json_decode($resp,true); if(!is_array($data)) throw new DownloadException('JSON releases inválido');
    foreach ($data as $release) {
        if (!empty($release['tag_name']) && strpos($release['tag_name'],'gestor-v')===0) {
            return [ 'tag'=>$release['tag_name'], 'published_at'=>$release['published_at'] ?? null ];
        }
    }
    throw new DownloadException('Nenhuma tag gestor-v encontrada');
}

function downloadRelease(string $tag, string $destDir): string {
    $url = "https://github.com/otavioserra/conn2flow/releases/download/$tag/gestor.zip";
    $zipPath = rtrim($destDir,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'gestor.zip';
    logAtualizacao("Download: $tag ($url)");
    $ch = curl_init();
    curl_setopt_array($ch,[
        CURLOPT_URL=>$url,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_SSL_VERIFYPEER=>false,
        CURLOPT_USERAGENT=>'Conn2Flow-Update/1.0',
        CURLOPT_TIMEOUT=>120
    ]);
    $data = curl_exec($ch); $code = curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
    if ($code!==200 || !$data) throw new DownloadException('Falha download HTTP '.$code.' '.$err);
    if (file_put_contents($zipPath,$data)===false) throw new DownloadException('Falha salvar zip: '.$zipPath);
    return $zipPath;
}

// ----------------------------
// Integridade simples via SHA256 do ZIP
// ----------------------------
function downloadZipChecksum(string $tag, string $stagingDir): string {
    $url = "https://github.com/otavioserra/conn2flow/releases/download/$tag/gestor.zip.sha256";
    $dest = $stagingDir.'gestor.zip.sha256';
    $ch=curl_init();
    curl_setopt_array($ch,[
        CURLOPT_URL=>$url,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_SSL_VERIFYPEER=>false,
        CURLOPT_USERAGENT=>'Conn2Flow-Update/1.0',
        CURLOPT_TIMEOUT=>30
    ]);
    $data=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
    if($code!==200 || !$data) throw new IntegrityException('Não foi possível baixar checksum SHA256 (HTTP '.$code.' '.$err.')');
    if(file_put_contents($dest,$data)===false) throw new IntegrityException('Falha salvar checksum sha256');
    return $dest;
}
function parseSha256File(string $file): string {
    $c=trim(@file_get_contents($file)?:''); if($c==='') throw new IntegrityException('Arquivo sha256 vazio');
    // formatos aceitos: "hash  nome" ou somente hash
    if(preg_match('/^([a-f0-9]{64})/i',$c,$m)) return strtolower($m[1]);
    throw new IntegrityException('Formato sha256 inválido');
}
function computeFileSha256(string $file): string {
    $h=hash_file('sha256',$file); if(!$h) throw new IntegrityException('Falha calcular sha256 local'); return $h; }
function verifyZipSha256(string $zipPath, string $checksumFile): array {
    $expected=parseSha256File($checksumFile); $got=computeFileSha256($zipPath);
    if($expected!==$got) throw new IntegrityException('Checksum ZIP divergente (esperado '.$expected.' obteve '.$got.')');
    logAtualizacao('Checksum ZIP verificado: '.$got);
    return ['expected'=>$expected,'got'=>$got,'file'=>basename($zipPath)];
}

// ----------------------------
// Extração
// ----------------------------
function extrairZipGestor(string $zipPath, string $stagingDir): string {
    if (!file_exists($zipPath)) throw new ExtractionException('ZIP inexistente: '.$zipPath);
    $zip = new ZipArchive(); $r=$zip->open($zipPath);
    if ($r!==true) throw new ExtractionException('Falha abrir zip code='.$r);
    if (!$zip->extractTo($stagingDir)) { $zip->close(); throw new ExtractionException('Falha extrair zip'); }
    $zip->close();
    return $stagingDir;
}

// ----------------------------
// ----------------------------
// Substituição total (com remoção de arquivos obsoletos) preservando contents/
// ----------------------------
function coletarArquivos(string $base, array $excludesPattern): array {
    $result=[]; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,RecursiveDirectoryIterator::SKIP_DOTS));
    foreach($it as $f){ if($f->isDir()) continue; $rel=substr($f->getPathname(), strlen($base)); $rel=str_replace('\\','/',$rel); $skip=false; foreach($excludesPattern as $pat){ if(preg_match($pat,$rel)){ $skip=true; break; } } if(!$skip) $result[$rel]=$f->getPathname(); }
    return $result;
}
function backupTotal(string $origemBase, string $destBase, array $excludesPattern): void {
    if(!@mkdir($destBase,0775,true) && !is_dir($destBase)) throw new RuntimeException('Falha criar dir backup');
    $files=coletarArquivos($origemBase,$excludesPattern);
    foreach($files as $rel=>$src){ $dest=$destBase.$rel; $dir=dirname($dest); if(!is_dir($dir)) @mkdir($dir,0775,true); @copy($src,$dest); }
}
function aplicarOverwriteTotal(string $stagingDir, string $basePath, array $excludesPattern, bool $dry): array {
    $stagingFiles=coletarArquivos($stagingDir,$excludesPattern);
    $baseFiles=coletarArquivos($basePath,$excludesPattern);
    // Remover arquivos que não existem mais no staging
    $removed=0; if(!$dry){ foreach($baseFiles as $rel=>$full){ if(!isset($stagingFiles[$rel])) { @unlink($full); $removed++; } } }
    // Copiar / sobrescrever
    $copied=0; if(!$dry){ foreach($stagingFiles as $rel=>$src){ $dest=$basePath.$rel; $dir=dirname($dest); if(!is_dir($dir)) @mkdir($dir,0775,true); @copy($src,$dest); $copied++; } }
    return ['removed'=>$removed,'copied'=>$copied,'total_new'=>count($stagingFiles)];
}

// ----------------------------
// Backup / Aplicação Arquivos
// ----------------------------
// Funções antigas de diff/atualização granular removidas nesta versão simplificada

// ----------------------------
// Merge .env Avançado (adiciona novas variáveis e registra deprecadas)
// ----------------------------
function parseEnvLines(array $lines): array {
    $map=[]; foreach($lines as $idx=>$l){ if(preg_match('/^([A-Z0-9_]+)=(.*)$/i',$l,$m)) $map[$m[1]]=['value'=>$m[2],'index'=>$idx]; } return $map; }

function mergeEnv(string $envAtualPath, string $envTemplatePath, array &$context, bool $dryRun): void {
    if(!file_exists($envAtualPath) || !file_exists($envTemplatePath)) { logAtualizacao('Merge .env ignorado (arquivos ausentes)','WARNING'); return; }
    $curLines = file($envAtualPath, FILE_IGNORE_NEW_LINES);
    $tplLines = file($envTemplatePath, FILE_IGNORE_NEW_LINES);
    $curMap = parseEnvLines($curLines); $tplMap=parseEnvLines($tplLines);
    $added=[]; $deprecated=[];
    foreach($tplMap as $k=>$v){ if(!isset($curMap[$k])) $added[$k]=$v['value']; }
    foreach($curMap as $k=>$v){ if(!isset($tplMap[$k])) $deprecated[]=$k; }
    if(!$dryRun && $added){
        $append="\n# added-by-update ".date('Y-m-d')."\n";
        foreach($added as $k=>$val){ $append.=$k.'='.$val."\n"; }
        file_put_contents($envAtualPath,$append,FILE_APPEND);
    }
    $context['env_merge']['added']=array_keys($added);
    $context['env_merge']['deprecated']=$deprecated; // apenas log
    logAtualizacao('Merge .env: novas='.count($added).' deprecated='.count($deprecated));
}

// ----------------------------
// Atualização Banco
// ----------------------------
function executarAtualizacaoBanco(array $opts): void {
    global $BASE_PATH;
    $script=$BASE_PATH.'controladores'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'atualizacoes-banco-de-dados.php';
    if(!file_exists($script)) throw new DatabaseUpdateException('Script de banco ausente');
    $GLOBALS['CLI_OPTS']=['env-dir'=>$opts['domain']??'localhost'];
    foreach(['debug','force-all','tables','log-diff'] as $k){ if(isset($opts[$k])) $GLOBALS['CLI_OPTS'][$k]=$opts[$k]; }
    logAtualizacao('Banco: iniciando');
    try {
        require $script; // script controla seu fluxo
        logAtualizacao('Banco: concluído');
    } catch (Throwable $t){
        throw new DatabaseUpdateException('Falha atualização banco: '.$t->getMessage());
    }
}

// ----------------------------
// Plano JSON / Relatório
// ----------------------------
function exportarPlanoJson(array $plan, array $context): string {
    global $LOGS_DIR;
    $out=[
        'generated_at'=>date(DATE_ATOM),
        'mode'=>$context['mode'],
    'plan'=>$plan,
        'dry_run'=>!empty($context['opts']['dry-run']),
    'env_merge'=>$context['env_merge'],
        'release_tag'=>$context['release_tag'],
    'stats'=>$plan['stats']??[],
    'checksum'=>$context['checksum'],
    ];
    $file=$LOGS_DIR.'plan-'.date('Ymd-His').'.json';
    file_put_contents($file,json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    logAtualizacao('Plano exportado: '.$file);
    return $file;
}

function renderRelatorioFinal(array $context): string {
    $dur = microtime(true)-$context['start_time'];
    $p=$context['plan']; $stats=$p['stats']??['removed'=>0,'copied'=>0];
    return 'Atualização concluída em '.number_format($dur,2).'s | copied='.$stats['copied'].' removed='.$stats['removed'].' envAdded='.count($context['env_merge']['added'])."\n";
}

// ----------------------------
// Limpeza (staging)
// ----------------------------
function removeDirectoryRecursive(string $dir): void {
    if(!is_dir($dir)) return; $it=new RecursiveDirectoryIterator($dir,RecursiveDirectoryIterator::SKIP_DOTS); $ri=new RecursiveIteratorIterator($it,RecursiveIteratorIterator::CHILD_FIRST);
    foreach($ri as $f){ if($f->isDir()) @rmdir($f->getPathname()); else @unlink($f->getPathname()); }
    @rmdir($dir);
}

// ----------------------------
// Hooks (Fase 1 placeholders)
// ----------------------------
function hookBeforeFiles(array &$context): void {}
function hookAfterDb(array &$context): void {}
function hookAfterAll(array &$context): void {}

// ----------------------------
// MAIN
// ----------------------------
function main(array $argv): int {
    global $CONTEXT, $BASE_PATH;
    try {
        $opts=parseArgs($argv); if(isset($opts['help'])) { help(); return EXIT_OK; }
        validarOpts($opts); $CONTEXT['opts']=$opts; $CONTEXT['debug']=!empty($opts['debug']);
        $dry = !empty($opts['dry-run']);
        $onlyFiles = !empty($opts['only-files']);
        $onlyDb = !empty($opts['only-db']);
        $noDb = !empty($opts['no-db']);
        $CONTEXT['mode']=$onlyFiles?'only-files':($onlyDb?'only-db':($noDb?'files-without-db':'full'));
        logAtualizacao('Iniciando atualização modo='.$CONTEXT['mode'].' dryRun='.($dry?'1':'0'));
        hookBeforeFiles($CONTEXT);

        // ---------------- Arquivos (modo simplificado overwrite) ----------------
        if(!$onlyDb){
            $staging=prepararStaging(); $CONTEXT['staging_dir']=$staging;
            // Descobrir tag
            if(!empty($opts['tag'])) { $CONTEXT['release_tag']=$opts['tag']; }
            else { $info=descobrirUltimaTagGestor(); $CONTEXT['release_tag']=$info['tag']; }
            $tag=$CONTEXT['release_tag'];
            $zip = !empty($opts['skip-download']) ? ($staging.'gestor.zip') : downloadRelease($tag,$staging);
            if(!file_exists($zip)) throw new DownloadException('ZIP esperado não encontrado (skip-download?)');
            $CONTEXT['zip_path']=$zip;
            // Checksum
            if(empty($opts['no-verify'])){
                $shaFile = downloadZipChecksum($tag,$staging);
                $CONTEXT['checksum']=verifyZipSha256($zip,$shaFile);
            } else { logAtualizacao('Verificação checksum desativada (--no-verify)','WARNING'); }
            extrairZipGestor($zip,$staging);
            // Backup total se solicitado
            $excludes=['#^contents/#','#^logs/#','#^backups/#','#^temp/#'];
            if(!empty($opts['backup']) && !$dry){
                $backupDir = $BASE_PATH.'backups'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'full'.DIRECTORY_SEPARATOR.date('Ymd-His').DIRECTORY_SEPARATOR;
                backupTotal($BASE_PATH,$backupDir,$excludes);
                $CONTEXT['backups'][]=['full_backup'=>$backupDir];
            }
            // Aplicar overwrite
            $stats = aplicarOverwriteTotal($staging,$BASE_PATH,$excludes,$dry);
            // Merge .env (adiciona novas variáveis)
            $envAtual=$BASE_PATH.'autenticacoes'.DIRECTORY_SEPARATOR.($opts['domain']??'localhost').DIRECTORY_SEPARATOR.'.env';
            $envTpl=$staging.'autenticacoes.exemplo'.DIRECTORY_SEPARATOR.'dominio'.DIRECTORY_SEPARATOR.'.env';
            mergeEnv($envAtual,$envTpl,$CONTEXT,$dry);
            $CONTEXT['plan']=['stats'=>$stats];
            exportarPlanoJson($CONTEXT['plan'],$CONTEXT);
        }

        // ---------------- Banco ----------------
        if(!$onlyFiles && !$noDb){
            executarAtualizacaoBanco($opts);
            hookAfterDb($CONTEXT);
        }

        hookAfterAll($CONTEXT);
        $rel=renderRelatorioFinal($CONTEXT); logAtualizacao(trim($rel)); echo $rel;
        if(!empty($opts['clean-temp']) && $CONTEXT['staging_dir']) removeDirectoryRecursive($CONTEXT['staging_dir']);
        return EXIT_OK;
    } catch (DownloadException $e){ logErroCtx($e->getMessage()); echo "ERRO DOWNLOAD: ".$e->getMessage()."\n"; return EXIT_DOWNLOAD; }
    catch (ExtractionException $e){ logErroCtx($e->getMessage()); echo "ERRO EXTRAÇÃO: ".$e->getMessage()."\n"; return EXIT_EXTRACTION; }
    catch (EnvMergeException $e){ logErroCtx($e->getMessage()); echo "ERRO ENV: ".$e->getMessage()."\n"; return EXIT_ENV_MERGE; }
    catch (DatabaseUpdateException $e){ logErroCtx($e->getMessage()); echo "ERRO BANCO: ".$e->getMessage()."\n"; return EXIT_DB_ERROR; }
    catch (IntegrityException $e){ logErroCtx($e->getMessage()); echo "ERRO INTEGRIDADE: ".$e->getMessage()."\n"; return EXIT_INTEGRITY; }
    catch (InvalidArgumentException $e){ logErroCtx($e->getMessage()); echo "ARGUMENTO INVÁLIDO: ".$e->getMessage()."\n"; return EXIT_GENERIC; }
    catch (Throwable $t){ logErroCtx('Fatal: '.$t->getMessage()); echo "ERRO FATAL: ".$t->getMessage()."\n"; return EXIT_GENERIC; }
}

if(PHP_SAPI==='cli') exit(main($argv));

// Execução via web futura: chamar main([]) com parâmetros simulados.
