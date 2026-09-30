<?php

/**
 * Módulo: admin-atualizacoes
 * Objetivo: Interface administrativa para orquestrar e visualizar execuções
 *           do sistema de atualização automatizada (atualizacoes-sistema.php).
 *
 * Padrões (alinhado ao template `modulo_id`):
 * - Uso de páginas declaradas em admin-atualizacoes.json com placeholders.
 * - Manipulação de HTML via substituição em $_GESTOR['pagina'] (sem HTML inline fixo).
 * - Camada futura de persistência (tabela atualizacoes_execucoes) será integrada
 *   para registrar: id, inicio, fim, modo, exit_code, log, plano, status.
 * - Estrutura de funções seguindo convenções: <modulo>_listar, <modulo>_detalhe, <modulo>_disparar.
 * - Execução de atualização isolada (sem bloqueio) a evoluir com fila/async.
 */

global $_GESTOR;

// Garantir base-path definido para evitar warnings caso módulo seja carregado cedo.
if(empty($_GESTOR['base-path'])){
    // Assume diretório raiz do gestor como base (duas pastas acima deste arquivo).
    $_GESTOR['base-path'] = dirname(__DIR__,2).'/' ;
}

$_GESTOR['modulo-id'] = 'admin-atualizacoes';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__.'/admin-atualizacoes.json'), true);

// ================= Utilidades =================

function admin_atualizacoes_logs_dir(): string {
    global $_GESTOR; return $_GESTOR['base-path'].'logs/atualizacoes/';
}
function admin_atualizacoes_temp_sessions_dir(): string {
    global $_GESTOR; return $_GESTOR['base-path'].'temp/atualizacoes/sessions/';
}
// Agora omitimos o log diário (atualizacoes-sistema-YYYYMMDD.log) da UI principal;
// Exibiremos apenas logs de sessão recentes (salvos em temp/atualizacoes/sessions/<sid>.log).
function admin_atualizacoes_listar_logs_recente(int $limit = 20): array {
    $dir = admin_atualizacoes_temp_sessions_dir();
    if(!is_dir($dir)) return [];
    $files = glob($dir.'*.log');
    rsort($files, SORT_STRING);
    return array_slice($files,0,$limit);
}
function admin_atualizacoes_ultimo_plano(): ?string {
    $dir = admin_atualizacoes_logs_dir();
    if(!is_dir($dir)) return null;
    $plans = glob($dir.'plan-*.json');
    rsort($plans, SORT_STRING);
    return $plans[0] ?? null;
}

// ================= Render Helpers =================

