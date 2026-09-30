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
const EXIT_LOCKED       = 8; // req-197: outro deploy em execução no ambiente (trava viva)

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
// Preferir raiz já definida pelo core do Gestor (config.php) quando disponível.
global $_GESTOR, $BASE_PATH, $LOGS_DIR, $TEMP_DIR, $LOG_FILE, $CONTEXT;

if (isset($_GESTOR) && !empty($_GESTOR['ROOT_PATH'])) {
    $BASE_PATH = rtrim($_GESTOR['ROOT_PATH'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR; // raiz gestor
} else {
    $BASE_PATH = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR; // fallback CLI/include direto
}
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
    // Execução web incremental
    'session_id' => null,
    'session_log' => null,
];

// ----------------------------
// Logging Helpers
// ----------------------------
function logAtualizacao(string $msg, string $level = 'INFO', bool $force = false): void {
    global $LOG_FILE, $CONTEXT, $BASE_PATH;
    // Fallback defensivo: se LOG_FILE não inicializado (ex: include parcial), reconstruir
    if (empty($LOG_FILE) || !is_string($LOG_FILE)) {
        $logsDir = $BASE_PATH . 'logs' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR;
        @is_dir($logsDir) || @mkdir($logsDir,0775,true);
        $LOG_FILE = $logsDir.'atualizacoes-sistema-'.date('Ymd').'.log';
    }
    if (!$force && $level === 'DEBUG' && empty($CONTEXT['debug'])) return; // ignora debug se não ativado
    $ts = date('Y-m-d H:i:s');
    // Prefixa ID de sessão (execução web) para facilitar rastreio cruzado no log diário
    $prefixSess = $CONTEXT['session_id'] ? '[SID:'.$CONTEXT['session_id'].']' : '';
    $line = "[$ts][$level]$prefixSess $msg" . PHP_EOL;
    if (is_string($LOG_FILE)) {
        @file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    }
    // Também grava em log dedicado da sessão web (se existir)
    if (!empty($CONTEXT['session_log']) && is_string($CONTEXT['session_log'])) {
        @file_put_contents($CONTEXT['session_log'], $line, FILE_APPEND | LOCK_EX);
    }
    // req-201: `--log-stdout` repete cada linha na saída padrão (a execução disparada pela API grava a saída
    // num log próprio e lê dele o snapshot, a verificação e o rollback).
    if (PHP_SAPI === 'cli' && in_array('--log-stdout', (array)($GLOBALS['argv'] ?? []), true)) echo $line;
}

// ----------------------------
// Persistência (tabela atualizacoes_execucoes) - utilitários simples
// ----------------------------
function db_exec_insert(array $data): ?int {
    // Usa funções banco_* se disponíveis no runtime do gestor
    if(!function_exists('banco_insert_name_campo')) return null;
    foreach($data as $k=>$v){
        if($v===null){ banco_insert_name_campo($k,'NULL',true,true); }
        elseif(is_int($v) || is_float($v)){ banco_insert_name_campo($k,(string)$v,true,true); }
        else { banco_insert_name_campo($k,(string)$v,false,true); }
    }
    banco_insert_name(banco_insert_name_campos(),'atualizacoes_execucoes');
    if(function_exists('mysqli_insert_id')){ global $_BANCO; if(isset($_BANCO['conexao'])) return (int)mysqli_insert_id($_BANCO['conexao']); }
    return null;
}
function db_exec_update(int $id, array $fields): void {
    if(!function_exists('banco_query')) return; if(!$id) return;
    $sets=[]; foreach($fields as $k=>$v){ if($v===null) $sets[]="$k=NULL"; else { $val=function_exists('banco_escape_field')?banco_escape_field((string)$v):addslashes((string)$v); $sets[]="$k='".$val."'"; } }
    if(!$sets) return; $sql='UPDATE atualizacoes_execucoes SET '.implode(',',$sets)." WHERE id_atualizacoes_execucoes=".(int)$id.' LIMIT 1'; banco_query($sql);
}
function persist_inicio_execucao(array $ctx): ?int {
    $data=[
        'session_id'=>$ctx['session_id']??null,
        'modo'=>$ctx['mode']??null,
        'release_tag'=>$ctx['release_tag']??null,
        'checksum'=>is_array($ctx['checksum']??null)?($ctx['checksum']['value']??null):($ctx['checksum']??null),
        'env_added'=>isset($ctx['env_merge']['added'])?count($ctx['env_merge']['added']):0,
        'stats_removed'=>null,
        'stats_copied'=>null,
        'started_at'=>date('Y-m-d H:i:s'),
        'status'=>'running',
        'exit_code'=>null,
        'created_at'=>date('Y-m-d H:i:s'),
        'updated_at'=>date('Y-m-d H:i:s'),
    ];
    return db_exec_insert($data);
}
function persist_parcial_execucao(int $id, array $ctx): void {
    if(!$id) return; $stats=$ctx['plan']['stats']??[]; db_exec_update($id,[
        'env_added'=>isset($ctx['env_merge']['added'])?count($ctx['env_merge']['added']):0,
        'stats_removed'=>$stats['removed']??null,
        'stats_copied'=>$stats['copied']??null,
        'updated_at'=>date('Y-m-d H:i:s'),
    ]);
}
function persist_final_execucao(int $id, array $ctx, int $exitCode, ?string $erro=null): void {
    if(!$id) return; $stats=$ctx['plan']['stats']??[];
    // Se stats vazios, tenta extrair de arquivo plano recente gerado nesta execução
    if((!isset($stats['removed']) || !isset($stats['copied'])) && isset($ctx['last_plan_file']) && is_file($ctx['last_plan_file'])){
        $planJson = json_decode(@file_get_contents($ctx['last_plan_file']), true);
        if(is_array($planJson) && isset($planJson['stats'])) $stats = $planJson['stats'];
    }
    // Se ainda não temos stats, preservar valores já existentes na linha (evitar sobrescrever com NULL)
    if(!isset($stats['removed']) || !isset($stats['copied'])){
        $row = db_exec_select_rows('SELECT stats_removed,stats_copied FROM atualizacoes_execucoes WHERE id_atualizacoes_execucoes='.(int)$id.' LIMIT 1');
        if($row && isset($row[0])){
            if(!isset($stats['removed']) && isset($row[0]['stats_removed'])) $stats['removed'] = $row[0]['stats_removed'];
            if(!isset($stats['copied']) && isset($row[0]['stats_copied'])) $stats['copied'] = $row[0]['stats_copied'];
        }
    }
    db_exec_update($id,[
        'stats_removed'=>$stats['removed']??null,
        'stats_copied'=>$stats['copied']??null,
        'finished_at'=>date('Y-m-d H:i:s'),
        'status'=>$exitCode===0 && !$erro?'success':'error',
        'exit_code'=>$exitCode,
        'error_message'=>$erro,
        'plan_json_path'=>isset($ctx['last_plan_file'])?$ctx['last_plan_file']:null,
        'log_file_path'=>$GLOBALS['LOG_FILE']??null,
        'session_log_path'=>$ctx['session_log']??null,
        'updated_at'=>date('Y-m-d H:i:s'),
    ]);
}
// Select genérico (PDO pode não estar disponível; fallback silencioso)
function db_exec_select_rows(string $sql): array {
    if(!function_exists('banco_query')) return []; // sem driver legacy
    $res = banco_query($sql);
    $rows=[]; if($res){ while($r = banco_fetch_assoc($res)){ $rows[]=$r; } }
    return $rows;
}
function persist_existe_em_execucao(): bool {
    $rows = db_exec_select_rows("SELECT id_atualizacoes_execucoes FROM atualizacoes_execucoes WHERE status='running' ORDER BY started_at DESC LIMIT 1");
    return !empty($rows);
}
/** CLI: fecha a linha da execução com erro (antes ficava `running` quando a atualização falhava). */
function atualizacoes_cli_falha(?int $execId, int $codigo, string $msg): void {
    global $CONTEXT;
    if($execId) persist_final_execucao($execId, $CONTEXT ?? [], $codigo, $msg);
}
function logErroCtx(string $msg): void { logAtualizacao($msg, 'ERROR', true); }

// ----------------------------
// CLI Parsing & Ajuda
// ----------------------------
function parseArgsUpdate(array $argv): array {
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
    echo "  --local-artifact        Usa artefato local em ../conn2flow-docker-test-environment/dados/sites/localhost/conn2flow-github/ (gestor.zip + gestor.zip.sha256)\n";
    echo "  --domain=DOMINIO        Ambiente (pasta autenticacoes/<dominio>)\n";
    echo "  --only-files            Apenas atualização de arquivos + merge .env\n";
    echo "  --only-db               Apenas banco de dados\n";
    echo "  --no-db                 Pula banco (igual full sem DB)\n";
    echo "  --download-only         Baixa, extrai e gera plano sem aplicar\n";
    echo "  --skip-download         Usa ZIP já existente em staging\n";
    echo "  --backup                Cria backup completo da instalação antes do deploy (exceto contents/, logs/, backups/, temp/, autenticacoes/)\n";
    echo "  (req-197) Toda atualização, menos --dry-run, roda com a trava de deploy do ambiente (temp/deploy.lock)\n";
    echo "  --rollback=<exec-id>    (req-198) Volta os arquivos de uma execução pelo snapshot; com --com-banco, também o banco\n";
    echo "  --no-health             (req-198) Pula a verificação pós-atualização (erro fatal novo / HTTP da raiz)\n";
    echo "  --no-rollback           (req-198) Verifica, mas não volta os arquivos sozinho quando a verificação falha\n";
    echo "  --health-url=URL        (req-198) URL da verificação HTTP (padrão https://<domínio>/; .env ATUALIZACOES_SAUDE_URL)\n";
    echo "  --health-ip=IP          (req-198) Resolve o host da verificação para este IP (.env ATUALIZACOES_SAUDE_IP)\n";
    echo "  --log-stdout            (req-201) Repete cada linha do log na saída padrão (execução disparada pela API)\n";
    echo "  --dry-run               Simula (gera plano, não aplica)\n";
    echo "  --no-verify             Desativa verificação SHA256 do arquivo gestor.zip\n";
    echo "  --force-all             Encaminha ao script de banco\n";
    echo "  --tables=lista          Limita tabelas (ex: paginas,variaveis)\n";
    echo "  --log-diff              Log detalhado de diffs banco\n";
    echo "  --debug                 Verbosidade maior (DEBUG logs)\n";
    echo "  --clean-temp            Remove staging ao final mesmo em dry-run\n";
    echo "  --wipe                  Ativa wipe completo antes do deploy (por padrão é overwrite — preserva arquivos customizados)\n";
    echo "  --logs-retention-days=N  Mantém somente N dias de logs de atualização e planos (default 14, 0 desativa)\n";
    echo "  --help                  Exibe esta ajuda\n";
    echo "\nFluxo Simplificado:\n";
    echo "  1. Bootstrap: baixa/usa artefato, extrai, atualiza este script e reexecuta nova versão.\n";
    echo "  2. Deploy: overwrite por padrão (preserva arquivos customizados). Use --wipe para forçar wipe completo (remove tudo exceto pastas protegidas) antes do deploy.\n";
    echo "  3. Merge .env (aditivo) e atualização de banco (se não --no-db / não --only-files).\n";
    echo "\nPastas protegidas (não removidas): logs/, backups/, temp/, contents/, autenticacoes/\n";
}

// ----------------------------
// Util: Reconstrói linha de comando preservando argumentos do usuário
// ----------------------------
function reconstruirArgs(array $original): array {
    // Remove índice 0 (script) e retorna somente flags originais
    $out=[]; foreach($original as $i=>$a){ if($i===0) continue; $out[]=$a; } return $out;
}

function detectarDominioAtualizacaoPadrao(): string {
    global $_GESTOR;

    $serverName = trim((string)($_SERVER['SERVER_NAME'] ?? ''));
    if ($serverName !== '') return $serverName;

    $authPathServer = trim((string)($_GESTOR['AUTH_PATH_SERVER'] ?? ''), "\\/");
    if ($authPathServer !== '') return basename($authPathServer);

    return 'localhost';
}

function validarOpts(array &$opts): void {
    if (!empty($opts['only-files']) && !empty($opts['only-db'])) {
        throw new InvalidArgumentException('Flags conflitantes: --only-files e --only-db');
    }
    if (empty($opts['domain'])) {
        $opts['domain'] = detectarDominioAtualizacaoPadrao();
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

// Descobre raiz real do gestor dentro do staging (caso zip tenha pasta encapsuladora)
function localizarRaizGestor(string $stagingDir): string {
    logAtualizacao('Localizando raiz do gestor em: '.$stagingDir,'DEBUG');
    $expectedDirs = ['controladores','bibliotecas','db','vendor'];
    $ok = true;
    foreach ($expectedDirs as $d) { if (is_dir($stagingDir.$d)) continue; $ok=false; break; }
    if ($ok) return $stagingDir; // já é raiz
    // Se há um único subdiretório, tenta nele
    $entries = array_values(array_filter(scandir($stagingDir), fn($e)=>$e!=='.' && $e!=='..'));
    if (count($entries)===1 && is_dir($stagingDir.$entries[0].DIRECTORY_SEPARATOR)) {
        $sub = $stagingDir.$entries[0].DIRECTORY_SEPARATOR;
        $ok2=true; foreach ($expectedDirs as $d){ if (!is_dir($sub.$d)) { $ok2=false; break; } }
        if ($ok2) return $sub;
    }
    return $stagingDir; // fallback
}

// Valida se artefato contém arquivos críticos antes de prosseguir
function validarArtefato(string $root, array $criticalFiles): void {
    foreach ($criticalFiles as $cf) {
        if (!file_exists($root.$cf)) {
            throw new ExtractionException('Artefato inválido: arquivo crítico ausente no ZIP: '.$cf);
        }
    }
}

// Localiza template .env dentro do artefato considerando possíveis nomes de diretório
function localizarEnvTemplate(string $stagingRoot, string $domain, bool $debug=false): ?string {
    // Ordem de tentativa:
    // 1. autenticacoes.exemplo/<domain>/.env
    // 2. autenticacoes.exemplo/localhost/.env
    // 3. autenticacoes.exemplo/dominio/.env (legado/documentação antiga)
    $candidatos = [
        'autenticacoes.exemplo'.DIRECTORY_SEPARATOR.$domain.DIRECTORY_SEPARATOR.'.env',
        'autenticacoes.exemplo'.DIRECTORY_SEPARATOR.'localhost'.DIRECTORY_SEPARATOR.'.env',
        'autenticacoes.exemplo'.DIRECTORY_SEPARATOR.'dominio'.DIRECTORY_SEPARATOR.'.env',
    ];
    foreach ($candidatos as $rel) {
        $full = $stagingRoot.$rel;
        if (file_exists($full)) {
            if ($debug) logAtualizacao('Template .env encontrado: '.$full,'DEBUG');
            return $full;
        } else {
            if ($debug) logAtualizacao('Template .env candidato ausente: '.$full,'DEBUG');
        }
    }
    return null;
}

// ----------------------------
// Deploy Simplificado (Wipe + Extract)
// ----------------------------
function removerConteudoBase(string $basePath, array $protegidos): int {
    $removidos = 0;
    $dh = opendir($basePath);
    if(!$dh) return 0;
    while(($entry = readdir($dh)) !== false){
        if($entry==='.'||$entry==='..') continue;
        if(in_array($entry,$protegidos,true)) continue; // preserva
        $full = $basePath.$entry;
        if(is_dir($full)) { removeDirectoryRecursive($full); $removidos++; }
        else { @unlink($full); $removidos++; }
    }
    closedir($dh);
    return $removidos;
}
function moverConteudoStaging(string $stagingRoot, string $basePath, array $protegidos): int {
    $movidos=0;
    $dh = opendir($stagingRoot); if(!$dh) return 0;
    while(($entry=readdir($dh))!==false){
        if($entry==='.'||$entry==='..') continue;
        $src = $stagingRoot.$entry;
        // Se destino é protegido e já existe, não sobrescrever
        if(in_array($entry,$protegidos,true) && file_exists($basePath.$entry)) continue;
        $dst = $basePath.$entry;
        // Se ambos são diretórios, merge recursivo (preserva conteúdo customizado no destino)
        if(is_dir($src) && is_dir($dst)) {
            copiarRecursivo($src, $dst);
            $movidos++;
            logAtualizacao('moverConteudoStaging: merge dir '.$entry.' (preservando conteúdo existente)','DEBUG');
            continue;
        }
        // Se destino existe e não é protegido, tratar conflitos de tipo com segurança
        if(file_exists($dst) && !in_array($entry,$protegidos,true)) {
            // Proteção importante: se o artefato TEM um arquivo mas o destino é um DIRETÓRIO,
            // não removemos o diretório (evita apagar módulos/customizações por conflito de empacotamento).
            if (is_file($src) && is_dir($dst)) {
                logAtualizacao('moverConteudoStaging: conflito tipo (src=file dst=dir) — preservando destino '.$dst,'WARNING');
                // pulamos a substituição para preservar conteúdo existente no destino
                continue;
            }
            $removeOk=true;
            if(is_dir($dst)) { removeDirectoryRecursive($dst); $removeOk=!is_dir($dst); }
            else { @unlink($dst); $removeOk=!file_exists($dst); }
            if(!$removeOk) logAtualizacao('moverConteudoStaging: falha ao remover destino existente '.$dst,'WARNING');
        }
        // Tenta mover (rename) para rapidez; se falhar, copia recursivo
        if(@rename($src,$dst)) { $movidos++; logAtualizacao('moverConteudoStaging: rename OK '.$src.' -> '.$dst,'DEBUG'); continue; }
        else { if(file_exists($src)) logAtualizacao('moverConteudoStaging: rename falhou para '.$src.' -> '.$dst.' tentando copy','DEBUG'); }
        // fallback copy
        if(is_dir($src)) copiarRecursivo($src,$dst); else { if(!@copy($src,$dst)) logAtualizacao('moverConteudoStaging: copy falhou '.$src.' -> '.$dst,'WARNING'); }
        $movidos++;
    }
    closedir($dh);
    return $movidos;
}
/**
 * req-194: remove do ambiente as migrações do core que o artefato novo não traz mais (ou a cópia antiga
 * de uma migração renomeada). Arquivos do projeto ficam; conflito com eles vira choque no log.
 * O helper vem da instalação ou, na primeira atualização que o traz, do próprio staging.
 */
function limparMigracoesCore(string $realRoot, string $basePath): void {
    $helper = __DIR__.DIRECTORY_SEPARATOR.'atualizacoes-migracoes.php';
    if(!is_file($helper)) $helper = $realRoot.'controladores'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'atualizacoes-migracoes.php';
    if(!is_file($helper)) { logAtualizacao('Limpeza de migrações: helper ausente, etapa pulada','WARNING'); return; }
    require_once $helper;
    $chegando = atualizacoes_migracoes_listar($realRoot.'db'.DIRECTORY_SEPARATOR.'migrations');
    if(!$chegando) return;
    $r = atualizacoes_migracoes_limpar($basePath.'db'.DIRECTORY_SEPARATOR.'migrations', 'core', $chegando, $chegando, true);
    foreach(atualizacoes_migracoes_log($r, 'core') as $linha) logAtualizacao($linha, $r['choques'] ? 'WARNING' : 'INFO');
}
/**
 * req-197: biblioteca da trava de deploy — da instalação ou, na primeira atualização que a traz, do
 * staging (o filho do bootstrap é o script novo rodando sobre a instalação antiga). Sem ela, a
 * atualização segue sem trava, com aviso.
 */
function atualizacoes_trava_carregar(?string $stagingRoot = null): bool {
    global $BASE_PATH;
    if(function_exists('deploy_lock_acquire')) return true;
    $candidatos = [$BASE_PATH.'bibliotecas'.DIRECTORY_SEPARATOR.'deploy-lock.php'];
    if($stagingRoot) $candidatos[] = rtrim($stagingRoot, '/\\').DIRECTORY_SEPARATOR.'bibliotecas'.DIRECTORY_SEPARATOR.'deploy-lock.php';
    foreach($candidatos as $f){ if(is_file($f)){ require_once $f; return true; } }
    return false;
}
/** Arquivo da trava de deploy do ambiente (em `temp/`, pasta protegida no deploy). */
function atualizacoes_trava_arquivo(): string { global $BASE_PATH; return $BASE_PATH.'temp'.DIRECTORY_SEPARATOR.'deploy.lock'; }
/**
 * req-197: backup completo da instalação antes do deploy (`--backup`). Copia tudo, menos as pastas
 * protegidas que casam com `$excludes` (regex sobre o caminho relativo com `/`) e a própria
 * `backups/`. Devolve [arquivos, bytes]. Erro de cópia derruba a atualização antes de mexer em algo.
 */
function backupTotal(string $basePath, string $backupDir, array $excludes): array {
    $basePath = rtrim($basePath, '/\\').DIRECTORY_SEPARATOR;
    $excludes[] = '#^backups/#';
    $excludes[] = '#^installation/choques/#';
    if(!is_dir($backupDir) && !@mkdir($backupDir, 0775, true) && !is_dir($backupDir)) throw new ExtractionException('Backup: não foi possível criar '.$backupDir);
    $arquivos = 0; $bytes = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach($it as $item){
        $rel = str_replace('\\', '/', substr($item->getPathname(), strlen($basePath)));
        $relDir = $item->isDir() ? $rel.'/' : $rel;
        foreach($excludes as $rx){ if(preg_match($rx, $relDir)) continue 2; }
        $destino = $backupDir.str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if($item->isDir()){ if(!is_dir($destino)) @mkdir($destino, 0775, true); continue; }
        if(!is_dir(dirname($destino))) @mkdir(dirname($destino), 0775, true);
        if(!@copy($item->getPathname(), $destino)) throw new ExtractionException('Backup: falha ao copiar '.$rel);
        $arquivos++; $bytes += (int)$item->getSize();
    }
    logAtualizacao('Backup total concluído: '.$arquivos.' arquivo(s), '.round($bytes / 1048576, 1).' MB em '.$backupDir);
    return [$arquivos, $bytes];
}
/**
 * req-198: biblioteca do manifesto por camada — da instalação ou, na primeira atualização que a traz,
 * do staging.
 */
function atualizacoes_manifesto_carregar(?string $stagingRoot = null): bool {
    global $BASE_PATH, $CONTEXT;
    if(function_exists('instalacao_planejar')) return true;
    // A do pacote novo primeiro: é a versão que casa com este atualizador (a instalada pode ser anterior
    // e não ter as funções que ele chama). Sem staging informado, usa o do contexto ou o `--staging-root`
    // que o bootstrap passa ao filho: o primeiro a carregar fixa a versão para o processo inteiro.
    if(!$stagingRoot) $stagingRoot = $CONTEXT['staging_root'] ?? null;
    if(!$stagingRoot) foreach((array)($GLOBALS['argv'] ?? []) as $a){
        if(strpos((string)$a, '--staging-root=') === 0) $stagingRoot = trim(substr((string)$a, 15), "\"' ");
    }
    $candidatos = [$BASE_PATH.'bibliotecas'.DIRECTORY_SEPARATOR.'instalacao-manifesto.php'];
    if($stagingRoot) array_unshift($candidatos, rtrim($stagingRoot, '/\\').DIRECTORY_SEPARATOR.'bibliotecas'.DIRECTORY_SEPARATOR.'instalacao-manifesto.php');
    foreach($candidatos as $f){ if(is_file($f)){ require_once $f; return true; } }
    return false;
}
/**
 * req-198: aplica a precedência de camadas antes de mover o staging. Arquivo sobreposto por projeto ou
 * plugin, ou editado no servidor, sai do staging (a versão nova fica em `backups/overrides/<versão>/`) e
 * vira choque; o que o core deixou de entregar e está intacto sai do disco; o manifesto do core é gravado.
 * Os choques ficam pendentes em `installation/choques/` e vão para a tabela depois da etapa de banco.
 */
function aplicarManifestoCore(string $realRoot, string $basePath): ?array {
    global $CONTEXT;
    if(!atualizacoes_manifesto_carregar($realRoot)){ logAtualizacao('Manifesto por camada: biblioteca ausente, etapa pulada','WARNING'); return null; }
    $versao = (string)($CONTEXT['release_tag'] ?? '');
    if($versao === '') $versao = date('Ymd-His');
    $pacote = instalacao_mapa($realRoot, ['gestor.zip', 'gestor-local.zip', 'gestor.zip.sha256']);
    $plano = instalacao_planejar($basePath, 'core', $pacote, true);
    // req-198 / BATCH-203: snapshot seletivo (só o que muda) antes de mexer em qualquer arquivo.
    if(function_exists('instalacao_snapshot_criar')){
        $idSnap = !empty($CONTEXT['exec_id']) ? 'exec-'.$CONTEXT['exec_id'] : 'exec-'.date('Ymd-His');
        $raizSnaps = $basePath.'backups'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'snapshots'.DIRECTORY_SEPARATOR;
        $CONTEXT['snapshot_dir'] = $raizSnaps.$idSnap.DIRECTORY_SEPARATOR;
        $c = instalacao_snapshot_criar($basePath, $plano, $CONTEXT['snapshot_dir'], ['versao' => $versao, 'exec_id' => $CONTEXT['exec_id'] ?? null]);
        logAtualizacao(sprintf('Snapshot %s: %d sobrescrito(s), %d removido(s), %d novo(s)', $idSnap, $c['sobrescritos'], $c['removidos'], $c['novos']));
        $podados = instalacao_snapshot_podar($raizSnaps, 5);
        if($podados) logAtualizacao('Snapshots antigos removidos: '.$podados, 'DEBUG');
    }
    $r = instalacao_aplicar($basePath, $realRoot, 'core', $plano, $versao, 'preparar');
    logAtualizacao(sprintf('Manifesto do core (%s): %d a escrever, %d preservado(s), %d retirado(s)%s', $versao, $r['escritos'], $r['preservados'], $r['retirados'], $plano['primeira'] ? ' — primeira entrega, linha de base gravada' : ''));
    foreach($r['choques'] as $c) logAtualizacao('Choque ('.$c['motivo'].($c['camada_dona'] ? ', dono '.$c['camada_dona'] : '').'): '.$c['caminho'].($c['copia'] ? ' — versão nova em '.$c['copia'] : ''), 'WARNING');
    instalacao_choques_registrar($basePath, 'update-system', 'core', $versao, isset($CONTEXT['exec_id']) ? (string)$CONTEXT['exec_id'] : null, $r['choques']);
    $CONTEXT['installation'] = ['versao' => $versao, 'primeira' => $plano['primeira'], 'escritos' => $r['escritos'], 'preservados' => $r['preservados'], 'retirados' => $r['retirados'], 'choques' => count($r['choques'])];
    return $r;
}
/** req-198: grava na tabela os choques pendentes (depois da etapa de banco, quando a tabela já existe). */
function gravarChoquesPendentes(string $basePath): void {
    if(!function_exists('instalacao_choques_gravar_pendentes') || !function_exists('banco_query')) return;
    $existe = banco_query("SHOW TABLES LIKE 'atualizacoes_choques'");
    if(!$existe || (function_exists('banco_num_rows') && !banco_num_rows($existe))) return;
    $esc = fn($v) => function_exists('banco_escape_field') ? banco_escape_field((string)$v) : addslashes((string)$v);
    $repetidos = 0;
    $n = instalacao_choques_gravar_pendentes($basePath, function(array $linha) use ($esc, &$repetidos){
        $ja = banco_query('SELECT 1 FROM atualizacoes_choques WHERE '.instalacao_choque_pendente_filtro($linha, $esc).' LIMIT 1');
        if($ja && banco_num_rows($ja)){ $repetidos++; return true; }
        $cols = []; $vals = [];
        foreach($linha as $k => $v){ $cols[] = $k; $vals[] = $v === null ? 'NULL' : "'".$esc($v)."'"; }
        return (bool)banco_query('INSERT INTO atualizacoes_choques ('.implode(',', $cols).') VALUES ('.implode(',', $vals).')');
    });
    if($n) logAtualizacao('Choques gravados em atualizacoes_choques: '.($n - $repetidos).($repetidos ? ' (mais '.$repetidos.' já pendente(s))' : ''), 'WARNING');
}
/**
 * req-198 / BATCH-203: dump do banco antes da etapa de banco, no snapshot da execução. O trabalho é da
 * biblioteca (`instalacao_banco_dump`, compartilhada com o deploy por API).
 */
function backupBancoSnapshot(): ?string {
    global $CONTEXT, $_BANCO;
    $dir = $CONTEXT['snapshot_dir'] ?? null;
    if(!$dir || empty($_BANCO['nome']) || !atualizacoes_manifesto_carregar()) return null;
    $d = instalacao_banco_dump($dir, (array)$_BANCO);
    if(!$d['ok']){ logAtualizacao('Dump do banco falhou ('.$d['erro'].')','WARNING'); return null; }
    logAtualizacao('Dump do banco no snapshot: '.$d['mb'].' MB');
    return $d['arquivo'];
}
/** Tamanho atual do log de erros do PHP (para achar fatais novos depois). */
function saudeLogOffset(string $basePath): int {
    // Sem a biblioteca: roda no começo, antes de o staging estar no contexto.
    $f = rtrim($basePath, '/\\').DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'php-error.log';
    return is_file($f) ? (int)filesize($f) : 0;
}
/** req-198 / BATCH-203: verificação depois da atualização (ver `instalacao_saude_verificar`). */
function saudeVerificar(string $basePath, int $offsetLog, string $dominio, array $opcoes = []): array {
    if(!atualizacoes_manifesto_carregar()) return ['ok' => true, 'motivos' => [], 'http' => null, 'avisos' => ['Verificação indisponível: biblioteca instalacao-manifesto ausente']];
    return instalacao_saude_verificar($basePath, $offsetLog, $dominio, $opcoes);
}
/**
 * req-198 / BATCH-203: rollback de uma execução pelo snapshot. Arquivos sempre; banco só com
 * `$comBanco` (decisão do operador: pode haver escrita de usuários depois da atualização).
 * Aceita `exec-<id>`, só o número, ou o id de um snapshot do deploy por API (`api-…`).
 */
function rollbackExecucao(string $basePath, string $id, bool $comBanco): int {
    global $_BANCO;
    $dir = atualizacoes_manifesto_carregar() ? instalacao_snapshot_dir($basePath, $id) : null;
    if(!$dir || !is_file($dir.'snapshot.json')){ echo "ERRO ROLLBACK: snapshot não encontrado: ".($dir ?? $id)."\n"; return EXIT_GENERIC; }
    $id = basename(rtrim($dir, '/\\'));
    if($comBanco && empty($_BANCO['nome'])){ echo "ERRO ROLLBACK: --com-banco precisa de --domain=<domínio> (configuração do banco)\n"; return EXIT_GENERIC; }
    $r = instalacao_snapshot_restaurar($basePath, $dir);
    if(isset($r['erro'])){ echo "ERRO ROLLBACK: {$r['erro']}\n"; return EXIT_GENERIC; }
    $msg = sprintf('Rollback %s: %d arquivo(s) restaurado(s), %d novo(s) removido(s)%s', $id, $r['restaurados'], $r['removidos_novos'], $r['falhas'] ? ', falhas: '.implode(', ', array_slice($r['falhas'], 0, 10)) : '');
    logAtualizacao($msg, $r['falhas'] ? 'WARNING' : 'INFO'); echo $msg."\n";
    if($comBanco){
        $b = instalacao_banco_restaurar($dir.'banco.sql.gz', (array)$_BANCO);
        $txt = $b['ok'] ? 'Banco restaurado do dump do snapshot.' : 'Falha ao restaurar o banco ('.$b['erro'].')';
        logAtualizacao($txt, $b['ok'] ? 'INFO' : 'ERROR'); echo $txt."\n";
        if(!$b['ok']) return EXIT_ROLLBACK;
        // O dump foi feito com a linha desta execução aberta (`running`): depois de restaurar, ela fica marcada.
        if(strpos($id, 'exec-') === 0 && ctype_digit(substr($id, 5))) db_exec_update((int)substr($id, 5), ['status' => 'rolled-back', 'finished_at' => date('Y-m-d H:i:s'),
            'error_message' => 'Revertida por --rollback='.$id.' --com-banco em '.date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
    } else {
        echo "Banco não foi tocado. Para voltar também o banco: --rollback={$id} --com-banco\n";
    }
    return EXIT_OK;
}
function copiarRecursivo(string $src, string $dst): void {
    if(is_dir($src)) { if(!is_dir($dst)) @mkdir($dst,0775,true); $it=opendir($src); if($it){ while(($e=readdir($it))!==false){ if($e==='.'||$e==='..') continue; copiarRecursivo($src.DIRECTORY_SEPARATOR.$e,$dst.DIRECTORY_SEPARATOR.$e); } closedir($it);} }
    else { @copy($src,$dst); }
}

// (Funções antigas de diff removidas - simplificação wipe+deploy)

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

    logAtualizacao('Merge .env: alvo='.$envAtualPath.' template='.$envTemplatePath,'DEBUG');

    // Antes do merge, substituir LANGUAGE_DEFAULT=LANG por LANGUAGE_DEFAULT=pt-br no template
    $templateContent = file_get_contents($envTemplatePath);
    $templateContent = preg_replace('/^LANGUAGE_DEFAULT=LANG$/m', 'LANGUAGE_DEFAULT=pt-br', $templateContent);
    file_put_contents($envTemplatePath, $templateContent);
    logAtualizacao('Template .env atualizado: LANGUAGE_DEFAULT=LANG -> LANGUAGE_DEFAULT=pt-br','DEBUG');

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
    logAtualizacao('Merge .env: novas='.count($added).' deprecated='.count($deprecated).' keys='.implode(',', array_keys($added)));
}

// ----------------------------
// Atualização Banco
// ----------------------------
function executarAtualizacaoBanco(array $opts): void {
        global $BASE_PATH, $GLOBALS, $_BANCO;
        $script = $BASE_PATH . 'controladores' . DIRECTORY_SEPARATOR . 'atualizacoes' . DIRECTORY_SEPARATOR . 'atualizacoes-banco-de-dados.php';
        if (!file_exists($script)) throw new DatabaseUpdateException('Script de banco ausente: ' . $script);

        // Execução estritamente inline (via require), sem subprocessos externos (exec/passthru).
        // Migrações Phinx e sincronização rodam no mesmo processo PHP. Os logs do banco são
        // capturados por referência em $GLOBALS['EXTERNAL_LOGGER'] e encaminhados ao log do
        // sistema com o prefixo [BANCO].
        $cli = [
            'env-dir'    => $opts['env-dir'] ?? ($opts['domain'] ?? 'localhost'),
            'installing' => true,
            'db' => [
                'host' => $_BANCO['host'],
                'name' => $_BANCO['nome'],
                'user' => $_BANCO['usuario'],
                'pass' => $_BANCO['senha'] ?? '',
            ],
        ];
        foreach (['debug','force-all','tables','log-diff','dry-run','orphans-detail','orphans-mode','defer-session-reset'] as $k) { if (isset($opts[$k])) $cli[$k] = $opts[$k]; }
        $GLOBALS['CLI_OPTS'] = $cli;

        $dbLogs = [];
        $loggerAnterior = $GLOBALS['EXTERNAL_LOGGER'] ?? null;
        $GLOBALS['EXTERNAL_LOGGER'] = &$dbLogs;
        logAtualizacao('Banco: incluindo script inline (sem processo externo)');
        $erroInline = null;
        try {
            require $script;
            logAtualizacao('Banco: concluído inline');
        } catch (Throwable $e) {
            $erroInline = $e;
        }
        // Restaura o logger externo anterior e encaminha o capturado prefixado com [BANCO].
        unset($GLOBALS['EXTERNAL_LOGGER']);
        if ($loggerAnterior !== null) $GLOBALS['EXTERNAL_LOGGER'] = $loggerAnterior;
        foreach ($dbLogs as $line) logAtualizacao('[BANCO] ' . $line, 'INFO');
        if ($erroInline !== null) {
            throw new DatabaseUpdateException('Falha atualização banco inline: ' . $erroInline->getMessage());
        }
}

// ----------------------------
// Plano JSON / Relatório
// ----------------------------
function exportarPlanoJson(array $plan, array $context): string {
    global $LOGS_DIR, $CONTEXT;
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
    // Registrar no contexto global para futura persistência (stats fallback)
    $CONTEXT['last_plan_file'] = $file;
    logAtualizacao('Plano exportado: '.$file);
    return $file;
}

function renderRelatorioFinal(array $context): string {
    $dur = microtime(true)-$context['start_time'];
    $p=$context['plan']; $stats=$p['stats']??['removed'=>0,'copied'=>0];
    return 'Atualização concluída em '.number_format($dur,2).'s | copied='.( $stats['copied']??0 ).' removed='.( $stats['removed']??0 ).' envAdded='.count($context['env_merge']['added'])."\n";
}

// ----------------------------
// Limpeza (staging)
// ----------------------------
function removeDirectoryRecursive(string $dir): void {
    if(!is_dir($dir)) return; $it=new RecursiveDirectoryIterator($dir,RecursiveDirectoryIterator::SKIP_DOTS); $ri=new RecursiveIteratorIterator($it,RecursiveIteratorIterator::CHILD_FIRST);
    foreach($ri as $f){ if($f->isDir()) @rmdir($f->getPathname()); else @unlink($f->getPathname()); }
    @rmdir($dir);
}

// Remove diretórios temporários antigos de atualizações (housekeeping)
function pruneOldUpdateTempDirs(string $baseTempAtualizacoes, string $currentDir, int $maxAgeHours = 24): void {
    if(!is_dir($baseTempAtualizacoes)) return;
    $now = time();
    $entries = scandir($baseTempAtualizacoes) ?: [];
    foreach($entries as $e){
        if($e==='.'||$e==='..') continue;
        $full = $baseTempAtualizacoes.$e.DIRECTORY_SEPARATOR;
        if(!is_dir($full)) continue;
        // Não apagar diretório em uso
        if($currentDir && strpos($currentDir,$full)===0) continue;
        $mtime = @filemtime($full) ?: $now;
        if(($now - $mtime) > $maxAgeHours*3600){
            logAtualizacao('Housekeeping: removendo temp antigo de atualização '.$full,'DEBUG');
            removeDirectoryRecursive($full);
        }
    }
}

// Remove logs e planos JSON antigos (atualizacoes-sistema-*.log, atualizacoes-banco-*.log, plan-*.json)
function pruneOldUpdateLogs(string $logsDir, int $retentionDays, bool $debug=false): void {
    if ($retentionDays <= 0) { if($debug) logAtualizacao('Retenção de logs desativada (--logs-retention-days=0)','DEBUG'); return; }
    if (!is_dir($logsDir)) return;
    $now = time();
    $cutoff = $now - ($retentionDays * 86400);
    $patterns = [
        '/^atualizacoes-sistema-\\d{8}\\.log$/',
        '/^atualizacoes-banco-\\d{8}\\.log$/',
        '/^atualizacoes-bd-\\d{8}\\.log$/',
        '/^plan-\\d{8}-\\d{6}\\.json$/'
    ];
    $dh = opendir($logsDir); if(!$dh) return;
    $removed=0; $kept=0;
    while(($f=readdir($dh))!==false){
        if($f==='.'||$f==='..') continue;
        $full = $logsDir.$f;
        if(!is_file($full)) continue;
        $match=false; foreach($patterns as $re){ if(preg_match($re,$f)){ $match=true; break; } }
        if(!$match) { $kept++; continue; }
        $mtime = @filemtime($full) ?: $now;
        if($mtime < $cutoff){
            if(@unlink($full)) { $removed++; if($debug) logAtualizacao('Housekeeping: removendo log antigo '.$f,'DEBUG'); }
        } else { $kept++; }
    }
    closedir($dh);
    if($debug) logAtualizacao('Retenção logs: removidos='.$removed.' mantidos='.$kept.' dias='.$retentionDays,'DEBUG');
}

// ----------------------------
// Hooks (Fase 1 placeholders)
// ----------------------------
function hookBeforeFiles(array &$context): void {}
function hookAfterDb(array &$context): void {}
function hookAfterAll(array &$context): void {
    // Após deploy completo: sincronizar tabela hooks com JSONs de módulos e projeto
    require_once dirname(__FILE__) . '/atualizacoes-hooks.php';
    atualizacoes_hooks_sincronizar();
}

// ----------------------------
// MAIN
// ----------------------------
/**
 * req-197: a atualização pelo CLI roda com a trava de deploy do ambiente. O processo do bootstrap pega a
 * trava e passa o token ao filho (`--lock-token`), que a adota; quem pegou é quem libera (no fim, no
 * erro ou na exceção). Simulação (`--dry-run`) e ajuda não travam.
 */
function main_update(array $argv): int {
    $opts = parseArgsUpdate($argv);
    if(isset($opts['help']) || !empty($opts['dry-run'])) return main_update_executar($argv);
    $stagingRoot = isset($opts['staging-root']) ? trim((string)$opts['staging-root'], "\"' ") : null;
    if(!atualizacoes_trava_carregar($stagingRoot)){
        logAtualizacao('Trava de deploy indisponível (biblioteca ausente): seguindo sem trava','WARNING');
        return main_update_executar($argv);
    }
    $arquivo = atualizacoes_trava_arquivo();
    $token = isset($opts['lock-token']) ? trim((string)$opts['lock-token'], "\"' ") : '';
    $atual = deploy_lock_read($arquivo);
    if($token !== '' && $atual && hash_equals((string)($atual['token'] ?? ''), $token)){
        $GLOBALS['C2F_DEPLOY_LOCK_TOKEN'] = $token; // adotada do processo pai do bootstrap
        return main_update_executar($argv);
    }
    $r = deploy_lock_acquire($arquivo, ['owner' => 'update-system', 'detail' => 'CLI']);
    if(!$r['ok']){
        $msg = 'Outro deploy está em execução neste ambiente: '.deploy_lock_describe($r['holder'] ?? null);
        logAtualizacao($msg, 'ERROR', true);
        echo "ERRO TRAVA: ".$msg."\n";
        return EXIT_LOCKED;
    }
    if(!empty($r['stale'])) logAtualizacao('Trava vencida assumida: '.deploy_lock_describe($r['stale']), 'WARNING');
    $GLOBALS['C2F_DEPLOY_LOCK_TOKEN'] = $r['token'];
    try { return main_update_executar($argv); }
    finally { deploy_lock_release($arquivo, $r['token']); }
}

/**
 * req-198: no CLI, o atualizador roda sem o Gestor carregado — `$_BANCO` ficava vazio e a etapa de banco
 * parava em "Configurações de banco não definidas" (o registro em `atualizacoes_execucoes` também não
 * acontecia). Carrega o `config.php` da instalação com o domínio de `--domain`, que escolhe o `.env` em
 * `autenticacoes/<domínio>/`. No fluxo web o Gestor já trouxe tudo e nada é feito.
 */
function atualizacoes_cli_carregar_config(array $opts): void {
    global $_BANCO, $_GESTOR, $_CONFIG, $_INDEX, $BASE_PATH;
    if(PHP_SAPI !== 'cli' || !empty($_BANCO['nome']) || defined('ATUALIZACOES_SISTEMA_SEM_EXECUCAO')) return;
    $dominio = (string)($opts['domain'] ?? ($opts['env-dir'] ?? ''));
    if($dominio === '' || $dominio === '1' || !is_file($BASE_PATH.'config.php')) return;
    $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? $dominio;
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? $dominio;
    $_INDEX = is_array($_INDEX ?? null) ? $_INDEX : [];
    $_INDEX['sistemas-dir'] = $_INDEX['sistemas-dir'] ?? $BASE_PATH;
    try {
        require_once $BASE_PATH.'config.php';
        if(is_file($BASE_PATH.'bibliotecas'.DIRECTORY_SEPARATOR.'banco.php')) require_once $BASE_PATH.'bibliotecas'.DIRECTORY_SEPARATOR.'banco.php';
        if(function_exists('banco_conectar') && empty($_BANCO['conexao'])) banco_conectar();
        logAtualizacao('CLI: configuração carregada de '.$dominio.' (banco '.($_BANCO['nome'] ?? '?').')', 'DEBUG');
    } catch(Throwable $e){
        logAtualizacao('CLI: não foi possível carregar a configuração da instalação: '.$e->getMessage(), 'WARNING');
    }
}

function main_update_executar(array $argv): int {
    global $CONTEXT, $BASE_PATH, $LOGS_DIR;
    try {
        $opts=parseArgsUpdate($argv); if(isset($opts['help'])) { help(); return EXIT_OK; }
        atualizacoes_cli_carregar_config($opts);
        // req-198 / BATCH-203: rollback manual de uma execução (arquivos; banco com --com-banco).
        if(!empty($opts['rollback']) && $opts['rollback'] !== true) return rollbackExecucao($BASE_PATH, (string)$opts['rollback'], !empty($opts['com-banco']));
        $CONTEXT['saude_offset'] = saudeLogOffset($BASE_PATH);
        validarOpts($opts); $CONTEXT['opts']=$opts; $CONTEXT['debug']=!empty($opts['debug']);
        $dry = !empty($opts['dry-run']);
        $onlyFiles = !empty($opts['only-files']);
        $onlyDb = !empty($opts['only-db']);
        $noDb = !empty($opts['no-db']);
    $CONTEXT['mode']=$onlyFiles?'only-files':($onlyDb?'only-db':($noDb?'files-without-db':'full'));
    // Retenção de logs (default 14 dias se não especificado)
    $retentionDays = isset($opts['logs-retention-days']) ? (int)$opts['logs-retention-days'] : 14;
    $CONTEXT['opts']['logs-retention-days']=$retentionDays;
    // Persistência: início execução. O processo do bootstrap só prepara e reexecuta a versão nova; a
    // linha em atualizacoes_execucoes é do processo que faz o trabalho (uma linha por atualização, e é
    // o id dela que nomeia o snapshot).
    $execId = (empty($opts['bootstrap-done']) && !$onlyDb) ? null : persist_inicio_execucao($CONTEXT);
    if($execId) $CONTEXT['exec_id']=$execId;
        logAtualizacao('Iniciando atualização modo='.$CONTEXT['mode'].' dryRun='.($dry?'1':'0').' bootstrap='.(!empty($opts['bootstrap-done'])?'1':'0')); 

        // ----------------------------------------------
        // FASE 1 (BOOTSTRAP): garantir que executamos a versão NOVA do script
        // Se não houver flag interna --bootstrap-done, fazemos somente: download/extract/local-artifact, copiar novo script e reexecutar
        // ----------------------------------------------
        if (empty($opts['bootstrap-done']) && !$onlyDb) {
            logAtualizacao('Bootstrap: iniciando (baixar/extrair/copiar script e reexecutar)...');
            $staging = prepararStaging();
            $CONTEXT['staging_dir']=$staging;
            // Download ou artefato local mínimo (sem deploy ainda)
            if(!empty($opts['local-artifact'])) {
                $repoRoot = realpath($BASE_PATH.'..').DIRECTORY_SEPARATOR;
                $localDir = $repoRoot.'conn2flow-github'.DIRECTORY_SEPARATOR;
                // Tentativas adicionais (ex: estrutura ../conn2flow-docker-test-environment/dados/sites/localhost/conn2flow-github)
                if(!is_file($localDir.'gestor.zip')){
                    $altPath = [$BASE_PATH,'..','..','conn2flow-docker-test-environment','dados','sites','localhost','conn2flow-github'];
                    $alt = realpath(join(DIRECTORY_SEPARATOR,$altPath));
                    if($alt && is_file($alt.DIRECTORY_SEPARATOR.'gestor.zip')){
                        $localDir = rtrim($alt,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                        logAtualizacao('Artefato local: usando caminho alternativo '.$localDir,'DEBUG');
                    }
                }
                $zip = $localDir.'gestor.zip';
                $sha = $localDir.'gestor.zip.sha256';
                if(!file_exists($zip)) throw new DownloadException('Bootstrap: artefato local não encontrado: '.$zip);
                $CONTEXT['release_tag']=$opts['tag'] ?? 'local-artifact';
                $CONTEXT['zip_path']=$zip;
                if(empty($opts['no-verify']) && file_exists($sha)) {
                    $CONTEXT['checksum']=verifyZipSha256($zip,$sha);
                }
                $zipTmp=$staging.'gestor-local.zip'; @copy($zip,$zipTmp); extrairZipGestor($zipTmp,$staging);
            } else {
                if(!empty($opts['tag'])) { $CONTEXT['release_tag']=$opts['tag']; }
                else { $info=descobrirUltimaTagGestor(); $CONTEXT['release_tag']=$info['tag']; }
                $tag=$CONTEXT['release_tag'];
                $zip = downloadRelease($tag,$staging);
                $CONTEXT['zip_path']=$zip;
                if(empty($opts['no-verify'])){ $shaFile=downloadZipChecksum($tag,$staging); $CONTEXT['checksum']=verifyZipSha256($zip,$shaFile); }
                extrairZipGestor($zip,$staging);
            }
            $realRoot = localizarRaizGestor($staging);
            $novoScript = $realRoot.'controladores'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'atualizacoes-sistema.php';
            if(!file_exists($novoScript)) throw new ExtractionException('Bootstrap: script atualizado ausente no artefato');
            $destScript = __FILE__;
            // Copia somente se diferente (hash) para reduzir IO
            $hashOrig = @hash_file('sha256',$destScript) ?: '';
            $hashNovo = @hash_file('sha256',$novoScript) ?: '';
            if($hashOrig!==$hashNovo){
                if(!@copy($novoScript,$destScript)) throw new ExtractionException('Bootstrap: falha ao copiar novo script para destino');
                logAtualizacao('Bootstrap: script atualizado copiado (hash antigo='.$hashOrig.' novo='.$hashNovo.')');
            } else {
                logAtualizacao('Bootstrap: script já estava atualizado (hash='.$hashOrig.')');
            }
            // Reexecuta nova versão com flags internas para pular bootstrap e reutilizar staging
            $userArgs = reconstruirArgs($argv);
            // Garante que não exista antiga flag bootstrap
            $userArgs = array_values(array_filter($userArgs, fn($a)=>strpos($a,'--bootstrap-done')!==0 && strpos($a,'--lock-token')!==0));
            // req-201: os argumentos de quem chamou vão escapados para a linha do filho (antes iam crus: um valor
            // com `;` ou `&`, como uma URL vinda da API, viraria comando). Os internos abaixo já são escapados.
            $userArgs = array_map('escapeshellarg', $userArgs);
            $userArgs[]='--bootstrap-done=1';
            // req-197: o filho adota a trava deste processo (quem libera é este processo, no fim).
            if(!empty($GLOBALS['C2F_DEPLOY_LOCK_TOKEN'])) $userArgs[]='--lock-token='.$GLOBALS['C2F_DEPLOY_LOCK_TOKEN'];
            $userArgs[]='--skip-download'; // pular download na fase 2
            $userArgs[]='--staging-dir='.escapeshellarg($staging);
            $userArgs[]='--staging-root='.escapeshellarg($realRoot);
            if($CONTEXT['release_tag']) $userArgs[]='--tag='.escapeshellarg($CONTEXT['release_tag']);
            // Propaga no-verify se presente
            if(!empty($opts['no-verify'])) $userArgs[]='--no-verify';
            // req-201: o mesmo PHP deste processo (o `php` do PATH pode ser outra versão, como no disparo pela API).
            $phpCli = (PHP_SAPI === 'cli' && PHP_BINARY !== '') ? escapeshellarg(PHP_BINARY) : 'php';
            $cmd = $phpCli.' '.escapeshellarg($destScript).' '.implode(' ',$userArgs);
            logAtualizacao('Bootstrap: reexecutando nova versão -> '.$cmd);
            passthru($cmd,$exit); // repassa saída direto
            logAtualizacao('Bootstrap: processo filho retornou exit='.$exit);
            return $exit; // encerra bootstrap
        }

        // Se estamos em fase 2 e recebemos staging pré-existente, configurar contexto antes de continuar
        if (!empty($opts['bootstrap-done']) && !empty($opts['staging-dir'])) {
            $stagingFromFlag = rtrim(str_replace(['"','\"'],'',$opts['staging-dir']),"\/").DIRECTORY_SEPARATOR; // limpeza simples
            if(is_dir($stagingFromFlag)) { $CONTEXT['staging_dir']=$stagingFromFlag; }
            if(!empty($opts['staging-root'])) {
                $rootFlag = rtrim(str_replace(['"','\"'],'',$opts['staging-root']),"\/").DIRECTORY_SEPARATOR;
                if(is_dir($rootFlag)) $CONTEXT['staging_root']=$rootFlag;
            }
        }
        
        logAtualizacao('Fase principal: prosseguindo com deploy (bootstrap-done='.(empty($opts['bootstrap-done'])?'0':'1').')');
        hookBeforeFiles($CONTEXT);

        // ---------------- Arquivos (modo simplificado overwrite) ----------------
        if(!$onlyDb){
            // Se bootstrap já trouxe staging, reutiliza; senão cria
            if(!empty($CONTEXT['staging_dir'])) { $staging=$CONTEXT['staging_dir']; }
            else { $staging=prepararStaging(); $CONTEXT['staging_dir']=$staging; }
            if(!empty($opts['local-artifact'])) {
                // Caminho padrão dentro do container para artefatos locais
                $repoRoot = realpath($BASE_PATH.'..').DIRECTORY_SEPARATOR;
                $localDir = $repoRoot.'conn2flow-github'.DIRECTORY_SEPARATOR;
                $zip = $localDir.'gestor.zip';
                $sha = $localDir.'gestor.zip.sha256';
                if(!file_exists($zip)) throw new DownloadException('Artefato local não encontrado: '.$zip);
                $CONTEXT['release_tag']=$opts['tag'] ?? 'local-artifact';
                $CONTEXT['zip_path']=$zip;
                if(empty($opts['no-verify']) && file_exists($sha)) {
                    $CONTEXT['checksum']=verifyZipSha256($zip,$sha);
                } elseif(!empty($opts['no-verify'])) {
                    logAtualizacao('Checksum ignorado (--no-verify) artefato local','WARNING');
                } else {
                    // Gera checksum local on-the-fly
                    try {
                        $hash=computeFileSha256($zip);
                        $shaGen=$localDir.'gestor.zip.sha256';
                        @file_put_contents($shaGen,$hash.PHP_EOL);
                        $CONTEXT['checksum']=['expected'=>$hash,'got'=>$hash,'file'=>basename($zip),'generated'=>true];
                        logAtualizacao('Checksum local gerado on-the-fly: '.$hash,'INFO');
                    } catch (Throwable $e) {
                        logAtualizacao('Falha gerar checksum local: '.$e->getMessage(),'WARNING');
                    }
                }
                $zipTmp = $staging.'gestor-local.zip'; @copy($zip,$zipTmp); extrairZipGestor($zipTmp,$staging);
            } else {
                if(!empty($opts['tag'])) { $CONTEXT['release_tag']=$opts['tag']; }
                else { $info=descobrirUltimaTagGestor(); $CONTEXT['release_tag']=$info['tag']; }
                $tag=$CONTEXT['release_tag'];
                $zip = !empty($opts['skip-download']) ? ($staging.'gestor.zip') : downloadRelease($tag,$staging);
                if(!file_exists($zip)) throw new DownloadException('ZIP esperado não encontrado (skip-download?)');
                $CONTEXT['zip_path']=$zip;
                if(empty($opts['no-verify'])){
                    $shaFile = downloadZipChecksum($tag,$staging);
                    $CONTEXT['checksum']=verifyZipSha256($zip,$shaFile);
                } else { logAtualizacao('Verificação checksum desativada (--no-verify)','WARNING'); }
                extrairZipGestor($zip,$staging);
            }
            // Ajusta raiz real se necessário (se já definida via bootstrap, reutiliza)
            $realRoot = $CONTEXT['staging_root'] ?? localizarRaizGestor($staging);
            if ($realRoot !== $staging) { logAtualizacao('Raiz real detectada dentro do ZIP: '.$realRoot,'DEBUG'); }
            $CONTEXT['staging_root'] = $realRoot;
            // Validar arquivos críticos antes de qualquer remoção
            $critical = [
                'controladores'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'atualizacoes-banco-de-dados.php',
                'controladores'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'atualizacoes-sistema.php',
                'phinx.php'
            ];
            validarArtefato($realRoot, $critical);
            logAtualizacao('Artefato validado: arquivos críticos presentes');
            // Remove artefatos ZIP do staging para não irem para produção
            foreach(['gestor.zip','gestor-local.zip'] as $zf){
                $zp = $realRoot.$zf;
                if(is_file($zp)) { @unlink($zp); logAtualizacao('Removido artefato temporário do staging: '.$zf,'DEBUG'); }
            }
            // Backup total se solicitado
            // Pastas protegidas (não removidas / não sobrescritas)
            // IMPORTANTE: 'autenticacoes/' contém configurações específicas por domínio (.env, chaves, etc.)
            // Mantemos 'autenticacoes.exemplo/' fora da lista para que novos templates continuem sendo distribuídos.
            $excludes=['#^contents/#','#^logs/#','#^backups/#','#^temp/#','#^autenticacoes/#','#^installation/#'];
            logAtualizacao('Pastas protegidas (excludes overwrite): contents/, logs/, backups/, temp/, autenticacoes/','DEBUG');
            if(!empty($opts['backup']) && !$dry){
                $backupDir = $BASE_PATH.'backups'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'full'.DIRECTORY_SEPARATOR.date('Ymd-His').DIRECTORY_SEPARATOR;
                [$bkArquivos, $bkBytes] = backupTotal($BASE_PATH,$backupDir,$excludes);
                $CONTEXT['backups'][]=['full_backup'=>$backupDir,'files'=>$bkArquivos,'bytes'=>$bkBytes];
            }
            // Antes do deploy capturamos (se existir) o template .env dentro do staging para evitar perdê-lo
            // pois o moverConteudoStaging usa rename sempre que possível e esvazia o staging.
            $envTemplateRel = null; $envTemplateOriginalPath = null;
            // Importante: template padrão fica em autenticacoes.exemplo/dominio/.env
            // Usamos literal 'dominio' para garantir detecção mesmo que --domain seja outro (ex: localhost)
            $envTemplateInitial = localizarEnvTemplate($realRoot, 'dominio', !empty($opts['debug']));
            if ($envTemplateInitial) {
                $envTemplateOriginalPath = $envTemplateInitial; // caminho completo pré-move
                // Deriva caminho relativo para reconstruir após mover
                $envTemplateRel = ltrim(substr($envTemplateInitial, strlen($realRoot)), DIRECTORY_SEPARATOR);
                if(!empty($opts['debug'])) logAtualizacao('Pré-deploy: template .env identificado (rel='.$envTemplateRel.')','DEBUG');
            } else {
                if(!empty($opts['debug'])) logAtualizacao('Pré-deploy: nenhum template .env localizado ainda','DEBUG');
            }

            // Merge .env (adiciona novas variáveis)
            $envAtual = $BASE_PATH.'autenticacoes'.DIRECTORY_SEPARATOR.($opts['domain']??'localhost').DIRECTORY_SEPARATOR.'.env';
            $envTpl = null;
            // O merge precisa comparar com o template recém-extraído do staging.
            // Se usarmos primeiro o caminho reconstruído na base, acabamos lendo o template antigo
            // ainda instalado e novas chaves só entram na atualização seguinte.
            if ($envTemplateRel) {
                if ($envTemplateOriginalPath && file_exists($envTemplateOriginalPath)) {
                    $envTpl = $envTemplateOriginalPath;
                    if(!empty($opts['debug'])) logAtualizacao('Template .env no staging (prioritário para merge): '.$envTpl,'DEBUG');
                } else {
                    $reconstructed = $BASE_PATH.$envTemplateRel; // fallback após mover
                    if (file_exists($reconstructed)) {
                        $envTpl = $reconstructed;
                        if(!empty($opts['debug'])) logAtualizacao('Template .env pós-deploy (fallback reconstructed): '.$envTpl,'DEBUG');
                    }
                }
            }
            // Caso não tenhamos capturado antes ou não reconstruído, tenta busca agora no destino final
            if (!$envTpl) {
                // Primeiro tenta o placeholder 'dominio' (estrutura de exemplo)
                $envTpl = localizarEnvTemplate($BASE_PATH, 'dominio', !empty($opts['debug']));
            }
            if (!$envTpl) {
                // Depois tenta um template específico do domínio atual (se existir)
                $envTpl = localizarEnvTemplate($BASE_PATH, $opts['domain'] ?? 'localhost', !empty($opts['debug']));
            }
            if($envTpl===null) {
                logAtualizacao('Template .env não encontrado em nenhuma localização esperada','WARNING');
            }
            mergeEnv($envAtual,$envTpl??'',$CONTEXT,$dry);

            // Deploy simplificado: remover tudo exceto diretórios protegidos e mover novo conteúdo
            // req-198: installation/ guarda os manifestos por camada, as originais e os choques.
            $protegidos = ['contents','logs','backups','temp','autenticacoes','installation'];
            $wipeEnabled = !empty($opts['wipe']); // nova flag: default = false (overwrite)
            if(!$dry){
                if($wipeEnabled){
                    logAtualizacao('Remover conteúdos base (wipe habilitado)...','DEBUG');
                    $removidos = removerConteudoBase($BASE_PATH,$protegidos);
                    logAtualizacao('Wipe concluído (removidos não protegidos) count='.$removidos);
                } else {
                    logAtualizacao('Wipe pulado (modo padrão = overwrite). Use --wipe para ativar wipe completo','INFO');
                    $removidos = 0;
                }
                limparMigracoesCore($realRoot,$BASE_PATH);
                aplicarManifestoCore($realRoot,$BASE_PATH);
                $movidos = moverConteudoStaging($realRoot,$BASE_PATH,$protegidos);
                logAtualizacao('Deploy concluído (itens movidos) count='.$movidos);
                // Revalida críticos
                foreach ($critical as $cf){ if(!file_exists($BASE_PATH.$cf)) throw new ExtractionException('Após deploy arquivo crítico ausente: '.$cf); }
                $stats = ['removed'=>$removidos,'copied'=>$movidos];
            } else {
                $stats = ['removed'=>0,'copied'=>0];
                logAtualizacao('Dry-run: skip wipe/deploy');
            }
            
            $CONTEXT['plan']=['stats'=>$stats];
            if(!empty($execId)) persist_parcial_execucao($execId,$CONTEXT);
            exportarPlanoJson($CONTEXT['plan'],$CONTEXT);
        }

        // ---------------- Banco ----------------
        if(!$onlyFiles && !$noDb){
            if(!$dry) backupBancoSnapshot();
            executarAtualizacaoBanco($opts);
            if(!$dry) gravarChoquesPendentes($BASE_PATH);
            if(!empty($execId)) persist_parcial_execucao($execId,$CONTEXT);
            hookAfterDb($CONTEXT);
            // req-197: `db/` fica (como no fluxo web). `db/migrations` tem dois donos (core e projeto) e o
            // manifesto da req-194 (`db/.c2f-migrations-<dono>.json`) mora aqui: apagar a pasta levava as
            // migrações do projeto e fazia toda atualização contar como a primeira. As migrações
            // obsoletas já saem pela limpeza por dono (req-194).
        }

        hookAfterAll($CONTEXT);
        // req-198 / BATCH-203: verificação depois da atualização; falhou, os arquivos voltam sozinhos.
        if(!$dry && empty($opts['no-health']) && !empty($CONTEXT['snapshot_dir'])){
            $saudeOpc = [
                'url' => (string)($opts['health-url'] ?? ($_ENV['ATUALIZACOES_SAUDE_URL'] ?? '')),
                'ip' => (string)($opts['health-ip'] ?? ($_ENV['ATUALIZACOES_SAUDE_IP'] ?? '')),
            ];
            $saude = saudeVerificar($BASE_PATH, (int)($CONTEXT['saude_offset'] ?? 0), (string)($opts['domain'] ?? ''), array_filter($saudeOpc));
            $CONTEXT['saude'] = $saude;
            foreach($saude['avisos'] as $aviso) logAtualizacao($aviso, 'WARNING', true);
            if($saude['ok']) logAtualizacao('Verificação pós-atualização OK'.($saude['http'] ? ' (HTTP '.$saude['http'].')' : ''));
            else {
                logAtualizacao('Verificação pós-atualização FALHOU: '.implode(' | ', $saude['motivos']), 'ERROR', true);
                if(empty($opts['no-rollback'])){
                    $r = instalacao_snapshot_restaurar($BASE_PATH, $CONTEXT['snapshot_dir']);
                    $msg = 'Rollback automático dos arquivos: '.($r['restaurados'] ?? 0).' restaurado(s), '.($r['removidos_novos'] ?? 0).' novo(s) removido(s). Banco não revertido; para reverter: --rollback='.basename(rtrim($CONTEXT['snapshot_dir'], '/\\')).' --com-banco';
                    logAtualizacao($msg, 'ERROR', true); echo "ROLLBACK: ".$msg."\n";
                    $depois = saudeVerificar($BASE_PATH, saudeLogOffset($BASE_PATH), (string)($opts['domain'] ?? ''), array_filter($saudeOpc));
                    $txt = 'Depois do rollback: '.($depois['ok'] ? 'OK' : implode(' | ', $depois['motivos'])).($depois['http'] ? ' (HTTP '.$depois['http'].')' : '');
                    logAtualizacao($txt, $depois['ok'] ? 'INFO' : 'ERROR', true); echo $txt."\n";
                    if(!empty($execId)) persist_final_execucao($execId,$CONTEXT,EXIT_ROLLBACK,'Verificação pós-atualização falhou: '.implode(' | ', $saude['motivos']));
                    return EXIT_ROLLBACK;
                }
            }
        }
        $rel=renderRelatorioFinal($CONTEXT); logAtualizacao(trim($rel)); echo $rel;
        // Limpeza de staging sempre (a menos que usuário peça para manter) + poda de antigos
        $keepTemp = !empty($opts['keep-temp']);
        if($CONTEXT['staging_dir'] && !$dry && !$keepTemp){
            logAtualizacao('Limpando diretório staging utilizado: '.$CONTEXT['staging_dir'],'DEBUG');
            removeDirectoryRecursive($CONTEXT['staging_dir']);
            // Housekeeping: remove diretórios mais antigos que 24h em temp/atualizacoes
            global $TEMP_DIR; // base gestor/temp/atualizacoes/
            $baseUpd = $TEMP_DIR; // já termina com /atualizacoes/
            pruneOldUpdateTempDirs($baseUpd, $CONTEXT['staging_dir']);
        } elseif($keepTemp) {
            logAtualizacao('Flag --keep-temp ativa: preservando staging '.$CONTEXT['staging_dir'],'WARNING');
        }
        // Após tudo, aplicar retenção de logs/planos
        try {
            pruneOldUpdateLogs($LOGS_DIR, $retentionDays, !empty($opts['debug']));
        } catch (Throwable $e){
            logAtualizacao('Falha prune logs: '.$e->getMessage(),'WARNING');
        }
    if(!empty($execId)) persist_final_execucao($execId,$CONTEXT,0,null);
    return EXIT_OK;
    } catch (DownloadException $e){ logErroCtx($e->getMessage()); atualizacoes_cli_falha($execId ?? null, EXIT_DOWNLOAD, $e->getMessage()); echo "ERRO DOWNLOAD: ".$e->getMessage()."\n"; return EXIT_DOWNLOAD; }
    catch (ExtractionException $e){ logErroCtx($e->getMessage()); atualizacoes_cli_falha($execId ?? null, EXIT_EXTRACTION, $e->getMessage()); echo "ERRO EXTRAÇÃO: ".$e->getMessage()."\n"; return EXIT_EXTRACTION; }
    catch (EnvMergeException $e){ logErroCtx($e->getMessage()); atualizacoes_cli_falha($execId ?? null, EXIT_ENV_MERGE, $e->getMessage()); echo "ERRO ENV: ".$e->getMessage()."\n"; return EXIT_ENV_MERGE; }
    catch (DatabaseUpdateException $e){ logErroCtx($e->getMessage()); atualizacoes_cli_falha($execId ?? null, EXIT_DB_ERROR, $e->getMessage()); echo "ERRO BANCO: ".$e->getMessage()."\n"; return EXIT_DB_ERROR; }
    catch (IntegrityException $e){ logErroCtx($e->getMessage()); atualizacoes_cli_falha($execId ?? null, EXIT_INTEGRITY, $e->getMessage()); echo "ERRO INTEGRIDADE: ".$e->getMessage()."\n"; return EXIT_INTEGRITY; }
    catch (InvalidArgumentException $e){ logErroCtx($e->getMessage()); atualizacoes_cli_falha($execId ?? null, EXIT_GENERIC, $e->getMessage()); echo "ARGUMENTO INVÁLIDO: ".$e->getMessage()."\n"; return EXIT_GENERIC; }
    catch (Throwable $t){ logErroCtx('Fatal: '.$t->getMessage()); atualizacoes_cli_falha($execId ?? null, EXIT_GENERIC, 'Fatal: '.$t->getMessage()); echo "ERRO FATAL: ".$t->getMessage()."\n"; return EXIT_GENERIC; }
}

// Testes carregam as funções sem executar a atualização (req-197).
if(PHP_SAPI==='cli' && !defined('ATUALIZACOES_SISTEMA_SEM_EXECUCAO')) exit(main_update($argv));

// -------------------------------------------------------------
// Execução Web Incremental (AJAX) - Fase 1.1
// -------------------------------------------------------------
// Objetivo: permitir que o módulo admin-atualizacoes orquestre a atualização
// por etapas, retornando JSON a cada chamada. Mantemos compatibilidade CLI.
// Etapas (steps) planejadas:
//  1. bootstrap      (download/extract + possível re-exec; aqui fazemos download + prepara staging + coleta metadata mas NÃO reexecuta via CLI)
//  2. deploy_files   (wipe + mover arquivos + merge .env + export plano)
//  3. database       (executar atualizacoes-banco-de-dados.php se aplicável)
//  4. finalize       (limpeza staging + retention logs + relatório final)
// Notas:
//  - Em execução web evitamos reexecução do próprio script (bootstrap simplificado inline).
//  - session_id: identificador da sequência, mapeia para arquivo de estado JSON em temp/atualizacoes/sessions/<id>.json
//  - Logs incrementais: agregados em temp/atualizacoes/sessions/<id>.log além do log diário padrão.

// Estado persistido da sessão web
function webSessionPath(string $sid): string { global $TEMP_DIR; return $TEMP_DIR.'sessions'.DIRECTORY_SEPARATOR.$sid.'.json'; }
function webSessionLogPath(string $sid): string { global $TEMP_DIR; return $TEMP_DIR.'sessions'.DIRECTORY_SEPARATOR.$sid.'.log'; }

function loadWebState(string $sid): array {
    $file = webSessionPath($sid);
    if(!is_file($file)) return [];
    $json = @file_get_contents($file); if(!$json) return [];
    $data = json_decode($json,true); return is_array($data)?$data:[];
}
function saveWebState(string $sid, array $state): void {
    $file = webSessionPath($sid); $dir=dirname($file); if(!is_dir($dir)) @mkdir($dir,0775,true);
    // Pequena limpeza de campos volumosos
    file_put_contents($file,json_encode($state,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}

function gerarSessionId(): string { return bin2hex(random_bytes(8)); }

// Inicia sessão (step bootstrap simplificado)
/**
 * req-197: a atualização web por etapas roda com a trava de deploy. `start` pega a trava e guarda o
 * token na sessão; `finalize` e `cancel` liberam; `start` que falha libera na hora. Simulação não trava.
 */
function webStart(array $req): array {
    if(!empty($req['dry_run']) || !atualizacoes_trava_carregar()) return webStartExecutar($req);
    $arquivo = atualizacoes_trava_arquivo();
    $r = deploy_lock_acquire($arquivo, ['owner' => 'update-system', 'detail' => 'web']);
    if(!$r['ok']) return ['error' => 'Outro deploy está em execução neste ambiente: '.deploy_lock_describe($r['holder'] ?? null), 'locked' => true];
    if(!empty($r['stale'])) logAtualizacao('WebStart: trava vencida assumida: '.deploy_lock_describe($r['stale']), 'WARNING');
    try { $res = webStartExecutar($req); }
    catch(Throwable $e){ deploy_lock_release($arquivo, $r['token']); throw $e; }
    if(!empty($res['error']) || empty($res['sid'])){ deploy_lock_release($arquivo, $r['token']); return $res; }
    $st = loadWebState($res['sid']);
    if($st){ $st['lock_token'] = $r['token']; saveWebState($res['sid'], $st); }
    return $res;
}
/** Libera a trava guardada na sessão web (fim ou cancelamento). */
function webLiberarTrava(string $sid): void {
    $st = loadWebState($sid);
    if(!$st || empty($st['lock_token']) || !atualizacoes_trava_carregar()) return;
    deploy_lock_release(atualizacoes_trava_arquivo(), (string)$st['lock_token']);
}
function webStartExecutar(array $req): array {
    global $CONTEXT, $TEMP_DIR, $BASE_PATH, $LOGS_DIR;
    $sid = gerarSessionId();
    $CONTEXT['session_id']=$sid;
    $CONTEXT['session_log']=webSessionLogPath($sid);
    logAtualizacao('WebStart: iniciando sessão');
    $opts = [
        'domain'=>$req['domain']??detectarDominioAtualizacaoPadrao(),
        'tag'=>$req['tag']??null,
        'local-artifact'=>!empty($req['local'])?1:null,
        'no-verify'=>!empty($req['no_verify'])?1:null,
        'dry-run'=>!empty($req['dry_run'])?1:null,
        'only-files'=>!empty($req['only_files'])?1:null,
        'only-db'=>!empty($req['only_db'])?1:null,
        'no-db'=>!empty($req['no_db'])?1:null,
        'debug'=>!empty($req['debug'])?1:null,
        // Extras avançados
        'log-diff'=>!empty($req['log_diff'])?1:null,
        'force-all'=>!empty($req['force_all'])?1:null,
        'backup'=>!empty($req['backup'])?1:null,
        'wipe'=>!empty($req['wipe'])?1:null,
        'download-only'=>!empty($req['download_only'])?1:null,
        'skip-download'=>!empty($req['skip_download'])?1:null,
        'clean-temp'=>!empty($req['clean_temp'])?1:null,
        'csrf-capable'=>!empty($req['csrf_capable'])?1:null,
        'tables'=>!empty($req['tables'])?$req['tables']:null,
        'logs-retention-days'=>!empty($req['logs_retention_days'])?(int)$req['logs_retention_days']:null,
        // req-201: verificação e volta automática também no caminho por etapas.
        'no-health'=>!empty($req['no_health'])?1:null,
        'no-rollback'=>!empty($req['no_rollback'])?1:null,
        'health-url'=>(!empty($req['health_url']) && preg_match('#^https?://[A-Za-z0-9.:/_%\[\]-]+$#', (string)$req['health_url'])) ? (string)$req['health_url'] : null,
        'health-ip'=>(!empty($req['health_ip']) && filter_var($req['health_ip'], FILTER_VALIDATE_IP)) ? (string)$req['health_ip'] : null,
    ];
    // Limpa nulls
    $opts = array_filter($opts,fn($v)=>$v!==null && $v!==false);
    $CONTEXT['opts']=$opts; $CONTEXT['debug']=!empty($opts['debug']);
    $dry = !empty($opts['dry-run']) || !empty($opts['download-only']);
    // Preparar staging e download/extract, mas não aplicar deploy ainda
    $staging = prepararStaging();
    $CONTEXT['staging_dir']=$staging;
    if(!empty($opts['local-artifact'])) {
        // Busca robusta por diretório conn2flow-github com gestor.zip subindo a árvore
        $tentativas=[]; $encontradoDir=null; $zip=null; $sha=null;
        $startDir = realpath(__DIR__); // controladores/atualizacoes
        $maxUp = 8; $cur = $startDir;
        while($maxUp-- > 0 && $cur && $cur !== DIRECTORY_SEPARATOR){
            // candidatos diretos neste nível
            $cand1 = rtrim($cur,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'conn2flow-github'.DIRECTORY_SEPARATOR;
            $tentativas[]=$cand1;
            if(is_file($cand1.'gestor.zip')){ $encontradoDir=$cand1; break; }
            // se diretório termina com public_html ou instalador, sobe mais mas também tenta um nível acima (ex: .../localhost/ )
            $cur = dirname($cur);
        }
        // Se ainda não achou, tenta irmão de $BASE_PATH se existir
        if(!$encontradoDir){
            $baseSibling = dirname(rtrim($BASE_PATH,DIRECTORY_SEPARATOR)).DIRECTORY_SEPARATOR.'conn2flow-github'.DIRECTORY_SEPARATOR;
            if(!in_array($baseSibling,$tentativas,true)) $tentativas[]=$baseSibling;
            if(is_file($baseSibling.'gestor.zip')) $encontradoDir=$baseSibling;
        }
        if(!$encontradoDir){
            logErroCtx('WebStart: artefato local ausente. Tentativas: '.implode(' | ',$tentativas));
            return ['error'=>'Artefato local não encontrado (tentativas: '.implode(', ',$tentativas).')'];
        }
        $zip=$encontradoDir.'gestor.zip'; $sha=$encontradoDir.'gestor.zip.sha256';
        $CONTEXT['release_tag']=$opts['tag'] ?? 'local-artifact';
        $CONTEXT['zip_path']=$zip;
        if(empty($opts['no-verify']) && $sha && file_exists($sha)) {
            $CONTEXT['checksum']=verifyZipSha256($zip,$sha);
        } elseif(!empty($opts['no-verify'])) {
            logAtualizacao('WebStart: checksum ignorado (--no-verify)','WARNING');
        }
        $zipTmp=$staging.'gestor-local.zip'; @copy($zip,$zipTmp); extrairZipGestor($zipTmp,$staging);
    } else {
        if(!empty($opts['tag'])) { $CONTEXT['release_tag']=$opts['tag']; }
        else { $info=descobrirUltimaTagGestor(); $CONTEXT['release_tag']=$info['tag']; }
        $tag=$CONTEXT['release_tag'];
        $zip = downloadRelease($tag,$staging);
        if(!file_exists($zip)) { logErroCtx('WebStart: ZIP não encontrado após download'); return ['error'=>'ZIP não encontrado']; }
        $CONTEXT['zip_path']=$zip;
        if(empty($opts['no-verify'])){
            $shaFile = downloadZipChecksum($tag,$staging);
            $CONTEXT['checksum']=verifyZipSha256($zip,$shaFile);
        }
        extrairZipGestor($zip,$staging);
    }
    $realRoot = localizarRaizGestor($staging);
    $CONTEXT['staging_root']=$realRoot;
    $execId = persist_inicio_execucao($CONTEXT) ?? null;
    $state = [
        'sid'=>$sid,
        'exec_id'=>$execId,
        'step'=>'bootstrap',
        'created_at'=>date(DATE_ATOM),
        'opts'=>$opts,
        'release_tag'=>$CONTEXT['release_tag'],
        'checksum'=>$CONTEXT['checksum'],
    'staging_dir'=>$staging,
    'staging_root'=>$realRoot,
        'progress'=>['bootstrap'=>['done'=>true,'ts'=>time()]],
        'errors'=>[],
        'stats'=>[],
        'finished'=>false,
        'saude_offset'=>saudeLogOffset($BASE_PATH), // req-201: fatais novos contam a partir daqui
    ];
    saveWebState($sid,$state);
    return ['sid'=>$sid,'exec_id'=>$execId,'next'=>'deploy_files','release_tag'=>$CONTEXT['release_tag'],'checksum'=>$CONTEXT['checksum']];
}

function webDeployFiles(string $sid): array {
    global $CONTEXT, $BASE_PATH, $_GESTOR; $st=loadWebState($sid); if(!$st) return ['error'=>'Sessão inválida']; if($st['finished']) return ['error'=>'Sessão já finalizada'];
    // Recarregar flags da sessão (necessário para chamadas web separadas — preserva --debug)
    $CONTEXT['opts'] = $st['opts'] ?? [];
    $CONTEXT['debug'] = !empty($CONTEXT['opts']['debug']);

    if((empty($BASE_PATH) || !is_string($BASE_PATH)) && isset($_GESTOR['ROOT_PATH'])) {
        $BASE_PATH = rtrim($_GESTOR['ROOT_PATH'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        logAtualizacao('WebDeployFiles: BASE_PATH de $_GESTOR[ROOT_PATH]','DEBUG');
    } elseif(empty($BASE_PATH) || !is_string($BASE_PATH)) { // fallback defensivo
        $BASE_PATH = realpath(__DIR__.'/../../').DIRECTORY_SEPARATOR;
        logAtualizacao('WebDeployFiles: reconstruído BASE_PATH via realpath='.$BASE_PATH,'WARNING');
    }
    $CONTEXT['session_id']=$sid; $CONTEXT['session_log']=webSessionLogPath($sid);
    $opts=$st['opts']; $dry=!empty($opts['dry-run']) || !empty($opts['download-only']);
    logAtualizacao('WebDeployFiles: iniciando');
    // Restaurar staging a partir do estado persistido
    if(empty($CONTEXT['staging_dir']) && !empty($st['staging_dir'])) $CONTEXT['staging_dir']=$st['staging_dir'];
    if(empty($CONTEXT['staging_root']) && !empty($st['staging_root'])) $CONTEXT['staging_root']=$st['staging_root'];
    $staging = $CONTEXT['staging_dir'] ?? null;
    $realRoot = $CONTEXT['staging_root'] ?? null;
    if(!$staging || !$realRoot){
        logAtualizacao('WebDeployFiles: staging ausente no estado','ERROR');
        return ['error'=>'Staging ausente'];
    }
    $critical=[
        'controladores'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'atualizacoes-banco-de-dados.php',
        'controladores'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'atualizacoes-sistema.php',
        'phinx.php'
    ];
    validarArtefato($realRoot,$critical);
    foreach(['gestor.zip','gestor-local.zip'] as $zf){ $zp=$realRoot.$zf; if(is_file($zp)) @unlink($zp); }
    // Merge .env após preparar template reloc dentro staging -> replicar lógica simplificada
    $envTemplateInitial = localizarEnvTemplate($realRoot, 'dominio', !empty($opts['debug']));
    $envTemplateRel=null; $envTemplateOriginalPath=null; if($envTemplateInitial){ $envTemplateOriginalPath=$envTemplateInitial; $envTemplateRel=ltrim(substr($envTemplateInitial, strlen($realRoot)), DIRECTORY_SEPARATOR); }
    $envAtual=$BASE_PATH.'autenticacoes'.DIRECTORY_SEPARATOR.($opts['domain']??'localhost').DIRECTORY_SEPARATOR.'.env'; $envTpl=null;
    if($envTemplateRel){ if($envTemplateOriginalPath && file_exists($envTemplateOriginalPath)) $envTpl=$envTemplateOriginalPath; else { $reconstructed=$BASE_PATH.$envTemplateRel; if(file_exists($reconstructed)) $envTpl=$reconstructed; } }
    if(!$envTpl) $envTpl=localizarEnvTemplate($BASE_PATH,'dominio',!empty($opts['debug'])) ?? localizarEnvTemplate($BASE_PATH,$opts['domain']??'localhost',!empty($opts['debug']));
    if($envTpl) mergeEnv($envAtual,$envTpl,$CONTEXT,$dry); else logAtualizacao('WebDeployFiles: template .env não encontrado','WARNING');
    $protegidos=['contents','logs','backups','temp','autenticacoes','installation'];
    $wipeEnabled = !empty($opts['wipe']); // respeita flag web --wipe (default = overwrite)
    if(!$dry){
        if(empty($BASE_PATH) || !is_dir($BASE_PATH)) {
            logAtualizacao('WebDeployFiles: BASE_PATH inválido antes de removerConteudoBase','ERROR');
            return ['error'=>'BASE_PATH inválido'];
        }
        if($wipeEnabled){
            logAtualizacao('WebDeployFiles: wipe habilitado — removendo conteúdos base...','DEBUG');
            $removidos = removerConteudoBase($BASE_PATH,$protegidos);
            logAtualizacao('WebDeployFiles: wipe concluído count='.$removidos,'DEBUG');
        } else {
            logAtualizacao('WebDeployFiles: wipe pulado (modo padrão = overwrite). Use --wipe para ativar wipe completo','INFO');
            $removidos = 0;
        }
        limparMigracoesCore($realRoot,$BASE_PATH);
        if(!empty($st['exec_id'])) $CONTEXT['exec_id'] = $st['exec_id'];
        if(!empty($st['release_tag'])) $CONTEXT['release_tag'] = $st['release_tag'];
        aplicarManifestoCore($realRoot,$BASE_PATH);
        $movidos = moverConteudoStaging($realRoot,$BASE_PATH,$protegidos);
        logAtualizacao('WebDeployFiles: itens movidos count='.$movidos,'DEBUG');
        $stats = ['removed'=>$removidos,'copied'=>$movidos];
    }
    else { $stats=['removed'=>0,'copied'=>0]; }
    $CONTEXT['plan']=['stats'=>$stats];
    exportarPlanoJson($CONTEXT['plan'],$CONTEXT);
    $st['progress']['deploy_files']=['done'=>true,'stats'=>$stats,'ts'=>time()];
    $st['stats']=$stats; $st['step']='deploy_files_done';
    if(!empty($CONTEXT['snapshot_dir'])) $st['snapshot_dir'] = $CONTEXT['snapshot_dir']; // req-198 / BATCH-203
    saveWebState($sid,$st);
    if(!empty($st['exec_id'])) persist_parcial_execucao((int)$st['exec_id'],$CONTEXT);
    logAtualizacao('WebDeployFiles: concluído');
    // Se for download-only, não segue para database (termina em finalize)
    $next = (!empty($opts['only-files']) || !empty($opts['download-only'])) ? 'finalize' : 'database';
    return ['sid'=>$sid,'exec_id'=>$st['exec_id'],'next'=>$next,'stats'=>$stats];
}

function webDatabase(string $sid): array { global $CONTEXT, $BASE_PATH, $_GESTOR; $st=loadWebState($sid); if(!$st) return ['error'=>'Sessão inválida']; if($st['finished']) return ['error'=>'Sessão já finalizada'];
    // Recarregar flags da sessão (inclui debug)
    $CONTEXT['opts'] = $st['opts'] ?? [];
    $CONTEXT['debug'] = !empty($CONTEXT['opts']['debug']);

    $CONTEXT['session_id']=$sid; $CONTEXT['session_log']=webSessionLogPath($sid); $opts=$st['opts']; if(!empty($opts['only-files']) || !empty($opts['no-db'])) { return ['sid'=>$sid,'exec_id'=>$st['exec_id'],'skipped'=>true,'next'=>'finalize']; }
    if((empty($BASE_PATH) || !is_dir($BASE_PATH)) && isset($_GESTOR['ROOT_PATH'])) { $BASE_PATH = rtrim($_GESTOR['ROOT_PATH'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR; logAtualizacao('WebDatabase: BASE_PATH de $_GESTOR[ROOT_PATH]','DEBUG'); }
    if(empty($BASE_PATH) || !is_dir($BASE_PATH)) { $BASE_PATH = realpath(__DIR__.'/../../').DIRECTORY_SEPARATOR; logAtualizacao('WebDatabase: reconstruído BASE_PATH via realpath='.$BASE_PATH,'WARNING'); }
    // Verificar presença do script de banco
    $scriptBanco = $BASE_PATH.'controladores'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'atualizacoes-banco-de-dados.php';
    if(!is_file($scriptBanco)) {
        // Possível que staging_root ainda possua script; tentar lá
        if(!empty($CONTEXT['staging_root'])){
            $alt = rtrim($CONTEXT['staging_root'],DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'controladores'.DIRECTORY_SEPARATOR.'atualizacoes'.DIRECTORY_SEPARATOR.'atualizacoes-banco-de-dados.php';
            if(is_file($alt)) { $scriptBanco=$alt; logAtualizacao('WebDatabase: usando script banco do staging','WARNING'); }
        }
    }
    if(!is_file($scriptBanco)) {
        logAtualizacao('WebDatabase: script não encontrado em '.$scriptBanco,'ERROR');
        return ['sid'=>$sid,'exec_id'=>$st['exec_id'],'error'=>'Script não encontrado'];
    }
    logAtualizacao('WebDatabase: iniciando com script='.$scriptBanco);
    try {
        // A limpeza global de sessões precisa ocorrer somente após o último POST
        // do fluxo; caso contrário, o token CSRF validado acima desaparece antes
        // da chamada de finalize.
        $opts['defer-session-reset'] = true;
        if(!empty($st['snapshot_dir'])){ $CONTEXT['snapshot_dir'] = $st['snapshot_dir']; backupBancoSnapshot(); }
        executarAtualizacaoBanco($opts);
        gravarChoquesPendentes($BASE_PATH);
        $st['progress']['database']=['done'=>true,'ts'=>time()];
        $st['session_reset_pending']=true;
        saveWebState($sid,$st);
        if(!empty($st['exec_id'])) persist_parcial_execucao((int)$st['exec_id'],$CONTEXT);
        logAtualizacao('WebDatabase: concluído');
        return ['sid'=>$sid,'exec_id'=>$st['exec_id'],'next'=>'finalize'];
    }
    catch(Throwable $e){ $st['errors'][]=$e->getMessage(); saveWebState($sid,$st); if(!empty($st['exec_id'])) persist_final_execucao((int)$st['exec_id'],$CONTEXT,1,$e->getMessage()); logErroCtx('WebDatabase erro: '.$e->getMessage()); return ['sid'=>$sid,'exec_id'=>$st['exec_id'],'error'=>$e->getMessage(),'next'=>'finalize']; }
}

function webFinalize(string $sid): array { global $CONTEXT, $LOGS_DIR, $TEMP_DIR; $st=loadWebState($sid); if(!$st) return ['error'=>'Sessão inválida']; if($st['finished']) return ['sid'=>$sid,'already'=>true];
    // Recarregar flags da sessão (inclui debug)
    $CONTEXT['opts'] = $st['opts'] ?? [];
    $CONTEXT['debug'] = !empty($CONTEXT['opts']['debug']);

    $CONTEXT['session_id']=$sid; $CONTEXT['session_log']=webSessionLogPath($sid); logAtualizacao('WebFinalize: iniciando');
    try {
        // Limpeza staging (se ainda existir)
        if(!empty($CONTEXT['staging_dir']) && is_dir($CONTEXT['staging_dir'])) removeDirectoryRecursive($CONTEXT['staging_dir']);
    } catch(Throwable $e){ logAtualizacao('WebFinalize: falha limpeza staging '.$e->getMessage(),'WARNING'); }
    // req-201: verificação depois da atualização por etapas, como no CLI; falhou, os arquivos voltam do snapshot.
    global $BASE_PATH;
    $saude = null; $rollback = null; $codigo = 0; $erroFinal = null;
    $opts = $st['opts'] ?? [];
    if(empty($opts['dry-run']) && empty($opts['download-only']) && empty($opts['no-health']) && !empty($st['snapshot_dir'])){
        $saude = saudeVerificar($BASE_PATH, (int)($st['saude_offset'] ?? 0), (string)($opts['domain'] ?? ''), array_filter(['url' => (string)($opts['health-url'] ?? ''), 'ip' => (string)($opts['health-ip'] ?? '')]));
        foreach($saude['avisos'] as $aviso) logAtualizacao($aviso, 'WARNING', true);
        if($saude['ok']) logAtualizacao('Verificação pós-atualização OK'.($saude['http'] ? ' (HTTP '.$saude['http'].')' : ''), 'INFO', true);
        else {
            logAtualizacao('Verificação pós-atualização FALHOU: '.implode(' | ', $saude['motivos']), 'ERROR', true);
            if(empty($opts['no-rollback']) && atualizacoes_manifesto_carregar()){
                $r = instalacao_snapshot_restaurar($BASE_PATH, $st['snapshot_dir']);
                $rollback = ['restaurados' => $r['restaurados'] ?? 0, 'removidos_novos' => $r['removidos_novos'] ?? 0, 'falhas' => $r['falhas'] ?? [],
                    'snapshot' => basename(rtrim($st['snapshot_dir'], '/\\'))];
                logAtualizacao('Rollback automático dos arquivos: '.$rollback['restaurados'].' restaurado(s), '.$rollback['removidos_novos'].' novo(s) removido(s). Banco não revertido; para reverter: --rollback='.$rollback['snapshot'].' --com-banco', 'ERROR', true);
                $codigo = EXIT_ROLLBACK;
                $erroFinal = 'Verificação pós-atualização falhou: '.implode(' | ', $saude['motivos']);
            }
        }
    }
    $sessionResetPending = !empty($st['session_reset_pending']);
    $st['finished']=true;
    $st['session_reset_pending']=false;
    $st['progress']['finalize']=['done'=>true,'ts'=>time()];
    if($saude !== null) $st['saude'] = $saude;
    if($rollback !== null) $st['rollback'] = $rollback;
    saveWebState($sid,$st);
    if(!empty($st['exec_id'])) persist_final_execucao((int)$st['exec_id'],$CONTEXT,$codigo,$erroFinal);
    logAtualizacao('WebFinalize: concluído');

    // O POST de finalize já foi validado. Agora é seguro invalidar os caches de
    // sessão; a próxima página renderizada criará um token CSRF novo.
    if($sessionResetPending && function_exists('gestor_sessao_del_all')) {
        try {
            // Preserva somente o token já validado desta página. Isso mantém
            // compatibilidade com o documento que iniciou a autoatualização,
            // enquanto os demais caches de sessão são reinicializados.
            $csrfTokenAtual = '';
            if(function_exists('seguranca_csrf_token_requisicao') && function_exists('gestor_csrf_validar')) {
                $csrfTokenRecebido = seguranca_csrf_token_requisicao();
                if(gestor_csrf_validar($csrfTokenRecebido)) $csrfTokenAtual = $csrfTokenRecebido;
            }

            gestor_sessao_del_all();
            if($csrfTokenAtual !== '' && function_exists('gestor_sessao_variavel')) {
                gestor_sessao_variavel('csrf_token', $csrfTokenAtual);
            }
            logAtualizacao('WebFinalize: sessões do Gestor reinicializadas');
        } catch(Throwable $e) {
            logAtualizacao('WebFinalize: falha ao reinicializar sessões '.$e->getMessage(),'WARNING');
        }
    }

    return ['sid'=>$sid,'exec_id'=>$st['exec_id'],'finished'=>true,'saude'=>$saude,'rollback'=>$rollback]; }

function webStatus(string $sid): array { $st=loadWebState($sid); if(!$st) return ['error'=>'Sessão inválida'];
    // Recarregar flags da sessão (inclui debug)
    $GLOBALS['CONTEXT']['opts'] = $st['opts'] ?? [];
    $GLOBALS['CONTEXT']['debug'] = !empty($GLOBALS['CONTEXT']['opts']['debug']);

    $logPath=webSessionLogPath($sid); $tail=''; if(is_file($logPath)){ $tail=@file_get_contents($logPath); } $percent=calcularProgresso($st); return ['sid'=>$sid,'state'=>$st,'progress_percent'=>$percent,'log'=>$tail]; }

function jsonResponse(array $data): void { header('Content-Type: application/json; charset=utf-8'); echo json_encode($data,JSON_UNESCAPED_UNICODE); }

// Router básico quando chamado via web (não CLI)
if(PHP_SAPI!=='cli') {
    $action = $_REQUEST['action'] ?? null;
    if($action){
        $expected = getenv('ATUALIZACOES_TOKEN');
        if($expected && ($_REQUEST['token'] ?? '') !== $expected){ jsonResponse(['error'=>'Token inválido']); return; }
        try {
            switch($action){
                case 'start': jsonResponse(webStart($_REQUEST)); break;
                case 'deploy': jsonResponse(webDeployFiles($_REQUEST['sid']??'')); break;
                case 'db': jsonResponse(webDatabase($_REQUEST['sid']??'')); break;
                case 'finalize': $r = webFinalize($_REQUEST['sid']??''); webLiberarTrava($_REQUEST['sid']??''); jsonResponse($r); break;
                case 'status': jsonResponse(webStatus($_REQUEST['sid']??'')); break;
                case 'cancel': $r = webCancel($_REQUEST['sid']??''); webLiberarTrava($_REQUEST['sid']??''); jsonResponse($r); break;
                default: jsonResponse(['error'=>'Ação desconhecida']);
            }
        } catch(Throwable $e){ jsonResponse(['error'=>$e->getMessage()]); }
        return; // impedir execução automática main_update
    }
}

// Cancelar execução (marca finished e persiste status canceled)
function webCancel(string $sid): array { global $CONTEXT; $st=loadWebState($sid); if(!$st) return ['error'=>'Sessão inválida']; if($st['finished']) return ['sid'=>$sid,'already'=>true];
    // Recarregar flags da sessão (inclui debug)
    $CONTEXT['opts'] = $st['opts'] ?? [];
    $CONTEXT['debug'] = !empty($CONTEXT['opts']['debug']);

    $CONTEXT['session_id']=$sid; $CONTEXT['session_log']=webSessionLogPath($sid); logAtualizacao('WebCancel: solicitando cancelamento','WARNING'); $st['finished']=true; $st['canceled']=true; $st['progress']['canceled']=['done'=>true,'ts'=>time()]; saveWebState($sid,$st); if(!empty($st['exec_id'])) persist_final_execucao((int)$st['exec_id'],$CONTEXT,1,'cancelado'); $percent=calcularProgresso($st); return ['sid'=>$sid,'canceled'=>true,'progress_percent'=>$percent]; }

// Progresso percentual simples baseado em passos concluídos
function calcularProgresso(array $st): int {
    $p=10; // bootstrap baseline
    if(!empty($st['progress']['deploy_files']['done'])) $p=60;
    if(!empty($st['progress']['database']['done'])) $p=85;
    if(!empty($st['progress']['finalize']['done'])) $p=100;
    if(!empty($st['canceled']) && $p<100) { /* mantém valor */ }
    return $p; }

// Fallback comentário anterior:
// Execução via web: usar ?action=start -> depois deploy -> db -> finalize (ou status para polling)