function admin_atualizacoes_listar(): void {
    global $_GESTOR;

    // Logs de sessão (temp) – pode ser útil para acesso rápido
    $logs = admin_atualizacoes_listar_logs_recente();
    $linhas = '';
    foreach($logs as $f){
        $base = basename($f);
        $data = date('Y-m-d H:i:s', @filemtime($f));
        $linhas .= '<tr>'
            . '<td>'.htmlspecialchars($data).'</td>'
            . '<td>'.htmlspecialchars($base).'</td>'
            . '<td>'
            . '<a class="ui mini button" href="detalhe/?log='.urlencode($base).'">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-view-log-button']).'</a>'
            . '</td>'
            . '</tr>';
    }
    if($linhas==='') $linhas = '<tr><td colspan="3">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-no-records']).'</td></tr>';

    $ultimoPlano = admin_atualizacoes_ultimo_plano();
    $planoLink = $ultimoPlano ? '<a class="ui mini button" href="detalhe/?plano='.urlencode(basename($ultimoPlano)).'">Plano JSON</a>' : '';

    // Histórico (tabela)
    $historicoLinhas='';
    if(function_exists('banco_query')){
        // Consulta últimos 15
        $sql = "SELECT id_atualizacoes_execucoes,started_at,finished_at,release_tag,modo,status,stats_removed,stats_copied,session_log_path,plan_json_path FROM atualizacoes_execucoes ORDER BY started_at DESC LIMIT 15";
        $res = @banco_query($sql);
        if($res){
            while($row = banco_fetch_assoc($res)){
                $statusLabel = htmlspecialchars($row['status'] ?? '');
                $cls = 'grey';
                if($row['status']==='running') $cls='blue'; elseif($row['status']==='success') $cls='green'; elseif($row['status']==='error') $cls='red';
                $acoes=[];
                if(!empty($row['session_log_path']) && file_exists($row['session_log_path'])){
                    $baseLog = basename($row['session_log_path']);
                    $acoes[]='<a class="ui mini button" href="detalhe/?log='.urlencode($baseLog).'">Log Sessão</a>';
                }
                if(!empty($row['plan_json_path']) && file_exists($row['plan_json_path'])){
                    $basePlan = basename($row['plan_json_path']);
                    $acoes[]='<a class="ui mini button" href="detalhe/?plano='.urlencode($basePlan).'">Plano</a>';
                }
                $historicoLinhas.='<tr>'
                    .'<td>'.htmlspecialchars($row['started_at']??'').'</td>'
                    .'<td>'.htmlspecialchars($row['release_tag']??'').'</td>'
                    .'<td>'.htmlspecialchars($row['modo']??'').'</td>'
                    .'<td><span class="ui '.$cls.' label">'.$statusLabel.'</span></td>'
                    .'<td>'.htmlspecialchars($row['stats_removed']??'').'</td>'
                    .'<td>'.htmlspecialchars($row['stats_copied']??'').'</td>'
                    .'<td>'.htmlspecialchars($row['finished_at']??'').'</td>'
                    .'<td>'.implode(' ',$acoes).'</td>'
                .'</tr>';
            }
        }
    }
    if($historicoLinhas==='') $historicoLinhas='<tr><td colspan="8">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-no-records']).'</td></tr>';

    $comp = gestor_componente(['id' => 'atualizacoes-lista']);
    // Remover botão de acesso à página "disparar" (será descontinuada)
    $comp = preg_replace('#<a class="ui small button" href="disparar/">.*?</a>#','',$comp);
    $comp = modelo_var_troca_tudo($comp,'#plano-link#',$planoLink);
    $comp = modelo_var_troca_tudo($comp,'#linhas#',$linhas);
    $comp = modelo_var_troca_tudo($comp,'#historico_linhas#',$historicoLinhas);
    $comp .= admin_atualizacoes_choques_html(); // req-198
    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#dynamic-content#',$comp);
    // Incluir CodeMirror assets também na página de lista (antes só detalhe/disparar) para log vivo
	if(!function_exists('assets_externos_incluir') && !empty($_GESTOR['bibliotecas-path'])){
		require_once($_GESTOR['bibliotecas-path'].'assets-externos.php');
	}
	assets_externos_incluir('codemirror');

    // Incluir JS do módulo
    if(function_exists('gestor_pagina_javascript_incluir')) gestor_pagina_javascript_incluir();
}

/**
 * req-198: choques das entregas (tabela `atualizacoes_choques`) — sobreposições do projeto/plugin que a
 * atualização do core preservou, edições no servidor e retiradas que não puderam acontecer. A resolução
 * (sobrescrever, manter, mesclar) chega na req-199; aqui a lista e o diff.
 */
function admin_atualizacoes_choques_html(): string {
    global $_GESTOR;
    if(!function_exists('banco_query')) return '';
    $existe = @banco_query("SHOW TABLES LIKE 'atualizacoes_choques'");
    if(!$existe || !banco_num_rows($existe)) return '';
    $v = function($id){ global $_GESTOR; return htmlspecialchars((string)gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>$id]), ENT_QUOTES, 'UTF-8'); };
    $e = function($t){ return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); };
    $linhas = '';
    $res = @banco_query("SELECT id_atualizacoes_choques,data_criacao,origem,camada,versao,caminho,motivo,camada_dona,resolucao FROM atualizacoes_choques ORDER BY id_atualizacoes_choques DESC LIMIT 50");
    if($res) while($r = banco_fetch_assoc($res)){
        $cor = in_array($r['motivo'], ['sobreposto'], true) ? 'blue' : 'orange';
        $linhas .= '<tr>'
            .'<td>'.$e($r['data_criacao']).'</td>'
            .'<td>'.$e($r['camada']).'<br><small>'.$e($r['origem']).'</small></td>'
            .'<td><code>'.$e($r['caminho']).'</code></td>'
            .'<td><span class="ui '.$cor.' label">'.$v('updates-clash-'.$r['motivo']).'</span></td>'
            .'<td>'.$e($r['camada_dona'] ?? '—').'</td>'
            .'<td>'.$e($r['versao'] ?? '').'</td>'
            .'<td>'.($r['resolucao'] ? $e($r['resolucao']) : $v('updates-clash-pending')).'</td>'
            .'<td><a class="ui mini button" href="detalhe/?choque='.(int)$r['id_atualizacoes_choques'].'">'.$v('updates-clash-view').'</a></td>'
            .'</tr>';
    }
    if($linhas === '') $linhas = '<tr><td colspan="8">'.$v('updates-clash-empty').'</td></tr>';
    return '<div class="ui segment" data-atualizacoes-choques><h3 class="ui header">'.$v('updates-clash-title').'</h3>'
        .'<p>'.$v('updates-clash-help').'</p>'
        .'<table class="ui celled compact table"><thead><tr>'
        .'<th>'.$v('updates-clash-col-date').'</th><th>'.$v('updates-clash-col-layer').'</th><th>'.$v('updates-clash-col-path').'</th>'
        .'<th>'.$v('updates-clash-col-reason').'</th><th>'.$v('updates-clash-col-owner').'</th><th>'.$v('updates-clash-col-version').'</th>'
        .'<th>'.$v('updates-clash-col-resolution').'</th><th></th></tr></thead><tbody>'.$linhas.'</tbody></table></div>';
}

/** req-198: detalhe de um choque (diff e onde está a versão nova). */
function admin_atualizacoes_choque_detalhe(int $id): string {
    global $_GESTOR;
    $e = function($t){ return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); };
    $v = function($id){ global $_GESTOR; return htmlspecialchars((string)gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>$id]), ENT_QUOTES, 'UTF-8'); };
    $res = @banco_query("SELECT * FROM atualizacoes_choques WHERE id_atualizacoes_choques=".(int)$id." LIMIT 1");
    $r = $res ? banco_fetch_assoc($res) : null;
    if(!$r) return '<div class="ui warning message">'.$v('updates-clash-not-found').'</div>';
    $diff = (string)($r['diff'] ?? '');
    return '<div class="ui header">'.$v('updates-clash-title').': <code>'.$e($r['caminho']).'</code></div>'
        .'<div class="ui list">'
        .'<div class="item"><b>'.$v('updates-clash-col-reason').':</b> '.$v('updates-clash-'.$r['motivo']).'</div>'
        .'<div class="item"><b>'.$v('updates-clash-col-layer').':</b> '.$e($r['camada']).' ('.$e($r['origem']).'), '.$v('updates-clash-col-version').' '.$e($r['versao']).'</div>'
        .'<div class="item"><b>'.$v('updates-clash-col-owner').':</b> '.$e($r['camada_dona'] ?? '—').'</div>'
        .'<div class="item"><b>'.$v('updates-clash-copy').':</b> '.($r['copia'] ? '<code>'.$e($r['copia']).'</code>' : '—').'</div>'
        .'</div>'
        .($diff !== '' ? '<textarea class="codemirror-log" data-mode="diff" style="display:none;" rows="30">'.$e($diff).'</textarea><pre class="fallback-log" style="max-height:60vh;overflow:auto;">'.$e($diff).'</pre>' : '');
}

function admin_atualizacoes_detalhe(): void {
    global $_GESTOR;
    $dirLogs = admin_atualizacoes_logs_dir();
    $dirSess = admin_atualizacoes_temp_sessions_dir();
    $log = $_GET['log'] ?? null; $plano = $_GET['plano'] ?? null;
    $conteudo = '';
    if(isset($_GET['choque']) && ctype_digit((string)$_GET['choque'])){
        $conteudo = admin_atualizacoes_choque_detalhe((int)$_GET['choque']);
    } elseif($log){
        $path = realpath($dirLogs.$log);
        if((!$path || strpos($path,$dirLogs)!==0 || !is_file($path)) && is_file($dirSess.$log)) {
            $path = realpath($dirSess.$log);
        }
        if($path && ( (strpos($path,$dirLogs)===0) || (strpos($path,$dirSess)===0) ) && is_file($path)) {
            $raw = @file_get_contents($path);
            $safe = htmlspecialchars($raw);
            $conteudo = '<div class="ui header">Log: '.htmlspecialchars($log).'</div>'
                .'<textarea class="codemirror-log" data-mode="text" style="display:none;" rows="30">'.$safe.'</textarea>'
                .'<pre class="fallback-log" style="max-height:60vh;overflow:auto;">'.$safe.'</pre>';
        } else $conteudo = '<div class="ui warning message">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-invalid-log']).'</div>';
    } elseif($plano){
        // Os planos moram na pasta de logs; a variável `$dir` usada antes não existia neste escopo.
        $path = realpath($dirLogs.$plano);
        if($path && strpos($path,$dirLogs)===0 && is_file($path)) {
            $json = @file_get_contents($path);
            $safe = htmlspecialchars($json);
            $conteudo = '<div class="ui header">Plano: '.htmlspecialchars($plano).'</div>'
                .'<textarea class="codemirror-log" data-mode="application/json" style="display:none;" rows="30">'.$safe.'</textarea>'
                .'<pre class="fallback-log" style="max-height:60vh;overflow:auto;">'.$safe.'</pre>';
        } else $conteudo = '<div class="ui warning message">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-invalid-plan']).'</div>';
    } else {
        $conteudo = '<div class="ui message">'.gestor_variaveis(['modulo'=>$_GESTOR['modulo-id'],'id'=>'updates-select-log-plan']).'</div>';
    }
    $comp = gestor_componente(['id' => 'atualizacoes-detalhe-comp']);
    $comp = modelo_var_troca_tudo($comp,'#conteudo#',$conteudo);
    $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#dynamic-content#',$comp);
    // Incluir CodeMirror assets somente aqui (detalhe)
	if(!function_exists('assets_externos_incluir') && !empty($_GESTOR['bibliotecas-path'])){
		require_once($_GESTOR['bibliotecas-path'].'assets-externos.php');
	}
	assets_externos_incluir('codemirror');

    // Incluir JS do módulo
    if(function_exists('gestor_pagina_javascript_incluir')) gestor_pagina_javascript_incluir();
}


// ==== Ajax

// --------------- AJAX Handlers ---------------
// Wrapper que encaminha chamadas ao script atualizacoes-sistema.php usando a API web (action=...)
function admin_atualizacoes_call_system(array $params): array {
    global $_GESTOR;
    $script = $_GESTOR['base-path'].'controladores/atualizacoes/atualizacoes-sistema.php';
    if(!is_file($script)) return ['error'=>'Script não encontrado'];
    // Construir query local (inclui action)
    $query = http_build_query($params);
    // Inclui script em escopo isolado capturando saída
    $_GET = $_REQUEST = [];
    parse_str($query,$_GET); $_REQUEST=$_GET; // simula requisição
    ob_start();
    include $script; // script imprime JSON
    $raw = ob_get_clean();
    $json = json_decode($raw,true);
    if($json===null) return ['error'=>'Resposta inválida','raw'=>$raw];
    return $json;
}

function admin_atualizacoes_ajax_update(){
    global $_GESTOR;
    $params = $_POST['params'] ?? $_GET['params'] ?? [];
    if(!is_array($params)) $params=[];
    $acao = $params['acao'] ?? '';
    $sid = $params['sid'] ?? '';
    $resp = [];
    try {
        switch($acao){
            // notas: o 'start' recebe flags via map abaixo (inclui agora 'wipe')
            case 'start':
                $modo = $params['modo'] ?? 'full';
                $mapModo = [];
                if($modo==='only-files') $mapModo['only_files']=1; elseif($modo==='only-db') $mapModo['only_db']=1; // flags que webStart interpreta
                // Flags extras opcionais
                $extraFlagsMap = [
                    'local'=>'local',
                    'debug'=>'debug',
                    'dry_run'=>'dry_run',
                    'no_db'=>'no_db',
                    'no_verify'=>'no_verify',
                    'download_only'=>'download_only',
                    'skip_download'=>'skip_download',
                    'force_all'=>'force_all',
                    'log_diff'=>'log_diff',
                    'backup'=>'backup',
                    'wipe'=>'wipe', // nova flag: envia --wipe ao backend
                    'clean_temp'=>'clean_temp',
                    'csrf_capable'=>'csrf_capable',
                ];
                $extras=[]; foreach($extraFlagsMap as $k=>$flag){ if(!empty($params[$k])) $extras[$flag]=1; }
                if(!empty($params['tables'])) $extras['tables']=$params['tables'];
                if(!empty($params['logs_retention_days'])) $extras['logs_retention_days']=(int)$params['logs_retention_days'];
                $domain = trim((string)($params['domain'] ?? ''));
                $resp = admin_atualizacoes_call_system(array_filter(array_merge([
                    'action'=>'start',
                    'domain'=>$domain !== '' ? $domain : null,
                    'tag'=>$params['tag'] ?? null,
                ],$mapModo,$extras)));
                break;
            case 'deploy':
                $resp = admin_atualizacoes_call_system(['action'=>'deploy','sid'=>$sid]);
                break;
            case 'db':
                $resp = admin_atualizacoes_call_system(['action'=>'db','sid'=>$sid]);
                break;
            case 'finalize':
                $resp = admin_atualizacoes_call_system(['action'=>'finalize','sid'=>$sid]);
                break;
            case 'status':
                $resp = admin_atualizacoes_call_system(['action'=>'status','sid'=>$sid]);
                break;
            case 'cancel':
                $resp = admin_atualizacoes_call_system(['action'=>'cancel','sid'=>$sid]);
                break;
            default:
                $resp = ['error'=>'Ação AJAX desconhecida'];
        }
        if(isset($resp['error'])) {
            $_GESTOR['ajax-json']=['status'=>'erro','erro'=>$resp['error'],'data'=>$resp];
        } else {
            $_GESTOR['ajax-json']=['status'=>'ok','data'=>$resp];
        }
    } catch(Throwable $e){
        $_GESTOR['ajax-json']=['status'=>'erro','erro'=>$e->getMessage()];
    }
}

// ================= Interface Principal =================

function admin_atualizacoes_start(){
    global $_GESTOR;

    gestor_incluir_bibliotecas();

    if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'update': admin_atualizacoes_ajax_update(); break;
		}
		
		interface_ajax_finalizar();
	} else {
        interface_iniciar();

        switch($_GESTOR['opcao']){
            case 'detalhe-atualizacao': admin_atualizacoes_detalhe(); break;
            case 'disparar': /* página descontinuada: redireciona para lista */ admin_atualizacoes_listar(); break;
            case 'listar-atualizacoes':
            default: admin_atualizacoes_listar(); break;
        }

        interface_finalizar();
    }
}

admin_atualizacoes_start();

?>
